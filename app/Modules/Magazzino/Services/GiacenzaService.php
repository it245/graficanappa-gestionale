<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Services;

use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoGiacenza;
use App\Modules\Magazzino\Enums\TipoMovimento;
use App\Modules\Magazzino\Events\SottoSogliaEvento;
use App\Modules\Magazzino\Rules\SogliaSottoMinimoRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Servizio dominio "Giacenza".
 *
 * Responsabilità:
 *  - Lettura giacenza corrente per cod_art
 *  - Aggiornamento atomico (delta) con dispatch evento sotto-soglia
 *  - Lista articoli sotto soglia minima
 *
 * Non gestisce la persistenza del movimento (lo fa MovimentoService),
 * ma può essere invocato DA quel servizio dentro la stessa transazione.
 *
 * @example
 *  $giacenza = app(GiacenzaService::class)->giacenzaCorrente('02W.ALASKA.GC1.300.003');
 */
final class GiacenzaService
{
    public function __construct(
        private readonly SogliaSottoMinimoRule $sogliaRule,
    ) {
    }

    /**
     * Somma delle giacenze su tutte le ubicazioni/lotti per il cod_art.
     */
    public function giacenzaCorrente(string $codArt): float
    {
        $articolo = MagazzinoArticolo::query()->where('codice', $codArt)->first();
        if ($articolo === null) {
            return 0.0;
        }
        return (float) $articolo->giacenze()->sum('quantita');
    }

    /**
     * Applica un delta (positivo o negativo) alla giacenza dell'articolo.
     * Se la giacenza scende sotto soglia, dispatcha SottoSogliaEvento.
     *
     * Nota: opera su riga giacenza "principale" (ubicazione_id NULL, lotto NULL).
     * Per gestione lotti/ubicazioni multiple usare MovimentoService->registraMovimento.
     *
     * @throws RuntimeException se articolo inesistente o delta porterebbe sotto zero.
     */
    public function aggiornaGiacenza(string $codArt, float $delta, TipoMovimento $tipo, string $causale): void
    {
        DB::transaction(function () use ($codArt, $delta, $tipo, $causale): void {
            $articolo = MagazzinoArticolo::query()
                ->where('codice', $codArt)
                ->lockForUpdate()
                ->first();

            if ($articolo === null) {
                throw new RuntimeException("Articolo {$codArt} non trovato.");
            }

            /** @var MagazzinoGiacenza $riga */
            $riga = MagazzinoGiacenza::query()
                ->where('articolo_id', $articolo->id)
                ->whereNull('ubicazione_id')
                ->whereNull('lotto')
                ->lockForUpdate()
                ->firstOrCreate(
                    ['articolo_id' => $articolo->id, 'ubicazione_id' => null, 'lotto' => null],
                    ['quantita' => 0],
                );

            $nuova = (int) $riga->quantita + (int) round($delta);
            if ($nuova < 0) {
                throw new RuntimeException(
                    "Giacenza negativa non ammessa per {$codArt} (corrente {$riga->quantita}, delta {$delta}, causale {$causale})."
                );
            }

            $riga->quantita = $nuova;
            if ($tipo === TipoMovimento::Carico || $tipo === TipoMovimento::Reso) {
                $riga->data_ultimo_carico = now()->toDateString();
            } elseif ($tipo === TipoMovimento::Scarico) {
                $riga->data_ultimo_scarico = now()->toDateString();
            }
            $riga->save();

            // Refresh articolo per check soglia con dati appena scritti
            $articolo->refresh();
            if ($this->sogliaRule->check($articolo)) {
                event(new SottoSogliaEvento(
                    articolo: $articolo,
                    giacenzaCorrente: (int) $articolo->giacenze()->sum('quantita'),
                    sogliaMinima: (int) $articolo->soglia_minima,
                ));
            }
        });
    }

    /**
     * Scarica giacenza (scarico produzione). Wrapper convenience su aggiornaGiacenza.
     * Lock-safe: aggiornaGiacenza usa lockForUpdate dentro DB::transaction.
     */
    public function scarica(string $codArt, float $qta, string $causale): void
    {
        $this->aggiornaGiacenza($codArt, -abs($qta), TipoMovimento::Scarico, $causale);
    }

    /**
     * Carica giacenza (carico fornitore). Wrapper convenience su aggiornaGiacenza.
     */
    public function carica(string $codArt, float $qta, string $causale): void
    {
        $this->aggiornaGiacenza($codArt, abs($qta), TipoMovimento::Carico, $causale);
    }

    /**
     * Tutti gli articoli attualmente sotto la soglia configurata.
     *
     * @return Collection<int,MagazzinoArticolo>
     */
    public function articoliSottoSoglia(): Collection
    {
        return MagazzinoArticolo::query()
            ->where('attivo', true)
            ->where('soglia_minima', '>', 0)
            ->with('giacenze')
            ->get()
            ->filter(fn (MagazzinoArticolo $a): bool => $this->sogliaRule->check($a))
            ->values();
    }
}
