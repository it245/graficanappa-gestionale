<?php

namespace App\Http\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use Carbon\Carbon;

class FierySyncService
{
    protected FieryService $fiery;

    // Reparto digitale
    const REPARTO_DIGITALE_ID = 4;

    public function __construct(FieryService $fiery)
    {
        $this->fiery = $fiery;
    }

    /**
     * Sincronizza lo stato Fiery con le fasi del MES.
     *
     * 1. WebTools: rileva job attualmente in stampa → avvia fase
     * 2. API v5 Jobs: processa TUTTI i job completati → aggiorna qta_prod e auto-termina
     */
    public function sincronizza(): ?array
    {
        $status = $this->fiery->getServerStatus();
        if (!$status || !$status['online']) return null;

        $operatore = $this->getOperatoreFiery();
        if (!$operatore) return null;

        $jobName = $status['stampa']['documento'] ?? null;
        $commessaCode = $this->estraiCommessa($jobName);
        $copieFatte = (int) ($status['stampa']['copie_fatte'] ?? 0);

        // Se non c'è job attivo → Fiery idle
        if (!$commessaCode) {
            $this->terminaFasiDopoOrario($operatore);
        }

        // === FASE 1: Sync job in stampa (WebTools - real-time) ===
        $risultato = null;
        if ($commessaCode) {
            $risultato = $this->syncJobInStampa($commessaCode, $copieFatte, $operatore);
        }

        // === FASE 2: Sync job completati (API v5 - storico) ===
        $this->syncJobCompletati($operatore);

        return $risultato;
    }

    /**
     * Sync del job attualmente in stampa (da WebTools).
     * Avvia la fase digitale corrispondente e aggiorna qta_prod real-time.
     */
    protected function syncJobInStampa(string $commessaCode, int $copieFatte, Operatore $operatore): ?array
    {
        $ordini = Ordine::where('commessa', $commessaCode)->get();
        if ($ordini->isEmpty()) return null;

        // Termina fasi digitali di ALTRE commesse ancora avviate da questo operatore
        $ordineIds = $ordini->pluck('id')->toArray();
        $this->terminaFasiPrecedenti($operatore, $ordineIds);

        // Trova fasi digitali per TUTTI gli ordini della commessa corrente
        $fasiDigitali = collect();
        $fasiGiaViste = [];
        foreach ($ordini as $ordine) {
            $fasi = $this->troveFasiDigitali($ordine);
            foreach ($fasi as $fase) {
                if (!isset($fasiGiaViste[$fase->fase])) {
                    $fasiDigitali->push($fase);
                    $fasiGiaViste[$fase->fase] = true;
                }
            }
        }
        if ($fasiDigitali->isEmpty()) return null;

        // Avvia le fasi digitali della commessa corrente
        $faseAvviata = null;
        foreach ($fasiDigitali as $fase) {
            if (in_array($fase->stato, [0, '0', 1, '1'])) {
                $fase->stato = 2;
                if (!$fase->data_inizio) {
                    $fase->data_inizio = now()->format('Y-m-d H:i:s');
                }
                if (!$fase->operatore_id) {
                    $fase->operatore_id = $operatore->id;
                }
                if ($copieFatte > 0) {
                    $fase->qta_prod = $copieFatte;
                }
                $fase->save();

                if (!$fase->operatori()->where('operatore_id', $operatore->id)->exists()) {
                    $fase->operatori()->attach($operatore->id, [
                        'data_inizio' => now(),
                        'data_fine' => null,
                    ]);
                }
                $faseAvviata = $fase;

            } elseif ($fase->stato == 2) {
                // Fase già in corso: aggiorna qta_prod
                if ($copieFatte > 0) {
                    $fase->qta_prod = $copieFatte;
                    $this->autoTerminaSeCompletata($fase, $operatore);
                    $fase->save();
                }
            }
        }

        return [
            'commessa' => $commessaCode,
            'cliente' => $ordini->first()->cliente_nome,
            'operatore' => $operatore->nome . ' ' . ($operatore->cognome ?? ''),
            'fase_avviata' => $faseAvviata?->fase,
        ];
    }

