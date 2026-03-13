<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PresenzeController extends Controller
{
    /**
     * API: chi è presente adesso?
     * Restituisce lista dipendenti con ultima timbratura di oggi.
     */
    public function presenti()
    {
        $oggi = Carbon::today()->format('Y-m-d');

        // Ultima timbratura di oggi per ogni matricola
        $ultime = DB::table('nettime_timbrature')
            ->select('matricola', DB::raw('MAX(data_ora) as ultima_timbratura'))
            ->whereDate('data_ora', $oggi)
            ->groupBy('matricola')
            ->get();

        $presenti = [];
        $usciti = [];

        foreach ($ultime as $u) {
            // Prendi il verso dell'ultima timbratura
            $ultima = DB::table('nettime_timbrature')
                ->where('matricola', $u->matricola)
                ->where('data_ora', $u->ultima_timbratura)
                ->first();

            // Anagrafica
            $anag = DB::table('nettime_anagrafica')
                ->where('matricola', $u->matricola)
                ->first();

            $nome = $anag ? "{$anag->cognome} {$anag->nome}" : "Matricola {$u->matricola}";

            $entry = [
                'matricola' => $u->matricola,
                'nome' => $nome,
                'ultima_timbratura' => Carbon::parse($u->ultima_timbratura)->format('H:i'),
                'verso' => $ultima->verso,
            ];

            // Prima timbratura di oggi (entrata)
            $prima = DB::table('nettime_timbrature')
                ->where('matricola', $u->matricola)
                ->whereDate('data_ora', $oggi)
                ->where('verso', 'E')
                ->orderBy('data_ora')
                ->first();

            if ($prima) {
                $entry['entrata'] = Carbon::parse($prima->data_ora)->format('H:i');
            }

            if ($ultima->verso === 'E') {
                $presenti[] = $entry;
            } else {
                $usciti[] = $entry;
            }
        }

        // Ordina per cognome
        usort($presenti, fn($a, $b) => strcmp($a['nome'], $b['nome']));
        usort($usciti, fn($a, $b) => strcmp($a['nome'], $b['nome']));

        return response()->json([
            'presenti' => $presenti,
            'usciti' => $usciti,
            'totale_presenti' => count($presenti),
            'totale_usciti' => count($usciti),
            'ultimo_sync' => now()->format('H:i:s'),
        ]);
    }
}
