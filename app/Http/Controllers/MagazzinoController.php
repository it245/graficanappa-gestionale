<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoGiacenza;
use App\Models\MagazzinoMovimento;
use App\Services\MagazzinoService;
use App\Services\FabbisognoService;

class MagazzinoController extends Controller
{
    /**
     * Dashboard magazzino: KPI, giacenze basse, ultimi movimenti.
     */
    public function dashboard(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $totArticoli = MagazzinoArticolo::where('attivo', true)->count();
        $totGiacenza = MagazzinoGiacenza::sum('quantita');
        $movOggi = MagazzinoMovimento::whereDate('created_at', today())->count();
        $alertSoglia = MagazzinoService::alertSottoSoglia();

        $ultimiMovimenti = MagazzinoMovimento::with(['articolo', 'operatore'])
            ->latest()
            ->limit(20)
            ->get();

        return view('magazzino.dashboard', [
            'operatore' => $operatore,
            'totArticoli' => $totArticoli,
            'totGiacenza' => $totGiacenza,
            'movOggi' => $movOggi,
            'alertSoglia' => $alertSoglia,
            'ultimiMovimenti' => $ultimiMovimenti,
        ]);
    }

    /**
     * CRUD Articoli (anagrafica carta).
     */
    public function articoli(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $query = MagazzinoArticolo::query();

        if ($request->filled('cerca')) {
            $cerca = str_replace(['%', '_'], ['\%', '\_'], $request->input('cerca'));
            $query->where(function ($q) use ($cerca) {
                $q->where('codice', 'LIKE', "%{$cerca}%")
                  ->orWhere('descrizione', 'LIKE', "%{$cerca}%")
                  ->orWhere('categoria', 'LIKE', "%{$cerca}%");
            });
        }

        $articoli = $query->orderBy('codice')->paginate(25);

        return view('magazzino.articoli', [
            'operatore' => $operatore,
            'articoli' => $articoli,
        ]);
    }

    /**
     * Salva nuovo articolo o aggiorna esistente.
     */
    public function storeArticolo(Request $request)
    {
        $request->validate([
            'codice' => 'required|string|max:255',
            'descrizione' => 'required|string|max:255',
        ]);

        MagazzinoArticolo::updateOrCreate(
            ['codice' => $request->input('codice')],
            $request->only(['codice', 'descrizione', 'categoria', 'formato', 'grammatura', 'spessore', 'um', 'soglia_minima', 'fornitore', 'certificazioni'])
        );

        return redirect()->route('magazzino.articoli', ['op_token' => $request->get('op_token')])
            ->with('success', 'Articolo salvato');
    }

    /**
     * Giacenze con filtri.
     */
    public function giacenze(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $query = MagazzinoGiacenza::with(['articolo']);

        if ($request->filled('categoria')) {
            $query->whereHas('articolo', fn($q) => $q->where('categoria', $request->categoria));
        }
        if ($request->filled('formato')) {
            $query->whereHas('articolo', fn($q) => $q->where('formato', $request->formato));
        }
        if ($request->filled('grammatura')) {
            $query->whereHas('articolo', fn($q) => $q->where('grammatura', $request->grammatura));
        }

        $giacenze = $query->orderBy('articolo_id')->paginate(30);

        $filtri = [
            'categorie' => MagazzinoArticolo::CATEGORIE,
            'formati' => MagazzinoArticolo::whereNotNull('formato')->distinct()->pluck('formato'),
            'grammature' => MagazzinoArticolo::whereNotNull('grammatura')->distinct()->pluck('grammatura'),
        ];

        return view('magazzino.giacenze', [
            'operatore' => $operatore,
            'giacenze' => $giacenze,
            'filtri' => $filtri,
        ]);
    }

    /**
     * Storico movimenti.
     */
    public function movimenti(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $query = MagazzinoMovimento::with(['articolo', 'operatore', 'ubicazione']);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('da')) {
            $query->whereDate('created_at', '>=', $request->da);
        }
        if ($request->filled('a')) {
            $query->whereDate('created_at', '<=', $request->a);
        }
        if ($request->filled('articolo_id')) {
            $query->where('articolo_id', $request->articolo_id);
        }

        $movimenti = $query->latest()->paginate(30);

        return view('magazzino.movimenti', [
            'operatore' => $operatore,
            'movimenti' => $movimenti,
        ]);
    }

    /**
     * Alert sotto soglia.
     */
    public function alertSoglia(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $alert = MagazzinoService::alertSottoSoglia();

        return view('magazzino.alert', [
            'operatore' => $operatore,
            'alert' => $alert,
        ]);
    }

    /**
     * Fabbisogno materiali: fabbisogno carta per commesse in attesa di stampa.
     */
    public function fabbisogno(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $fabbisogno = FabbisognoService::calcolaFabbisogno();

        $totFabbisogno = collect($fabbisogno)->sum('fabbisogno_totale');
        $totDisponibile = collect($fabbisogno)->where('deficit', '<=', 0)->count();
        $totMancante = collect($fabbisogno)->where('deficit', '>', 0)->count();

        return view('magazzino.fabbisogno', [
            'operatore' => $operatore,
            'fabbisogno' => $fabbisogno,
            'totFabbisogno' => $totFabbisogno,
            'totDisponibile' => $totDisponibile,
            'totMancante' => $totMancante,
        ]);
    }

    /**
     * Ordini di acquisto: lista carta da ordinare raggruppata per fornitore.
     */
    public function ordiniAcquisto(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $ordini = FabbisognoService::generaOrdiniAcquisto();

        return view('magazzino.ordini_acquisto', [
            'operatore' => $operatore,
            'ordini' => $ordini,
        ]);
    }
}