    /**
     * Sync dei job completati dall'API v5.
     * Per ogni job completed con codice commessa:
     * - Trova le fasi digitali della commessa
     * - Aggiorna qta_prod con copies_printed (se maggiore del valore attuale)
     * - Auto-termina se raggiunta la quantità target
     */
    protected function syncJobCompletati(Operatore $operatore): void
    {
        $jobs = $this->fiery->getJobs();
        if (!$jobs) return;

        // Raggruppa job completati per commessa, prendi max copies_printed
        $completatiPerCommessa = [];
        foreach ($jobs as $job) {
            if ($job['state'] !== 'completed') continue;
            if (!$job['commessa']) continue;
            if ($job['copies_printed'] <= 0) continue;

            $code = $job['commessa'];
            if (!isset($completatiPerCommessa[$code])) {
                $completatiPerCommessa[$code] = 0;
            }
            // Usa il MAX delle copie tra tutti i job della stessa commessa
            // (ogni job è un file diverso: copertina, interni, ecc. ma le copie sono per prodotto)
            $completatiPerCommessa[$code] = max($completatiPerCommessa[$code], $job['copies_printed']);
        }

        // Per ogni commessa con job completati, aggiorna le fasi MES
        foreach ($completatiPerCommessa as $commessaCode => $maxCopie) {
            $ordini = Ordine::where('commessa', $commessaCode)->get();
            if ($ordini->isEmpty()) continue;

            foreach ($ordini as $ordine) {
                $fasi = $this->troveFasiDigitali($ordine);
                foreach ($fasi as $fase) {
                    // Solo fasi avviate (stato 2) o non ancora avviate (0, 1)
                    if (!in_array($fase->stato, [0, '0', 1, '1', 2, '2'])) continue;

                    $qtaAttuale = (int) ($fase->qta_prod ?: 0);

                    // Aggiorna solo se il nuovo valore è maggiore (idempotente)
                    if ($maxCopie > $qtaAttuale) {
                        // Se la fase non è ancora avviata, avviala
                        if (in_array($fase->stato, [0, '0', 1, '1'])) {
                            $fase->stato = 2;
                            if (!$fase->data_inizio) {
                                $fase->data_inizio = now()->format('Y-m-d H:i:s');
                            }
                            if (!$fase->operatore_id) {
                                $fase->operatore_id = $operatore->id;
                            }
                            if (!$fase->operatori()->where('operatore_id', $operatore->id)->exists()) {
                                $fase->operatori()->attach($operatore->id, [
                                    'data_inizio' => now(),
                                    'data_fine' => null,
                                ]);
                            }
                        }

                        $fase->qta_prod = $maxCopie;
                        $this->autoTerminaSeCompletata($fase, $operatore);
                        $fase->save();
                    }
                }
            }
        }
    }

    /**
     * Auto-termina la fase se qta_prod >= target (qta_carta o qta_richiesta).
     * Modifica la fase in-place (il chiamante deve fare save).
     */
    protected function autoTerminaSeCompletata(OrdineFase $fase, Operatore $operatore): void
    {
        if ($fase->stato != 2) return;

        $qtaTarget = $fase->ordine->qta_carta ?: ($fase->ordine->qta_richiesta ?: 0);
        $qtaProd = (int) ($fase->qta_prod ?: 0);

        if ($qtaTarget > 0 && $qtaProd >= $qtaTarget) {
            $fase->stato = 3;
            if (!$fase->data_fine) {
                $fase->data_fine = now()->format('Y-m-d H:i:s');
            }
            $fase->operatori()->updateExistingPivot($operatore->id, [
                'data_fine' => now(),
            ]);
        }
    }

