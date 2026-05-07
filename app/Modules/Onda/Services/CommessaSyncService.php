<?php

declare(strict_types=1);

namespace App\Modules\Onda\Services;

use App\Models\FasiCatalogo;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Reparti\Services\RepartoService;
use App\Services\FaseStatoService;

/**
 * Sincronizza una singola commessa da Onda, senza filtro data.
 *
 * Use-case: commessa "vecchia" che la sync incrementale non prende
 * (es. riapertura di una commessa archiviata, fix manuale).
 *
 * Strangler Fig: contiene il body completo migrato da
 * {@see \App\Services\OndaSyncService::sincronizzaSingolaCommessa()}.
 *
 * Differenze rispetto a {@see OrdineSyncService::sync()}:
 *  - non fa cleanup duplicati pre-esistenti (commessa singola → impatto limitato);
 *  - usa {@see OndaErpInterface::getOrdiniPerCommessa()} invece di getOrdiniDal();
 *  - chiama {@see FaseStatoService::ricalcolaCommessa()} solo per la commessa target;
 *  - ricalcola priorita/ore via DashboardOwnerController::calcolaOreEPriorita()
 *    (commessa singola → costo trascurabile).
 */
final class CommessaSyncService
{
    public function __construct(
        private readonly OndaErpInterface $onda,
    ) {}

