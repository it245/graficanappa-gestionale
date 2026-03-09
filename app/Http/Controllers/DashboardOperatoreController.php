<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Helpers\DescrizioneParser;
use Carbon\Carbon;

class DashboardOperatoreController extends Controller
{
    public function index(Request $request)
    {
        // Operatore loggato (da token per-tab o fallback sessione)
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        // Recupera gli ID dei reparti dell'operatore (molti-a-molti)
        $reparti = $operatore->reparti->pluck('id')->toArray();

        // Sicurezza: se nessun reparto
        if (empty($reparti)) {
            $reparti = [];
        }

        // Mappa fasi → ore avviamento e copieh (da config centralizzato)
        $fasiInfo = config('fasi_ore', []);

        // Recupera le fasi visibili per i reparti dell'operatore
        $fasiVisibili = OrdineFase::where('stato', '<', 3)
            ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
            ->whereHas('faseCatalogo', function ($q) use ($reparti) {
                $q->whereIn('reparto_id', $reparti);
            })
            ->with(['ordine', 'faseCatalogo.reparto', 'operatori'])
            ->get()
            ->map(function ($fase) use ($fasiInfo) {
                $qta_carta = $fase->ordine->qta_carta ?? 0;
                $infoFase = $fasiInfo[$fase->fase] ?? ['avviamento' => 0, 'copieh' => 0];
                $copieh = $infoFase['copieh'] ?: 1;

                // ore = avviamento + qtaCarta / copieh (pezzi da fare / pezzi all'ora)
                $fase->ore = round($infoFase['avviamento'] + ($qta_carta / $copieh), 2);

                // giorni rimasti = dataPrevConsegna - OGGI (negativo se scaduta)
                $giorni_rimasti = 0;
                if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
                    $oggi = \Carbon\Carbon::today();
                    $consegna = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
                    $giorni_rimasti = ($consegna->timestamp - $oggi->timestamp) / 86400;
                }

                // Se priorità manuale (impostata da owner/Excel), non ricalcolare
                $currentPriorita = $fase->priorita ?? 0;
                if ($fase->priorita_manuale || ($currentPriorita <= -901 && $currentPriorita >= -999)) {
                    // Mantieni la priorità dal DB
                } else {
                    // Ordine fase dalla config
                    $fasePriorita = config('fasi_priorita')[$fase->fase] ?? 500;

                    // Priorità = giorni rimasti - ore/24 + ordineFase/10000
                    $fase->priorita = round($giorni_rimasti - ($fase->ore / 24) + ($fasePriorita / 10000), 2);
                }

                // Colori e Fustella dalla descrizione
                $desc = $fase->ordine->descrizione ?? '';
                $cliente = $fase->ordine->cliente_nome ?? '';
                $repNome = optional($fase->faseCatalogo)->reparto->nome ?? '';
                $fase->colori = DescrizioneParser::parseColori($desc, $cliente, $repNome);
                $fase->fustella_codice = DescrizioneParser::parseFustella($desc, $cliente);

                // Fornitore esterno: estrai "Inviato a: XXX" dalla note
                $fase->fornitore_esterno = preg_match('/Inviato a:\s*(.+)/i', $fase->note ?? '', $m) ? trim($m[1]) : null;

                // Pulisci "Inviato a: ..." dalla note visualizzata
                $fase->note_pulita = preg_replace('/,?\s*Inviato a:\s*.+/i', '', $fase->note ?? '');
                $fase->note_pulita = trim($fase->note_pulita, ", \t\n\r") ?: null;

                return $fase;
            })
            ->sortBy('priorita');

        // Flag per colonne condizionali
        $nomiReparti = $operatore->reparti->pluck('nome')->map(fn($n) => strtolower($n))->toArray();
        $showColori = !empty(array_intersect($nomiReparti, ['stampa offset']));
        $showFustella = empty(array_intersect($nomiReparti, ['piegaincolla']));
        $showEsterno = !empty(array_intersect($nomiReparti, ['spedizione']));

        // Raggruppa fasi per reparto (per operatori multi-reparto)
        $repartiOperatore = $operatore->reparti->sortBy('nome');
        $fasiPerReparto = [];
        if ($repartiOperatore->count() > 1) {
            foreach ($repartiOperatore as $rep) {
                $fasiPerReparto[$rep->id] = [
                    'nome' => ucfirst($rep->nome),
                    'fasi' => $fasiVisibili->filter(function ($fase) use ($rep) {
                        return optional($fase->faseCatalogo)->reparto_id == $rep->id;
                    }),
                ];
            }
        }

        // Storico fasi terminate (ultimi 30 giorni)
        $fasiTerminate = OrdineFase::whereIn('stato', [3, 4])
            ->whereHas('faseCatalogo', function ($q) use ($reparti) {
                $q->whereIn('reparto_id', $reparti);
            })
            ->whereDate('data_fine', '>=', Carbon::today()->subDays(30))
            ->with(['ordine', 'faseCatalogo.reparto'])
            ->orderByDesc('data_fine')
            ->get();

        return view('operatore.dashboard', compact('fasiVisibili', 'operatore', 'fasiPerReparto', 'showColori', 'showFustella', 'showEsterno', 'fasiTerminate'));
    }
}