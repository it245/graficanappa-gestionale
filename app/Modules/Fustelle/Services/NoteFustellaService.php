<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Services;

use App\Models\OrdineFase;
use App\Modules\Fustelle\Events\NotaFustellaAggiunta;
use App\Modules\Fustelle\ValueObjects\NotaFustella;

/**
 * Gestione note fustella per fase di lavorazione.
 *
 * Le note vengono APPESE al campo `ordine_fasi.note` esistente (non
 * sovrascritte) per preservare lo storico. Ogni nota è prefissata da
 * "[Autore - dd/mm HH:MM]" come stabilito nella sessione 20 marzo 2026.
 */
final class NoteFustellaService
{
    /**
     * Appende una nuova nota al campo note della fase.
     *
     * @return NotaFustella la nota costruita (utile per UI/test).
     */
    public function aggiungi(OrdineFase $fase, string $autore, string $testo): NotaFustella
    {
        $nota = NotaFustella::ora($autore, $testo);

        $precedente = (string) ($fase->note ?? '');
        $nuovo = $precedente !== ''
            ? rtrim($precedente) . "\n" . $nota->format()
            : $nota->format();

        $fase->note = $nuovo;
        $fase->save();

        NotaFustellaAggiunta::dispatch($fase, $nota);

        return $nota;
    }

    /**
     * Estrae le note prefissate (formato canonico) dal campo `note`.
     *
     * Righe non riconosciute (testo libero pre-esistente) vengono ignorate
     * per non corrompere la lista; il campo `note` resta la fonte di verità.
     *
     * @return list<array{autore:string,timestamp:string,testo:string}>
     */
    public function elenco(OrdineFase $fase): array
    {
        $raw = (string) ($fase->note ?? '');
        if ($raw === '') {
            return [];
        }

        $out = [];
        // Pattern: [Autore - dd/mm HH:MM] testo
        $regex = '/^\[(?<autore>[^\]\-]+?)\s*-\s*(?<ts>\d{2}\/\d{2}\s+\d{2}:\d{2})\]\s*(?<testo>.*)$/';

        foreach (preg_split('/\r?\n/', $raw) ?: [] as $riga) {
            $riga = trim($riga);
            if ($riga === '') {
                continue;
            }
            if (preg_match($regex, $riga, $m) === 1) {
                $out[] = [
                    'autore' => trim($m['autore']),
                    'timestamp' => trim($m['ts']),
                    'testo' => trim($m['testo']),
                ];
            }
        }

        return $out;
    }
}