    /**
     * Sync di una singola commessa Onda.
     *
     * @return array{
     *   trovata:bool,
     *   ordini_creati?:int,
     *   ordini_aggiornati?:int,
     *   fasi_create?:int,
     *   fasi?:list<string>,
     *   messaggio:string
     * }
     */
    public function sync(string $codCommessa): array
    {
        $repartoSvc = app(RepartoService::class);
        $mappaReparti = $repartoSvc->mappaSlugToId();
        $tipiFase = $repartoSvc->tipoFromCodice();
        $mappaPriorita = config('fasi_priorita');
        $oggi = now()->format('Y-m-d');

        // Pre-fetch scarti + righe ordine via Adapter (I/O delegato a OndaErpAdapter)
        $scartiMacchine = $this->onda->getScartiPrevistiPerMacchina();
        $righeOnda = $this->onda->getOrdiniPerCommessa($codCommessa);

        if (empty($righeOnda)) {
            return ['trovata' => false, 'messaggio' => "Commessa $codCommessa non trovata in Onda"];
        }

        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;
        $logFasi = [];

        // Raggruppa per PrdIdDoc (documento di produzione Onda)
        $gruppi = collect($righeOnda)->groupBy(function ($riga) {
            return $riga->CodCommessa . '|' . ($riga->PrdIdDoc ?? $riga->CodArt);
        });

        $codArtMax2 = [
            'Volumi','Vassoio','Vassoi','SPILLATI.OFFSET','SPILLATI.DIGITALE',
            'SOVRACOPERTA','RIVISTE.FRECCIA','riviste','RIVISTA.FRECCIA.128PP',
            'RICETTARI','Raccoglitori','Quaderni','Opuscoli','Libro.di.bordo',
            'Libricino','LibriBN','Libri','INSERTO.RIVISTA.NOTE.4pp',
            'I.Volumi','I.riviste','I.Raccoglitori','I.Quaderni','I.Poster',
            'I.Opuscoli','I.Menu','I.Libricino','I.Libri','I.copertina',
            'I.cataloghi','I.cartoline','I.Calendari.da.Tavolo',
            'I.Calendari.da.Muro','I.Calendari','I.Block.Notes',
            'I.Blocchi.autocopianti','I.Blocchi','I.Bilanci',
            'Espositori.da.Terra','Espositori.da.banco','Depliant','COPERTINA',
            'cataloghi','Calendari.da.Tavolo','Calendari.da.Muro','Calendari',
            'BROSSURATI.OFFSET','BROSSURATI.DIGITALE','brochure','Block.Notes',
            'Blocchi.Mod.TI','Blocchi.Mod.R1','Blocchi.Mod.K','Blocchi.Mod.CH69',
            'Blocchi.autocopianti.M40a','Blocchi.autocopianti','Blocchi','Bilanci',
        ];

        $repartiStampaOffset = Reparto::where('nome', 'stampa offset')->pluck('id');
        $dedupPerCommessa = [];
        $dedupQta = [];

        foreach ($gruppi as $chiave => $righe) {
            $prima = $righe->first();
            $commessa = trim($prima->CodCommessa ?? '');
            $codArt = trim($prima->CodArt ?? '');
            $descrizione = preg_replace('/\s+/', ' ', trim($prima->OC_Descrizione ?? ''));

            if (!$commessa) continue;

            $ordine = Ordine::where('commessa', $commessa)
                ->where('cod_art', $codArt)
                ->where('descrizione', $descrizione)
                ->first();

            if (!$ordine && $descrizione === '') {
                $ordine = Ordine::where('commessa', $commessa)
                    ->where('cod_art', $codArt)
                    ->first();
            }

            $datiOrdine = [
                'cliente_nome'           => trim($prima->ClienteNome ?? ''),
                'data_prevista_consegna' => $prima->DataPresConsegna && date('Y', strtotime($prima->DataPresConsegna)) >= 2024 ? date('Y-m-d', strtotime($prima->DataPresConsegna)) : null,
                'qta_richiesta'          => $prima->QtaDaProdurre ?? 0,
                'cod_carta'              => trim($prima->CodCarta ?? ''),
                'carta'                  => trim($prima->DescrizioneCarta ?? ''),
                'qta_carta'              => $prima->QtaCarta ?? 0,
                'UM_carta'               => trim($prima->UMCarta ?? ''),
                'supp_base_cm'           => ($prima->SuppBaseCM ?? 0) > 0 ? (float) $prima->SuppBaseCM : null,
                'supp_altezza_cm'        => ($prima->SuppAltezzaCM ?? 0) > 0 ? (float) $prima->SuppAltezzaCM : null,
                'resa'                   => ($prima->Resa ?? 0) > 0 ? (int) $prima->Resa : null,
                'tot_supporti'           => ($prima->TotSupporti ?? 0) > 0 ? (int) $prima->TotSupporti : null,
                'note_prestampa'         => trim($prima->NotePrestampa ?? ''),
                'responsabile'           => trim($prima->Responsabile ?? ''),
                'commento_produzione'    => trim($prima->CommentoProduzione ?? ''),
                'ordine_cliente'         => trim($prima->OrdineCliente ?? '') ?: null,
                'valore_ordine'          => ($prima->TotMerce ?? 0) > 0 ? (float) $prima->TotMerce : null,
                'costo_materiali'        => ($prima->CostoMateriali ?? 0) > 0 ? (float) $prima->CostoMateriali : null,
            ];

            if ($ordine) {
                // Non sovrascrivere data_prevista_consegna se modificata manualmente nel MES
                $dataConsegnaOnda = $datiOrdine['data_prevista_consegna'];
                if ($ordine->data_prevista_consegna && $ordine->data_prevista_consegna != $dataConsegnaOnda) {
                    unset($datiOrdine['data_prevista_consegna']);
                }
                // Non sovrascrivere cliente_nome se modificato manualmente nel MES
                $clienteOnda = $datiOrdine['cliente_nome'];
                if ($ordine->cliente_nome && $ordine->cliente_nome !== $clienteOnda && !empty($ordine->cliente_nome)) {
                    unset($datiOrdine['cliente_nome']);
                }
                $ordine->update($datiOrdine);
                $ordiniAggiornati++;
            } else {
                $ordine = Ordine::create(array_merge([
                    'commessa'    => $commessa,
                    'cod_art'     => $codArt,
                    'descrizione' => $descrizione,
                    'stato'       => 0,
                    'data_registrazione' => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : $oggi,
                ], $datiOrdine));
                $ordiniCreati++;
            }

            // BRT1 auto
            $hasBrt = OrdineFase::withTrashed()
                ->where(function ($q) {
                    $q->where('fase', 'BRT1')->orWhere('fase', 'brt1');
                })
                ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->exists();

            if (!$hasBrt) {
                $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
                $faseCatalogoBrt = FasiCatalogo::firstOrCreate(
                    ['nome' => 'BRT1'],
                    ['reparto_id' => $repartoBrt->id]
                );
                OrdineFase::create([
                    'ordine_id'        => $ordine->id,
                    'fase'             => 'BRT1',
                    'fase_catalogo_id' => $faseCatalogoBrt->id,
                    'qta_fase'         => $ordine->qta_richiesta ?? 0,
                    'um'               => 'FG',
                    'priorita'         => $mappaPriorita['BRT1'] ?? 96,
                    'stato'            => 0,
                ]);
                $fasiCreate++;
                $logFasi[] = 'BRT1 (auto)';
            }

            // Fasi
            $fasiViste = [];
            foreach ($righe as $riga) {
                $faseNome = trim($riga->CodFase ?? '');
                if (!$faseNome) continue;

                if ($faseNome === 'STAMPA') {
                    $macchina = trim($riga->CodMacchina ?? '');
                    if (stripos($macchina, 'INDIGO') !== false) {
                        $faseNome = (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false)
                            ? 'STAMPAINDIGOBN' : 'STAMPAINDIGO';
                    } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                        $faseNome = 'STAMPAXL106.' . $m[1];
                    } else {
                        $faseNome = 'STAMPAXL106';
                    }
                }

                // Chiave dedup: include PrdIdDoc per non bloccare fasi tra ordini diversi
                $chiaveFase = $riga->PrdIdDoc . '|' . $faseNome . '|' . ($riga->QtaDaLavorare ?? 0);
                if (isset($fasiViste[$chiaveFase])) continue;
                $fasiViste[$chiaveFase] = true;

                // TipoRiga da Onda: 1=interna, 2=esterna
                $tipoRigaOnda = (int)($riga->TipoRigaFase ?? 1);
                $faseEsterna = ($tipoRigaOnda === 2);

                if ($faseEsterna) {
                    $repartoNome = 'esterno';
                } else {
                    $repartoNome = $mappaReparti[$faseNome] ?? 'legatoria';
                }
                $tipo = $tipiFase[$faseNome] ?? 'monofase';
                $prioritaFase = $mappaPriorita[$faseNome] ?? 500;

                if (str_starts_with($faseNome, 'STAMPAXL106') && in_array($codArt, $codArtMax2)) {
                    $tipo = 'max 2 fasi';
                }

                $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
                $faseCatalogo = FasiCatalogo::updateOrCreate(
                    ['nome' => $faseNome],
                    ['reparto_id' => $reparto->id]
                );

                // Dedup fustella cilindrica per ordine (1 fase per cod_art)
                if ($repartoNome === 'fustella cilindrica') {
                    $chiaveDedup = $commessa . '|fustcil|' . $faseNome . '|' . $codArt;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInOrdine = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->where('ordine_id', $ordine->id)->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInOrdine) continue;
                }

                // Dedup fustella piana/generica per commessa
                if (in_array($repartoNome, ['fustella piana', 'fustella'])) {
                    $chiaveDedup = $commessa . '|fust|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                if ($repartoNome === 'stampa offset' && str_starts_with($faseNome, 'STAMPAXL106')) {
                    $chiaveDedup = $commessa . '|stampa_offset';
                    $maxStampa = in_array($codArt, $codArtMax2) ? 2 : 1;
                    if (!isset($dedupPerCommessa[$chiaveDedup])) {
                        $existCount = OrdineFase::whereHas('faseCatalogo', fn($q) =>
                                $q->whereIn('reparto_id', $repartiStampaOffset)
                                  ->where('nome', 'like', 'STAMPAXL106%'))
                            ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                            ->count();
                        $dedupPerCommessa[$chiaveDedup] = $existCount;
                    }
                    if ($dedupPerCommessa[$chiaveDedup] >= $maxStampa) continue;
                    $existsThisVariant = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    if ($existsThisVariant) continue;
                    $dedupPerCommessa[$chiaveDedup]++;
                }

                if ($repartoNome === 'stampa a caldo') {
                    $chiaveDedup = $commessa . '|stampa_caldo|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                if ($repartoNome === 'spedizione') {
                    $chiaveDedup = $commessa . '|spedizione|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                // Dedup TAGLIACARTE per ordine: 1 sola per ordine_id per fase_catalogo
                // Include withTrashed per non ricreare fasi eliminate manualmente
                if ($repartoNome === 'tagliacarte') {
                    $chiaveDedup = $ordine->id . '|tagliacarte|' . $faseNome;
                    if (isset($dedupPerCommessa[$chiaveDedup])) continue;
                    $existsInCommessa = OrdineFase::withTrashed()
                        ->where('fase_catalogo_id', $faseCatalogo->id)
                        ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->exists();
                    $dedupPerCommessa[$chiaveDedup] = true;
                    if ($existsInCommessa) continue;
                }

                $dataFase = [
                    'ordine_id'        => $ordine->id,
                    'fase'             => $faseNome,
                    'fase_catalogo_id' => $faseCatalogo->id,
                    'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                    'um'               => trim($riga->UMFase ?? 'FG'),
                    'priorita'         => $prioritaFase,
                    'stato'            => 0,
                    'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
                    'esterno'          => $repartoNome === 'esterno',
                ];

                // Rimappa STAMPA generico
                $faseOriginaleOnda = trim($riga->CodFase ?? '');
                if ($faseOriginaleOnda === 'STAMPA' && $faseNome !== 'STAMPA') {
                    $faseStampaGenerica = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', 'STAMPA')->first();
                    if ($faseStampaGenerica) {
                        $faseStampaGenerica->update([
                            'fase'             => $faseNome,
                            'fase_catalogo_id' => $faseCatalogo->id,
                            'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                            'um'               => trim($riga->UMFase ?? 'FG'),
                            'priorita'         => $prioritaFase,
                            'scarti_previsti'   => $scartiMacchine[trim($riga->CodMacchina ?? '')] ?? null,
                        ]);
                        $fasiCreate++;
                        $logFasi[] = $faseNome . ' (remapped)';
                        continue;
                    }
                }

                $exists = OrdineFase::withTrashed()
                    ->where('ordine_id', $ordine->id)
                    ->where('fase_catalogo_id', $faseCatalogo->id)->exists();

                if ($tipo === 'max 2 fasi') {
                    $count = OrdineFase::withTrashed()
                        ->where('ordine_id', $ordine->id)
                        ->where('fase_catalogo_id', $faseCatalogo->id)->count();
                    if ($count < 2) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                        $logFasi[] = $faseNome;
                    }
                } elseif (!$exists) {
                    OrdineFase::create($dataFase);
                    $fasiCreate++;
                    $logFasi[] = $faseNome;
                }
            }
        }

        // Ricalcola priorità e stati per la commessa
        $controller = app(\App\Http\Controllers\DashboardOwnerController::class);
        $fasiCommessa = OrdineFase::with('ordine')
            ->whereHas('ordine', fn($q) => $q->where('commessa', $codCommessa))
            ->get();
        foreach ($fasiCommessa as $fase) {
            $controller->calcolaOreEPriorita($fase);
            $fase->save();
        }
        FaseStatoService::ricalcolaCommessa($codCommessa);

        return [
            'trovata'           => true,
            'ordini_creati'     => $ordiniCreati,
            'ordini_aggiornati' => $ordiniAggiornati,
            'fasi_create'       => $fasiCreate,
            'fasi'              => $logFasi,
            'messaggio'         => "Commessa $codCommessa: $ordiniCreati ordini creati, $ordiniAggiornati aggiornati, $fasiCreate fasi create" .
                                   ($logFasi ? ' (' . implode(', ', $logFasi) . ')' : ''),
        ];
    }

    public function adapter(): OndaErpInterface
    {
        return $this->onda;
    }
}
