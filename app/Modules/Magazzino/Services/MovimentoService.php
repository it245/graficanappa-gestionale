<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Services;

use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoMovimento;
use App\Modules\Magazzino\Enums\TipoMovimento;
use App\Modules\Magazzino\Rules\MovimentoValiditaRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Servizio dominio "Movimento Magazzino".
 *
 * Orchestratore unico per la creazione/annullamento dei movimenti:
 *  - valida tramite MovimentoValiditaRule
 *  - aggiorna la giacenza tramite GiacenzaService (stessa transazione)
 *  - persiste il record `magazzino_movimenti` con `giacenza_dopo`
 *
 * @example registra scarico produzione
 *  app(MovimentoService::class)->registraMovimento(
 *      codArt: '02W.ALASKA.GC1.300.003',
 *      tipo: TipoMovimento::Scarico,
 *      quantita: 1200,
 *      causale: 'PRODUZIONE',
 *      meta: ['commessa' => '66575', 'fase' => 'STAMPA XL', 'operatore_id' => 17],
 *  );
 */
final class MovimentoService
{
    public function __construct(
        private readonly GiacenzaService $giacenze,
        private readonly MovimentoValiditaRule $validita,
    ) {
    }

    /**
     * Registra un movimento e aggiorna la giacenza.
     *
     * @param array{
     *   commessa?: ?string,
     *   fase?: ?string,
     *   operatore_id?: ?int,
     *   ubicazione_id?: ?int,
     *   lotto?: ?string,
     *   fornitore?: ?string,
     *   note?: ?string,
     *   foto_bolla?: ?string,
     *   ocr_raw?: ?string
     * } $meta
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function registraMovimento(
        string $codArt,
        TipoMovimento $tipo,
        float $quantita,
        string $causale,
        array $meta = [],
    ): MagazzinoMovimento {
        $articolo = MagazzinoArticolo::query()->where('codice', $codArt)->first();

        $errori = $this->validita->validate($tipo, $quantita, $articolo);
        if ($errori !== []) {
            throw ValidationException::withMessages($errori);
        }
        /** @var MagazzinoArticolo $articolo (verificato sopra) */

        return DB::transaction(function () use ($articolo, $tipo, $quantita, $causale, $meta): MagazzinoMovimento {
            $delta = $tipo->aumentaGiacenza() ? abs($quantita) : -abs($quantita);
            if ($tipo === TipoMovimento::Rettifica) {
                $delta = $quantita; // segno significativo
            }

            $this->giacenze->aggiornaGiacenza($articolo->codice, $delta, $tipo, $causale);

            $giacenzaDopo = (int) $articolo->giacenze()->sum('quantita');

            return MagazzinoMovimento::query()->create([
                'articolo_id'    => $articolo->id,
                'ubicazione_id'  => $meta['ubicazione_id'] ?? null,
                'tipo'           => $tipo->value,
                'quantita'       => (int) round($delta),
                'giacenza_dopo'  => $giacenzaDopo,
                'lotto'          => $meta['lotto'] ?? null,
                'fornitore'      => $meta['fornitore'] ?? null,
                'commessa'       => $meta['commessa'] ?? null,
                'fase'           => $meta['fase'] ?? null,
                'operatore_id'   => $meta['operatore_id'] ?? null,
                'note'           => trim($causale . ($meta['note'] ?? '' ? ' — ' . $meta['note'] : '')),
                'foto_bolla'     => $meta['foto_bolla'] ?? null,
                'ocr_raw'        => $meta['ocr_raw'] ?? null,
            ]);
        });
    }

    /**
     * Annulla un movimento generandone uno opposto + nota di motivo.
     * Non cancella la riga originale (audit trail).
     *
     * @return bool true se annullato, false se già annullato/non trovato.
     */
    public function annullaMovimento(int $id, string $motivo): bool
    {
        return DB::transaction(function () use ($id, $motivo): bool {
            $orig = MagazzinoMovimento::query()->lockForUpdate()->find($id);
            if ($orig === null) {
                return false;
            }
            $articolo = MagazzinoArticolo::query()->find($orig->articolo_id);
            if ($articolo === null) {
                return false;
            }

            // Tipo inverso
            $tipoInverso = match ($orig->tipo) {
                'carico', 'reso'      => TipoMovimento::Scarico,
                'scarico'             => TipoMovimento::Carico,
                'rettifica'           => TipoMovimento::Rettifica,
                default               => TipoMovimento::Rettifica,
            };

            $this->giacenze->aggiornaGiacenza(
                $articolo->codice,
                -1.0 * (float) $orig->quantita,
                $tipoInverso,
                "ANNULLO #{$id}: {$motivo}",
            );

            MagazzinoMovimento::query()->create([
                'articolo_id'    => $articolo->id,
                'ubicazione_id'  => $orig->ubicazione_id,
                'tipo'           => $tipoInverso->value,
                'quantita'       => -1 * (int) $orig->quantita,
                'giacenza_dopo'  => (int) $articolo->giacenze()->sum('quantita'),
                'lotto'          => $orig->lotto,
                'commessa'       => $orig->commessa,
                'fase'           => $orig->fase,
                'note'           => "ANNULLO mov #{$id}: {$motivo}",
            ]);

            return true;
        });
    }

    /**
     * Storico movimenti per articolo, ordine desc, finestra giorni.
     *
     * @return Collection<int,MagazzinoMovimento>
     */
    public function storicoMovimenti(string $codArt, int $giorni = 90): Collection
    {
        $articolo = MagazzinoArticolo::query()->where('codice', $codArt)->first();
        if ($articolo === null) {
            return collect();
        }

        return MagazzinoMovimento::query()
            ->where('articolo_id', $articolo->id)
            ->where('created_at', '>=', now()->subDays($giorni))
            ->orderByDesc('created_at')
            ->get();
    }
}
