<?php

namespace App\Services;

use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoGiacenza;
use App\Models\MagazzinoMovimento;
use App\Models\MagazzinoEtichetta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MagazzinoService
{
    /**
     * Registra un carico (arrivo merce da bolla fornitore).
     * Aggiorna giacenza e crea etichetta QR.
     */
    public static function registraCarico(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $articolo = MagazzinoArticolo::findOrFail($data['articolo_id']);

            // Trova o crea giacenza per articolo+lotto (lockForUpdate per evitare race condition)
            $giacenza = MagazzinoGiacenza::where('articolo_id', $data['articolo_id'])
                ->where(function ($q) use ($data) {
                    empty($data['lotto']) ? $q->whereNull('lotto') : $q->where('lotto', $data['lotto']);
                })
                ->whereNull('ubicazione_id')
                ->lockForUpdate()
                ->first();

            if (!$giacenza) {
                $giacenza = MagazzinoGiacenza::create([
                    'articolo_id' => $data['articolo_id'],
                    'ubicazione_id' => null,
                    'lotto' => $data['lotto'] ?? null,
                    'quantita' => 0,
                ]);
            }

            $giacenza->quantita += $data['quantita'];
            $giacenza->data_ultimo_carico = now()->toDateString();
            $giacenza->save();

            // Registra movimento
            $movimento = MagazzinoMovimento::create([
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => null,
                'tipo' => 'carico',
                'quantita' => $data['quantita'],
                'giacenza_dopo' => $giacenza->quantita,
                'lotto' => $data['lotto'] ?? null,
                'fornitore' => $data['fornitore'] ?? null,
                'operatore_id' => $data['operatore_id'] ?? null,
                'note' => $data['note'] ?? null,
                'foto_bolla' => $data['foto_bolla'] ?? null,
                'ocr_raw' => $data['ocr_raw'] ?? null,
            ]);

            // Crea etichetta QR
            $qrCode = strtoupper(Str::uuid()->toString());
            $etichetta = MagazzinoEtichetta::create([
                'qr_code' => $qrCode,
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => null,
                'giacenza_id' => $giacenza->id,
                'lotto' => $data['lotto'] ?? null,
                'quantita_iniziale' => $data['quantita'],
            ]);

            return [
                'movimento' => $movimento,
                'giacenza' => $giacenza,
                'etichetta' => $etichetta,
            ];
        });
    }

    /**
     * Registra uno scarico (prelievo per produzione).
     * Lo scarico avviene SOLO per fase STAMPA (validazione server-side).
     */
    public static function registraScarico(array $data): MagazzinoMovimento
    {
        // Validazione server: scarico solo per fase STAMPA
        $fase = strtoupper(trim($data['fase'] ?? ''));
        if (!str_starts_with($fase, 'STAMPA')) {
            throw new \RuntimeException('Scarico consentito solo per fasi di stampa (STAMPA, STAMPAXL106, STAMPAINDIGO, ecc.)');
        }

        return DB::transaction(function () use ($data) {
            // lockForUpdate per evitare race condition tra due tablet
            $query = MagazzinoGiacenza::where('articolo_id', $data['articolo_id']);
            $query->whereNull('ubicazione_id');
            empty($data['lotto']) ? $query->whereNull('lotto') : $query->where('lotto', $data['lotto']);
            $giacenza = $query->lockForUpdate()->firstOrFail();

            if ($giacenza->quantita < $data['quantita']) {
                throw new \RuntimeException("Giacenza insufficiente: disponibili {$giacenza->quantita}, richiesti {$data['quantita']}");
            }

            $giacenza->quantita -= $data['quantita'];
            $giacenza->data_ultimo_scarico = now()->toDateString();
            $giacenza->save();

            return MagazzinoMovimento::create([
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => null,
                'tipo' => 'scarico',
                'quantita' => -$data['quantita'],
                'giacenza_dopo' => $giacenza->quantita,
                'lotto' => $data['lotto'] ?? null,
                'commessa' => $data['commessa'] ?? null,
                'fase' => $data['fase'] ?? null,
                'operatore_id' => $data['operatore_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    /**
     * Registra un reso (rientro fogli avanzati).
     */
    public static function registraReso(array $data): MagazzinoMovimento
    {
        return DB::transaction(function () use ($data) {
            $giacenza = MagazzinoGiacenza::where('articolo_id', $data['articolo_id'])
                ->where(function ($q) use ($data) {
                    empty($data['lotto']) ? $q->whereNull('lotto') : $q->where('lotto', $data['lotto']);
                })
                ->whereNull('ubicazione_id')
                ->lockForUpdate()
                ->first();

            if (!$giacenza) {
                $giacenza = MagazzinoGiacenza::create([
                    'articolo_id' => $data['articolo_id'],
                    'ubicazione_id' => null,
                    'lotto' => $data['lotto'] ?? null,
                    'quantita' => 0,
                ]);
            }

            $giacenza->quantita += $data['quantita'];
            $giacenza->data_ultimo_carico = now()->toDateString();
            $giacenza->save();

            return MagazzinoMovimento::create([
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => null,
                'tipo' => 'reso',
                'quantita' => $data['quantita'],
                'giacenza_dopo' => $giacenza->quantita,
                'lotto' => $data['lotto'] ?? null,
                'commessa' => $data['commessa'] ?? null,
                'operatore_id' => $data['operatore_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    /**
     * Rettifica inventariale — con validazione no negativi.
     */
    public static function rettifica(int $giacenzaId, float $nuovaQta, ?int $operatoreId = null, ?string $note = null): MagazzinoMovimento
    {
        if ($nuovaQta < 0) {
            throw new \RuntimeException('La quantità non può essere negativa');
        }

        return DB::transaction(function () use ($giacenzaId, $nuovaQta, $operatoreId, $note) {
            $giacenza = MagazzinoGiacenza::lockForUpdate()->findOrFail($giacenzaId);
            $differenza = $nuovaQta - $giacenza->quantita;

            // Normalizza ubicazione_id: 0/"" → NULL (evita FK violation)
            $ubicazioneId = $giacenza->ubicazione_id;
            if (empty($ubicazioneId)) {
                $ubicazioneId = null;
                if ($giacenza->ubicazione_id !== null) {
                    $giacenza->ubicazione_id = null;
                }
            }

            $giacenza->quantita = $nuovaQta;
            $giacenza->save();

            return MagazzinoMovimento::create([
                'articolo_id' => $giacenza->articolo_id,
                'ubicazione_id' => $ubicazioneId,
                'tipo' => 'rettifica',
                'quantita' => $differenza,
                'giacenza_dopo' => $nuovaQta,
                'lotto' => $giacenza->lotto,
                'operatore_id' => $operatoreId,
                'note' => $note ?? 'Rettifica inventariale',
            ]);
        });
    }

    /**
     * Articoli sotto soglia minima.
     */
    public static function alertSottoSoglia(): array
    {
        $giacenzePerArticolo = MagazzinoGiacenza::selectRaw('articolo_id, SUM(quantita) as totale')
            ->groupBy('articolo_id')
            ->pluck('totale', 'articolo_id');

        $articoli = MagazzinoArticolo::where('attivo', true)
            ->where('soglia_minima', '>', 0)
            ->get();

        $alert = [];
        foreach ($articoli as $art) {
            $totale = (float) ($giacenzePerArticolo[$art->id] ?? 0);
            if ($totale < $art->soglia_minima) {
                $alert[] = [
                    'articolo' => $art,
                    'giacenza' => $totale,
                    'soglia' => $art->soglia_minima,
                    'mancanti' => $art->soglia_minima - $totale,
                ];
            }
        }

        return $alert;
    }

    /**
     * Cerca bancale da QR code. Log se etichetta trovata ma disattivata.
     */
    public static function lookupQr(string $qrCode): ?MagazzinoEtichetta
    {
        // Check se esiste ma disattivata
        $etichetta = MagazzinoEtichetta::with(['articolo', 'ubicazione', 'giacenza'])
            ->where('qr_code', $qrCode)
            ->first();

        if ($etichetta && !$etichetta->attiva) {
            \Log::info("QR scansionato ma etichetta disattivata: {$qrCode}");
            return null;
        }

        return $etichetta;
    }
}
