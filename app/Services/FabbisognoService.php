<?php

namespace App\Services;

use App\Models\Ordine;
use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoGiacenza;
use Illuminate\Support\Facades\DB;

class FabbisognoService
{
    /**
     * Calcola il fabbisogno carta per tutte le commesse con fase STAMPA non ancora avviata.
     *
     * Logica: prende ordini con cod_carta valorizzato e fase STAMPA in stato 0 o 1,
     * confronta qta_carta con la giacenza disponibile in magazzino.
     *
     * Ritorna array di fabbisogni raggruppati per cod_carta.
     */
    public static function calcolaFabbisogno(): array
    {
        // Ordini con carta da stampare (fase STAMPA non ancora avviata)
        $ordini = Ordine::whereNotNull('cod_carta')
            ->where('cod_carta', '!=', '')
            ->whereHas('fasi', function ($q) {
                $q->whereHas('faseCatalogo', function ($fc) {
                    $fc->whereHas('reparto', function ($r) {
                        $r->whereIn('nome', ['stampa offset', 'digitale']);
                    });
                })->whereIn('stato', [0, 1]); // non ancora avviata
            })
            ->with(['fasi' => function ($q) {
                $q->whereHas('faseCatalogo', function ($fc) {
                    $fc->whereHas('reparto', function ($r) {
                        $r->whereIn('nome', ['stampa offset', 'digitale']);
                    });
                })->whereIn('stato', [0, 1]);
            }])
            ->orderBy('data_prevista_consegna')
            ->get();

        // Raggruppa per cod_carta
        $perCarta = [];
        foreach ($ordini as $ordine) {
            $codCarta = trim($ordine->cod_carta);
            if (!$codCarta) continue;

            $qta = (int) ($ordine->qta_carta ?? $ordine->qta_richiesta ?? 0);

            if (!isset($perCarta[$codCarta])) {
                $perCarta[$codCarta] = [
                    'cod_carta' => $codCarta,
                    'descrizione_carta' => $ordine->carta ?? $codCarta,
                    'fabbisogno_totale' => 0,
                    'giacenza' => 0,
                    'deficit' => 0,
                    'articolo_magazzino' => null,
                    'commesse' => [],
                ];
            }

            $perCarta[$codCarta]['fabbisogno_totale'] += $qta;
            $perCarta[$codCarta]['commesse'][] = [
                'commessa' => $ordine->commessa,
                'cliente' => $ordine->cliente_nome,
                'descrizione' => $ordine->descrizione,
                'qta_carta' => $qta,
                'um' => $ordine->UM_carta ?? 'fg',
                'data_consegna' => $ordine->data_prevista_consegna,
            ];
        }

        // Confronta con giacenza magazzino
        foreach ($perCarta as $codCarta => &$item) {
            // Cerca articolo per codice esatto o parziale
            $articolo = MagazzinoArticolo::where('codice', $codCarta)->first();
            if (!$articolo) {
                // Prova match parziale (il cod_carta Onda potrebbe essere diverso dal codice magazzino)
                $articolo = MagazzinoArticolo::where('codice', 'LIKE', "%{$codCarta}%")->first();
            }

            if ($articolo) {
                $item['articolo_magazzino'] = $articolo;
                $item['giacenza'] = MagazzinoGiacenza::where('articolo_id', $articolo->id)->sum('quantita');
            }

            $item['deficit'] = $item['fabbisogno_totale'] - $item['giacenza'];
        }

        // Ordina: prima quelli con deficit (mancanti), poi per cod_carta
        uasort($perCarta, function ($a, $b) {
            if ($a['deficit'] > 0 && $b['deficit'] <= 0) return -1;
            if ($a['deficit'] <= 0 && $b['deficit'] > 0) return 1;
            return $b['deficit'] <=> $a['deficit'];
        });

        return array_values($perCarta);
    }

    /**
     * Genera la lista ordini di acquisto raggruppata per fornitore.
     * Solo per articoli con deficit > 0 (fabbisogno > giacenza).
     */
    public static function generaOrdiniAcquisto(): array
    {
        $fabbisogno = self::calcolaFabbisogno();

        $perFornitore = [];
        foreach ($fabbisogno as $item) {
            if ($item['deficit'] <= 0) continue; // carta sufficiente

            $fornitore = $item['articolo_magazzino']->fornitore ?? 'Fornitore non assegnato';

            if (!isset($perFornitore[$fornitore])) {
                $perFornitore[$fornitore] = [
                    'fornitore' => $fornitore,
                    'articoli' => [],
                    'totale_articoli' => 0,
                ];
            }

            $perFornitore[$fornitore]['articoli'][] = [
                'cod_carta' => $item['cod_carta'],
                'descrizione' => $item['descrizione_carta'],
                'fabbisogno' => $item['fabbisogno_totale'],
                'giacenza' => $item['giacenza'],
                'da_ordinare' => $item['deficit'],
                'commesse' => collect($item['commesse'])->pluck('commessa')->implode(', '),
            ];
            $perFornitore[$fornitore]['totale_articoli']++;
        }

        return array_values($perFornitore);
    }

    /**
     * Verifica se una commessa ha carta disponibile per la fase STAMPA.
     * Usato per il blocco/avviso prelievo.
     */
    public static function verificaDisponibilita(string $commessa): array
    {
        $ordini = Ordine::where('commessa', $commessa)
            ->whereNotNull('cod_carta')
            ->where('cod_carta', '!=', '')
            ->get();

        $risultato = [
            'disponibile' => true,
            'dettagli' => [],
        ];

        foreach ($ordini as $ordine) {
            $codCarta = trim($ordine->cod_carta);
            $qtaNecessaria = (int) ($ordine->qta_carta ?? $ordine->qta_richiesta ?? 0);

            $articolo = MagazzinoArticolo::where('codice', $codCarta)->first();
            $giacenza = 0;
            if ($articolo) {
                $giacenza = MagazzinoGiacenza::where('articolo_id', $articolo->id)->sum('quantita');
            }

            $sufficiente = $giacenza >= $qtaNecessaria;
            if (!$sufficiente) $risultato['disponibile'] = false;

            $risultato['dettagli'][] = [
                'cod_carta' => $codCarta,
                'descrizione' => $ordine->carta ?? $codCarta,
                'necessaria' => $qtaNecessaria,
                'disponibile_mag' => $giacenza,
                'sufficiente' => $sufficiente,
            ];
        }

        return $risultato;
    }
}
