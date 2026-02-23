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
     * - Se la Fiery sta stampando un job → avvia la fase digitale corrispondente
     * - Se il job cambia o la Fiery è idle → termina le fasi della commessa precedente
     */
    public function sincronizza(): ?array
    {
        $status = $this->fiery->getServerStatus();
        if (!$status || !$status['online']) return null;

        $operatore = $this->getOperatoreFiery();
        if (!$operatore) return null;

        $jobName = $status['stampa']['documento'] ?? null;
        $commessaCode = $this->estraiCommessa($jobName);

        // Se non c'è job attivo o commessa non trovata → non fare nulla (idle)
        if (!$commessaCode) {
            return null;
        }

        $ordini = Ordine::where('commessa', $commessaCode)->get();
        if ($ordini->isEmpty()) {
            return null;
        }

        // Termina fasi digitali di ALTRE commesse ancora avviate da questo operatore
        $ordineIds = $ordini->pluck('id')->toArray();
        $this->terminaFasiPrecedenti($operatore, $ordineIds);

        // Trova fasi digitali per TUTTI gli ordini della commessa corrente
        $fasiDigitali = collect();
        foreach ($ordini as $ordine) {
            $fasi = $this->troveFasiDigitali($ordine);
            $fasiDigitali = $fasiDigitali->merge($fasi);
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
                $fase->save();

                // Aggiungi operatore alla pivot se non presente
                if (!$fase->operatori()->where('operatore_id', $operatore->id)->exists()) {
                    $fase->operatori()->attach($operatore->id, [
                        'data_inizio' => now(),
                        'data_fine' => null,
                    ]);
                }

                $faseAvviata = $fase;
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
            ->with('operatori');

        return $query->get();
    }

    /**
     * Verifica se il cod_carta indica un formato compatibile con la stampa digitale (≤ 33x48).
     * Formati cod_carta: "IPO.33.48.250", "PO.70.100.350", ecc.
     * Pattern: PREFISSO.LARGHEZZA.ALTEZZA.GRAMMATURA
     */
    public function isFormatoDigitale(?string $codCarta): bool
    {
        if (!$codCarta) return false;

        // Estrai numeri dal cod_carta: "IPO.33.48.250" → [33, 48, 250]
        if (!preg_match('/\.(\d+)\.(\d+)\./', $codCarta, $matches)) {
            return false;
        }

        $dim1 = (int) $matches[1];
        $dim2 = (int) $matches[2];

        // Il formato massimo della digitale è 33x48
        $minDim = min($dim1, $dim2);
        $maxDim = max($dim1, $dim2);

        return $minDim <= 33 && $maxDim <= 48;
    }

    /**
     * Termina le fasi digitali avviate su ALTRE commesse dallo stesso operatore.
     * (L'operatore ha iniziato un nuovo job → il precedente è terminato)
     */
    protected function terminaFasiPrecedenti(Operatore $operatore, array $ordineCorrenteIds): void
    {
        // Trova fasi avviate (stato=2) su altre commesse con questo operatore
        // Include sia fasi reparto digitale che fasi STAMPA su formati ≤ 33x48
        $fasiDaTerminare = OrdineFase::where('stato', 2)
            ->whereNotIn('ordine_id', $ordineCorrenteIds)
            ->where(function ($q) {
                $q->whereHas('faseCatalogo', function ($sub) {
                    $sub->where('reparto_id', self::REPARTO_DIGITALE_ID);
                })->orWhere('fase', 'STAMPA');
            })
            ->whereHas('operatori', function ($q) use ($operatore) {
                $q->where('operatore_id', $operatore->id);
            })
            ->get();

        foreach ($fasiDaTerminare as $fase) {
            $fase->stato = 3;
            if (!$fase->data_fine) {
                $fase->data_fine = now()->format('Y-m-d H:i:s');
            }
            $fase->save();

            // Aggiorna data_fine sulla pivot
            $fase->operatori()->updateExistingPivot($operatore->id, [
                'data_fine' => now(),
            ]);
        }
    }
}
