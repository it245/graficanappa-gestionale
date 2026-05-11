<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Services;

use App\Models\FasiCatalogo;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Commessa\Enums\StatoCommessa;
use App\Modules\Commessa\Rules\StatoDerivatoRule;
use App\Services\FaseStatoService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servizio di alto livello sulla commessa: query DB + delega alle Rules pure.
 *
 * Strangler Fig (sessione 07/05/2026):
 *   I metodi aggiungiRiga()/aggiornaCampo()/eliminaRiga()/ricalcolaProgresso()
 *   sono stati estratti dal DashboardOwnerController per centralizzare la
 *   logica di mutazione (auto-suffix anno, side-effect note "Inviato a:",
 *   propagazione data_prevista_consegna su tutti gli ordini).
 *
 * @example
 *   $svc = app(CommessaService::class);
 *   $stato = $svc->getStatoAggregato('0067164-26');   // StatoCommessa::InCorso
 *   $svc->getQtaTotale('0067164-26');                  // 5000
 *   $svc->getQtaConsegnata('0067164-26');              // 2500
 */
final class CommessaService
{
    public function __construct(
        private readonly StatoDerivatoRule $statoRule = new StatoDerivatoRule(),
    ) {}

    /**
     * Stato aggregato della commessa (deriva da tutte le fasi di tutti gli ordini).
     */
    public function getStatoAggregato(string $codCommessa): StatoCommessa
    {
        $fasi = OrdineFase::query()
            ->whereHas('ordine', fn ($q) => $q->where('commessa', $codCommessa))
            ->get(['id', 'ordine_id', 'stato']);

        return $this->statoRule->calcolaStato($fasi);
    }

    /**
     * Somma `qta_richiesta` esclusi i semilavorati (cod_art LIKE 'SEMILAV%').
     * Convenzione MES: i semilavorati sono pezzi intermedi, non vendibili.
     */
    public function getQtaTotale(string $codCommessa): int
    {
        return (int) Ordine::query()
            ->where('commessa', $codCommessa)
            ->where(function ($q) {
                $q->whereNull('cod_art')
                  ->orWhere('cod_art', 'NOT LIKE', 'SEMILAV%');
            })
            ->sum('qta_richiesta');
    }

    /**
     * Quantità complessivamente fatturata/consegnata (qta_ddt_vendita).
     */
    public function getQtaConsegnata(string $codCommessa): int
    {
        return (int) Ordine::query()
            ->where('commessa', $codCommessa)
            ->where(function ($q) {
                $q->whereNull('cod_art')
                  ->orWhere('cod_art', 'NOT LIKE', 'SEMILAV%');
            })
            ->sum('qta_ddt_vendita');
    }

    /**
     * Ordini articoli finiti della commessa (esclude semilavorati).
     *
     * @return Collection<int, Ordine>
     */
    public function getOrdiniArticoliFiniti(string $codCommessa): Collection
    {
        return Ordine::query()
            ->where('commessa', $codCommessa)
            ->where(function ($q) {
                $q->whereNull('cod_art')
                  ->orWhere('cod_art', 'NOT LIKE', 'SEMILAV%');
            })
            ->orderBy('cod_art')
            ->get();
    }

    /**
     * Crea un ordine + ordine_fase manuali (dashboard owner "Aggiungi riga").
     *
     * Regole:
     * - se commessa non termina con "-YY" (anno 2 cifre), aggiunge suffisso anno corrente
     * - se passato fase_catalogo_id, usa quel nome/reparto; altrimenti fase = '-'
     *
     * @param  array<string,mixed>  $payload  campi validati da OwnerAggiungiRigaRequest
     */
    public function aggiungiRiga(array $payload): Ordine
    {
        $faseCatalogo = ! empty($payload['fase_catalogo_id'])
            ? FasiCatalogo::with('reparto')->find($payload['fase_catalogo_id'])
            : null;

        $commessa = trim((string) ($payload['commessa'] ?? ''));
        if (! preg_match('/-\d{2}$/', $commessa)) {
            $commessa .= '-'.date('y');
        }

        return DB::transaction(function () use ($payload, $commessa, $faseCatalogo): Ordine {
            $ordine = Ordine::create([
                'commessa'               => $commessa,
                'cliente_nome'           => trim((string) ($payload['cliente_nome'] ?? '')),
                'cod_art'                => trim((string) ($payload['cod_art'] ?? '')),
                'descrizione'            => trim((string) ($payload['descrizione'] ?? '')),
                'qta_richiesta'          => $payload['qta_richiesta'] ?? 0,
                'um'                     => $payload['um'] ?? 'FG',
                'stato'                  => 0,
                'data_registrazione'     => now()->toDateString(),
                'data_prevista_consegna' => $payload['data_prevista_consegna'] ?? null,
                'priorita'               => $payload['priorita'] ?? 0,
            ]);

            OrdineFase::create([
                'ordine_id'        => $ordine->id,
                'fase'             => $faseCatalogo ? $faseCatalogo->nome : '-',
                'fase_catalogo_id' => $faseCatalogo?->id,
                'stato'            => 0,
                'manuale'          => true,
            ]);

            return $ordine;
        });
    }

