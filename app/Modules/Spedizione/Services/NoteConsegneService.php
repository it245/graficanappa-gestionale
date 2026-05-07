<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Services;

use App\Models\NotaSpedizione;
use App\Models\Operatore;
use Carbon\Carbon;

/**
 * Servizio per le "Note Consegne" condivise tra owner e reparto spedizione.
 *
 * Wrappa il modello NotaSpedizione (tabella `note_spedizione`) senza modificarlo.
 * Una nota per data: se manca quella odierna, esponiamo l'ultima disponibile.
 */
final class NoteConsegneService
{
    /**
     * Contenuto della nota per la data indicata (default oggi).
     */
    public function notaPerData(?Carbon $data = null): ?string
    {
        $data ??= Carbon::today();

        $nota = NotaSpedizione::query()
            ->whereDate('data', $data->toDateString())
            ->first();

        return $nota?->contenuto;
    }

    /**
     * Salva (upsert) la nota di oggi. L'autore è incluso nel contenuto come prefisso
     * — la tabella attuale espone solo `data` e `contenuto`, niente colonna autore.
     */
    public function salvaNota(string $contenuto, Operatore $autore): NotaSpedizione
    {
        $contenuto = trim($contenuto);
        $oggi = Carbon::today();

        $nomeAutore = $autore->getAttribute('nome') ?? $autore->getKey();
        $contenutoFirmato = sprintf('[%s] %s', $nomeAutore, $contenuto);

        return NotaSpedizione::updateOrCreate(
            ['data' => $oggi->toDateString()],
            ['contenuto' => $contenutoFirmato],
        );
    }

    /**
     * Restituisce l'ultima nota disponibile (per fallback quando oggi è vuota).
     * Formato: ['data' => Carbon, 'contenuto' => string] oppure null.
     *
     * @return array{data: Carbon, contenuto: string}|null
     */
    public function notaUltimaDisponibile(): ?array
    {
        $nota = NotaSpedizione::query()
            ->whereNotNull('contenuto')
            ->where('contenuto', '!=', '')
            ->orderByDesc('data')
            ->first();

        if ($nota === null) {
            return null;
        }

        $data = $nota->data instanceof Carbon
            ? $nota->data
            : Carbon::parse((string) $nota->data);

        return [
            'data' => $data,
            'contenuto' => (string) $nota->contenuto,
        ];
    }
}