    /**
     * Estrae il codice commessa dal nome del job Fiery.
     * "66539_Schede_BrochureMaurelliGroup_2026_33x48.pdf" → "0066539-26"
     */
    public function estraiCommessa(?string $jobName): ?string
    {
        if (!$jobName) return null;

        if (!preg_match('/^(\d+)_/', $jobName, $matches)) {
            return null;
        }

        return '00' . $matches[1] . '-26';
    }

    /**
     * Trova l'operatore Fiery dal config o dal DB.
     */
    protected function getOperatoreFiery(): ?Operatore
    {
        $nomeCompleto = config('fiery.operatore', 'Francesco Verde');
        $parts = explode(' ', $nomeCompleto, 2);
        $nome = $parts[0] ?? '';
        $cognome = $parts[1] ?? '';

        return Operatore::whereRaw('LOWER(nome) = ? AND LOWER(cognome) = ?', [
            strtolower($nome),
            strtolower($cognome),
        ])->where('attivo', 1)->first();
    }

    /**
     * Trova le fasi gestite dalla Fiery per un ordine:
     * 1. Fasi del reparto digitale (UVSPOT, FOIL, ZUND, STAMPAINDIGO, ecc.)
     * 2. Fasi STAMPA su ordini con formato carta ≤ 33x48 (stampa digitale)
     */
    protected function troveFasiDigitali(Ordine $ordine)
    {
        $query = OrdineFase::where('ordine_id', $ordine->id)
            ->where(function ($q) use ($ordine) {
                // Reparto digitale
                $q->whereHas('faseCatalogo', function ($sub) {
                    $sub->where('reparto_id', self::REPARTO_DIGITALE_ID);
                });

                // Oppure fase STAMPA con formato carta ≤ 33x48
                if ($this->isFormatoDigitale($ordine->cod_carta)) {
                    $q->orWhere('fase', 'STAMPA');
                }
            })
            ->with(['operatori', 'ordine']);

        return $query->get();
    }

    /**
     * Verifica se il cod_carta indica un formato compatibile con la stampa digitale (≤ 33x48).
     */
    public function isFormatoDigitale(?string $codCarta): bool
    {
        if (!$codCarta) return false;

        if (!preg_match('/\.(\d+)\.(\d+)\./', $codCarta, $matches)) {
            return false;
        }

        $dim1 = (int) $matches[1];
        $dim2 = (int) $matches[2];

        $minDim = min($dim1, $dim2);
        $maxDim = max($dim1, $dim2);

        return $minDim <= 33 && $maxDim <= 48;
    }

    /**
     * Termina fasi di altre commesse (se qta_prod >= target).
     */
    protected function terminaFasiPrecedenti(Operatore $operatore, array $ordineCorrenteIds): void
    {
        $fasiDaValutare = OrdineFase::where('stato', 2)
            ->whereNotIn('ordine_id', $ordineCorrenteIds)
            ->where(function ($q) {
                $q->whereHas('faseCatalogo', function ($sub) {
                    $sub->where('reparto_id', self::REPARTO_DIGITALE_ID);
                })->orWhere('fase', 'STAMPA');
            })
            ->whereHas('operatori', function ($q) use ($operatore) {
                $q->where('operatore_id', $operatore->id);
            })
            ->with('ordine')
            ->get();

        foreach ($fasiDaValutare as $fase) {
            $this->autoTerminaSeCompletata($fase, $operatore);
            $fase->save();
        }
    }

    protected function terminaFasiDopoOrario(Operatore $operatore): void
    {
        $fasiAperte = OrdineFase::where('stato', 2)
            ->where(function ($q) {
                $q->whereHas('faseCatalogo', function ($sub) {
                    $sub->where('reparto_id', self::REPARTO_DIGITALE_ID);
                })->orWhere('fase', 'STAMPA');
            })
            ->whereHas('operatori', function ($q) use ($operatore) {
                $q->where('operatore_id', $operatore->id);
            })
            ->with('ordine')
            ->get();

        foreach ($fasiAperte as $fase) {
            $this->autoTerminaSeCompletata($fase, $operatore);
            $fase->save();
        }
    }
}