    /**
     * Aggiorna inline un singolo campo della fase o dell'ordine.
     *
     * Esegue parsing/cast (date d/m/Y, numerici, virgola->punto), gestisce
     * side-effect (priorita_manuale, data_fine al cambio stato, propagazione
     * data_prevista_consegna su TUTTI gli ordini della commessa, flag esterno
     * se note contengono "Inviato a:").
     *
     * @param  list<string>  $campiFase
     * @param  list<string>  $campiOrdine
     * @return array{success:bool, reload?:bool, messaggio?:string}
     */
    public function aggiornaCampo(
        OrdineFase $fase,
        string $campo,
        mixed $valore,
        array $campiFase,
        array $campiOrdine,
    ): array {
        // Parsing date
        if (in_array($campo, ['data_registrazione', 'data_prevista_consegna', 'data_inizio', 'data_fine'], true)) {
            if (in_array(trim((string) $valore), ['-', ''], true)) {
                $valore = null;
            } else {
                $formati = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'];
                $parsed = false;
                foreach ($formati as $fmt) {
                    try {
                        $valore = $valore ? Carbon::createFromFormat($fmt, trim((string) $valore))->format('Y-m-d H:i:s') : null;
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        // try next format
                    }
                }
                if (! $parsed && $valore) {
                    return ['success' => false, 'messaggio' => 'Formato data non valido'];
                }
            }
        }

        // Parsing numerici
        $campiNumerici = ['qta_prod', 'qta_carta', 'qta_richiesta', 'priorita', 'ore'];
        if (in_array($campo, $campiNumerici, true)) {
            $valore = $valore !== null ? (float) str_replace(',', '.', (string) $valore) : 0;
        }

        if ($campo === 'fase') {
            $nomeNuovo = trim((string) $valore) ?: '-';
            $fase->fase = $nomeNuovo;
            if ($nomeNuovo !== '-') {
                $faseCat = FasiCatalogo::where('nome', $nomeNuovo)->first();
                if ($faseCat) {
                    $fase->fase_catalogo_id = $faseCat->id;
                }
            }
            $fase->save();
            return ['success' => true];
        }

        if ($campo === 'reparto') {
            $nomeReparto = trim((string) $valore) ?: 'generico';
            $reparto = Reparto::firstOrCreate(['nome' => $nomeReparto]);
            $faseNome = $fase->fase ?: '-';
            $faseCat = FasiCatalogo::updateOrCreate(
                ['nome' => $faseNome],
                ['reparto_id' => $reparto->id],
            );
            $fase->fase_catalogo_id = $faseCat->id;
            $fase->save();
            return ['success' => true];
        }

        if (in_array($campo, $campiFase, true)) {
            $valorePrima = $fase->{$campo};
            $fase->{$campo} = $valore;

            if ($campo === 'priorita') {
                $fase->priorita_manuale = true;
                \Log::info("aggiornaCampo priorita: fase_id={$fase->id} commessa=".($fase->ordine->commessa ?? '-')." fase={$fase->fase} {$valorePrima}→{$valore} manuale=true");
            }

            $saved = $fase->save();
            if ($campo === 'priorita' && ! $saved) {
                \Log::warning("aggiornaCampo priorita SAVE FAILED fase_id={$fase->id}");
            }

            if ($campo === 'qta_prod') {
                FaseStatoService::controllaCompletamento($fase->id);
            }

            if ($campo === 'stato') {
                $statoNum = (int) $valore;
                if ($statoNum === 3 && ! $fase->data_fine) {
                    $fase->data_fine = now()->format('Y-m-d H:i:s');
                    $fase->save();
                }
                if ($statoNum === 2) {
                    $fase->data_fine = null;
                    $fase->save();
                }
                if ($statoNum <= 1) {
                    $fase->data_fine = null;
                    $fase->riaperta_at = now();
                    $fase->qta_prod_at_riapertura = (int) ($fase->qta_prod ?? 0);
                    if ($fase->esterno) {
                        $fase->esterno = false;
                    }
                    if ($fase->note && preg_match('/Inviato a:/i', $fase->note)) {
                        $fase->note = preg_replace('/,?\s*Inviato a:.*$/i', '', $fase->note);
                        $fase->note = trim($fase->note) ?: null;
                    }
                    $fase->save();
                }
                FaseStatoService::ricalcolaCommessa($fase->ordine->commessa);
            }

            // Note → flag esterno
            if ($campo === 'note' && preg_match('/\b(lavorato esternamente|esterno)\b|Inviato a:/i', (string) ($valore ?? ''))) {
                if (! $fase->esterno) {
                    $fase->esterno = true;
                    $fase->save();
                }
            }

            // Note "Inviato a:" → notifica spedizione + avvia stato 5
            if ($campo === 'note' && preg_match('/Inviato a:\s*(.+)/i', (string) ($valore ?? ''), $mInv)) {
                $fornitore = trim($mInv[1]);
                $commessa = $fase->ordine->commessa ?? '';
                $faseNome = $fase->faseCatalogo->nome_display ?? $fase->fase ?? '';
                $descrizione = mb_substr($fase->ordine->descrizione ?? '', 0, 60);

                if (in_array((int) $fase->stato, [0, 1], true)) {
                    $fase->stato = 5;
                    $fase->data_inizio = $fase->data_inizio ?? now();
                    $fase->esterno = true;
                    $fase->save();
                }

                DB::table('notifiche_spedizione')->insert([
                    'tipo'       => 'invio_esterno',
                    'commessa'   => $commessa,
                    'fase'       => $faseNome,
                    'fornitore'  => $fornitore,
                    'messaggio'  => "{$faseNome} commessa {$commessa} inviata a {$fornitore} — {$descrizione}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['success' => true];
        }

        if (in_array($campo, $campiOrdine, true)) {
            $fase->ordine->{$campo} = $valore;
            $fase->ordine->save();

            if ($campo === 'data_prevista_consegna') {
                Ordine::where('commessa', $fase->ordine->commessa)
                    ->where('id', '!=', $fase->ordine->id)
                    ->update(['data_prevista_consegna' => $valore]);

                FaseStatoService::ricalcolaStati($fase->ordine_id);

                return ['success' => true, 'reload' => true];
            }

            // Propaga descrizione a TUTTI gli ordini della stessa commessa
            // (header dettaglio + altre fasi mostrano la stessa desc unificata)
            if ($campo === 'descrizione') {
                Ordine::where('commessa', $fase->ordine->commessa)
                    ->where('id', '!=', $fase->ordine->id)
                    ->update(['descrizione' => $valore]);

                return ['success' => true, 'reload' => true];
            }

            return ['success' => true];
        }

        return ['success' => false, 'messaggio' => 'Campo non aggiornabile'];
    }

    /**
     * Elimina una fase: detach operatori dal pivot e delete.
     */
    public function eliminaRiga(OrdineFase $fase): bool
    {
        return (bool) DB::transaction(function () use ($fase): bool {
            $fase->operatori()->detach();
            return (bool) $fase->delete();
        });
    }

    /**
     * Ricalcola progresso/stato di tutte le fasi di una commessa.
     */
    public function ricalcolaProgresso(string $commessa): void
    {
        FaseStatoService::ricalcolaCommessa($commessa);
    }

    /**
     * Ricalcola tutti gli stati di tutte le commesse.
     */
    public function ricalcolaTutto(): void
    {
        FaseStatoService::ricalcolaTutti();
    }
}
