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

            // Trova o crea giacenza per articolo+ubicazione+lotto
            $giacenza = MagazzinoGiacenza::firstOrCreate(
                [
                    'articolo_id' => $data['articolo_id'],
                    'ubicazione_id' => $data['ubicazione_id'] ?? null,
                    'lotto' => $data['lotto'] ?? null,
                ],
                ['quantita' => 0]
            );

            $giacenza->quantita += $data['quantita'];
            $giacenza->data_ultimo_carico = now()->toDateString();
            $giacenza->save();

            // Registra movimento
            $movimento = MagazzinoMovimento::create([
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => $data['ubicazione_id'] ?? null,
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
                'ubicazione_id' => $data['ubicazione_id'] ?? null,
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
     * Lo scarico avviene SOLO per fase STAMPA.
     */
    public static function registraScarico(array $data): MagazzinoMovimento
    {
        return DB::transaction(function () use ($data) {
            $query = MagazzinoGiacenza::where('articolo_id', $data['articolo_id']);
            empty($data['ubicazione_id']) ? $query->whereNull('ubicazione_id') : $query->where('ubicazione_id', $data['ubicazione_id']);
            empty($data['lotto']) ? $query->whereNull('lotto') : $query->where('lotto', $data['lotto']);
            $giacenza = $query->firstOrFail();

            if ($giacenza->quantita < $data['quantita']) {
                throw new \RuntimeException("Giacenza insufficiente: disponibili {$giacenza->quantita}, richiesti {$data['quantita']}");
            }

            $giacenza->quantita -= $data['quantita'];
            $giacenza->data_ultimo_scarico = now()->toDateString();
            $giacenza->save();

            return MagazzinoMovimento::create([
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => $data['ubicazione_id'] ?? null,
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
            $giacenza = MagazzinoGiacenza::firstOrCreate(
                [
                    'articolo_id' => $data['articolo_id'],
                    'ubicazione_id' => $data['ubicazione_id'] ?? null,
                    'lotto' => $data['lotto'] ?? null,
                ],
                ['quantita' => 0]
            );

            $giacenza->quantita += $data['quantita'];
            $giacenza->data_ultimo_carico = now()->toDateString();
            $giacenza->save();

            return MagazzinoMovimento::create([
                'articolo_id' => $data['articolo_id'],
                'ubicazione_id' => $data['ubicazione_id'] ?? null,
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
     * Rettifica inventariale.
     */
    public static function rettifica(int $giacenzaId, int $nuovaQta, ?int $operatoreId = null, ?string $note = null): MagazzinoMovimento
    {
        return DB::transaction(function () use ($giacenzaId, $nuovaQta, $operatoreId, $note) {
            $giacenza = MagazzinoGiacenza::findOrFail($giacenzaId);
            $differenza = $nuovaQta - $giacenza->quantita;

            $giacenza->quantita = $nuovaQta;
            $giacenza->save();

            return MagazzinoMovimento::create([
                'articolo_id' => $giacenza->articolo_id,
                'ubicazione_id' => $giacenza->ubicazione_id,
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
        // Una sola query: somma giacenze per articolo e confronta con soglia
        $giacenzePerArticolo = MagazzinoGiacenza::selectRaw('articolo_id, SUM(quantita) as totale')
            ->groupBy('articolo_id')
            ->pluck('totale', 'articolo_id');

        $articoli = MagazzinoArticolo::where('attivo', true)
            ->where('soglia_minima', '>', 0)
            ->get();

        $alert = [];
        foreach ($articoli as $art) {
            $totale = (int) ($giacenzePerArticolo[$art->id] ?? 0);
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
     * Cerca bancale da QR code.
     */
    public static function lookupQr(string $qrCode): ?MagazzinoEtichetta
    {
        return MagazzinoEtichetta::with(['articolo', 'ubicazione', 'giacenza'])
            ->where('qr_code', $qrCode)
            ->where('attiva', true)
            ->first();
    }
}
