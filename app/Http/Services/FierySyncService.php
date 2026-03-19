<?php

namespace App\Http\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Services\FaseStatoService;
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
        $this->syncJobCompletati($operatore, $commessaCode);

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
                    // NON auto-terminare mentre il job è ancora in stampa sulla Fiery
                    $fase->save();
                }
            } elseif ($fase->stato == 3) {
                // Fase terminata ma job ancora in stampa → ripristina a stato 2
                $fase->stato = 2;
                $fase->data_fine = null;
                if ($copieFatte > 0) {
                    $fase->qta_prod = $copieFatte;
                }
                $fase->save();
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
     * Per fasi in stato 2: aggiorna qta_prod e auto-termina.
     * Per fasi in stato 0/1: se il job Fiery è completato, avvia e termina direttamente
     * (recupera i job che si completano tra due polling).
     */
    protected function syncJobCompletati(Operatore $operatore, ?string $commessaInStampa = null): void
    {
        // Usa Accounting API per fogli totali storici (tutti i run aggregati)
        $accounting = $this->fiery->getAccountingPerCommessa();

        // Fallback: se accounting non disponibile, usa job list
        if (!$accounting) {
            $jobs = $this->fiery->getJobs();
            if (!$jobs) return;

            $accounting = [];
            foreach ($jobs as $job) {
                if ($job['state'] !== 'completed') continue;
                if (!$job['commessa']) continue;
                if ($job['copies_printed'] <= 0 && $job['total_sheets'] <= 0) continue;

                $code = $job['commessa'];
                if (!isset($accounting[$code])) {
                    $accounting[$code] = ['fogli' => 0, 'copie' => 0];
                }
                $accounting[$code]['fogli'] += $job['total_sheets'];
                $accounting[$code]['copie'] += $job['copies_printed'];
            }
        }

        if (empty($accounting)) return;

        // Filtra solo commesse con fogli > 0
        $commesseCodes = [];
        foreach ($accounting as $code => $data) {
            if (($data['fogli'] ?? 0) > 0 || ($data['copie'] ?? 0) > 0) {
                $commesseCodes[] = $code;
            }
        }
        if (empty($commesseCodes)) return;

        $ordiniIds = Ordine::whereIn('commessa', $commesseCodes)->pluck('id');
        if ($ordiniIds->isEmpty()) return;

        $fasiDigitali = OrdineFase::whereIn('stato', [0, 1, 2])
            ->whereIn('ordine_id', $ordiniIds)
            ->where(function ($q) {
                $q->whereHas('faseCatalogo', function ($sub) {
                    $sub->where('reparto_id', self::REPARTO_DIGITALE_ID);
                })->orWhere('fase', 'STAMPA');
            })
            ->with('ordine')
            ->get();

        if ($fasiDigitali->isEmpty()) return;

        foreach ($fasiDigitali as $fase) {
            $commessaCode = $fase->ordine->commessa ?? null;
            if (!$commessaCode) continue;
            if (!isset($accounting[$commessaCode])) continue;

            // Non auto-terminare la commessa attualmente in stampa sulla Fiery
            if ($commessaCode === $commessaInStampa) continue;

            $acc = $accounting[$commessaCode];
            $fogliTotali = (int) ($acc['fogli'] ?? 0);
            $copieTotali = (int) ($acc['copie'] ?? 0);
            // Usa il valore più alto tra fogli e copie per qta_prod
            $qtaProdotta = max($fogliTotali, $copieTotali);

            if ($fase->stato == 2) {
                // Fase già avviata: aggiorna qta_prod e auto-termina
                $qtaAttuale = (int) ($fase->qta_prod ?: 0);
                if ($qtaProdotta > $qtaAttuale) {
                    $fase->qta_prod = $qtaProdotta;
                    $this->autoTerminaSeCompletata($fase, $operatore);
                    $fase->save();
                }
            } elseif (in_array($fase->stato, [0, 1])) {
                // Fase non ancora avviata ma job Fiery completato → avvia e termina
                $fase->stato = 2;
                $fase->qta_prod = $qtaProdotta;
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
                $this->autoTerminaSeCompletata($fase, $operatore);
                $fase->save();

                // Ricalcola stati fasi successive
                if ($fase->stato == 3) {
                    FaseStatoService::ricalcolaStati($fase->ordine_id);
                }
            }
        }
    }

    /**
     * Auto-termina la fase se qta_prod >= target.
     * Stessa logica di FaseStatoService::controllaCompletamento():
     * 1. qta_prod >= qta_richiesta (priorità - senza scarti)
     * 2. qta_prod >= qta_fase
     * 3. qta_prod >= qta_carta (quando qta_fase = 0)
     * 4. qta_prod + scarti_previsti >= qta_carta
     * Modifica la fase in-place (il chiamante deve fare save).
     */
    protected function autoTerminaSeCompletata(OrdineFase $fase, Operatore $operatore): void
    {
        if ($fase->stato != 2) return;

        $qtaProd = (int) ($fase->qta_prod ?: 0);
        if ($qtaProd <= 0) return;

        $qtaRichiesta = (int) ($fase->ordine->qta_richiesta ?? 0);
        $qtaFase = (int) ($fase->qta_fase ?? 0);
        $qtaCarta = (int) ($fase->ordine->qta_carta ?? 0);

        $completata = false;

        // Check 1: qta_prod >= qta_richiesta (pezzi prodotti coprono l'ordine)
        if ($qtaRichiesta > 0 && $qtaProd >= $qtaRichiesta) {
            $completata = true;
        }

        // Check 2: qta_prod >= qta_fase
        if (!$completata && $qtaFase > 0 && $qtaProd >= $qtaFase) {
            $completata = true;
        }

        // Check 3: qta_prod >= qta_carta (quando qta_fase è 0)
        if (!$completata && $qtaFase == 0 && $qtaCarta > 0 && $qtaProd >= $qtaCarta) {
            $completata = true;
        }

        // Check 4: qta_prod + scarti_previsti >= qta_carta
        if (!$completata && ($fase->scarti_previsti ?? 0) > 0 && $qtaCarta > 0) {
            if (($qtaProd + $fase->scarti_previsti) >= $qtaCarta) {
                $completata = true;
            }
        }

        if ($completata) {
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

        if (!preg_match('/^(\d+)[\s_]/', $jobName, $matches)) {
            return null;
        }

        // Cerca la commessa nel DB per determinare l'anno corretto
        $numPadded = str_pad($matches[1], 7, '0', STR_PAD_LEFT);
        $ordine = \App\Models\Ordine::where('commessa', 'LIKE', $numPadded . '-%')->first();
        if ($ordine) {
            return $ordine->commessa;
        }

        // Fallback: anno corrente
        return '00' . $matches[1] . '-' . date('y');
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
