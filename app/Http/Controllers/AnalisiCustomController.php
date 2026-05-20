<?php

namespace App\Http\Controllers;

use App\Models\AnalisiCustom;
use App\Models\AnalisiCustomCommessa;
use App\Services\CostoConsuntivoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisiCustomController extends Controller
{
    public function index()
    {
        $autore = session('admin_nome') ?? session('operatore_nome') ?? 'owner';
        $analisiList = AnalisiCustom::orderByDesc('ultimo_accesso')->orderByDesc('id')->paginate(30);
        return view('owner.costi.analisi_custom_index', compact('analisiList'));
    }

    public function create()
    {
        return view('owner.costi.analisi_custom_form', ['analisi' => new AnalisiCustom()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'        => 'required|string|max:200',
            'descrizione' => 'nullable|string|max:500',
        ]);
        $data['autore'] = session('admin_nome') ?? session('operatore_nome') ?? 'owner';
        $data['ultimo_accesso'] = now();
        $analisi = AnalisiCustom::create($data);
        return redirect()->route('owner.analisi.custom.show', $analisi->id);
    }

    public function show(int $id, CostoConsuntivoService $costoService)
    {
        $analisi = AnalisiCustom::with('commesse')->findOrFail($id);
        $analisi->update(['ultimo_accesso' => now()]);

        // Calcola costi per ogni commessa
        $datiCommesse = [];
        foreach ($analisi->commesse->sortBy('ordine') as $row) {
            $voci = $costoService->calcola($row->commessa);
            $totale = array_sum(array_column($voci, 'importo'));
            $perCategoria = [];
            foreach ($voci as $v) $perCategoria[$v['categoria']] = ($perCategoria[$v['categoria']] ?? 0) + $v['importo'];

            $info = DB::table('ordini')->where('commessa', $row->commessa)
                ->select('cliente_nome', DB::raw("GROUP_CONCAT(DISTINCT descrizione SEPARATOR ' · ') as descrizione"))
                ->groupBy('cliente_nome')->first();

            // Override locale: totale_override sostituisce il totale calcolato (per analisi)
            $totaleEffettivo = $totale;
            $hasOverride = false;
            if (is_array($row->override_voci) && isset($row->override_voci['totale_override'])) {
                $totaleEffettivo = (float) $row->override_voci['totale_override'];
                $hasOverride = true;
            }
            $datiCommesse[] = [
                'commessa'      => $row->commessa,
                'etichetta'     => $row->etichetta,
                'cliente'       => $info->cliente_nome ?? '-',
                'descrizione'   => $info->descrizione ?? '-',
                'voci'          => $voci,
                'per_categoria' => $perCategoria,
                'totale_calc'   => $totale,
                'totale'        => $totaleEffettivo,
                'override_attivo' => $hasOverride,
                'pivot_id'      => $row->id,
            ];
        }

        // Totali aggregati
        $totaleGenerale = array_sum(array_column($datiCommesse, 'totale'));
        $categorieTot = [];
        foreach ($datiCommesse as $c) {
            foreach ($c['per_categoria'] as $cat => $val) {
                $categorieTot[$cat] = ($categorieTot[$cat] ?? 0) + $val;
            }
        }
        arsort($categorieTot);

        // #10 Voci custom ad-hoc analisi
        $vociCustom = $analisi->opzioni_view['voci_custom'] ?? [];
        $totaleVociCustom = array_sum(array_column($vociCustom, 'importo'));
        $totaleGenerale += $totaleVociCustom;
        if ($totaleVociCustom != 0) {
            $categorieTot['voci_custom'] = ($categorieTot['voci_custom'] ?? 0) + $totaleVociCustom;
            arsort($categorieTot);
        }

        return view('owner.costi.analisi_custom_show', compact('analisi', 'datiCommesse', 'totaleGenerale', 'categorieTot', 'vociCustom'));
    }

    public function aggiungiCommessa(Request $request, int $id)
    {
        $data = $request->validate([
            'commessa'  => 'required|string|max:20',
            'etichetta' => 'nullable|string|max:100',
        ]);

        $analisi = AnalisiCustom::findOrFail($id);
        $existing = AnalisiCustomCommessa::where('analisi_id', $id)->where('commessa', $data['commessa'])->first();
        if (!$existing) {
            AnalisiCustomCommessa::create([
                'analisi_id' => $id,
                'commessa'   => $data['commessa'],
                'etichetta'  => $data['etichetta'] ?? null,
                'ordine'     => AnalisiCustomCommessa::where('analisi_id', $id)->max('ordine') + 1,
            ]);
        }
        return redirect()->route('owner.analisi.custom.show', $id);
    }

    public function aggiornaRiga(Request $request, int $id, int $pivotId)
    {
        $data = $request->validate([
            'etichetta'       => 'nullable|string|max:100',
            'totale_override' => 'nullable|numeric|min:0',
        ]);
        $row = AnalisiCustomCommessa::where('id', $pivotId)->where('analisi_id', $id)->firstOrFail();
        $override = $row->override_voci ?? [];
        if (array_key_exists('totale_override', $data) && $data['totale_override'] !== null && $data['totale_override'] !== '') {
            $override['totale_override'] = (float) $data['totale_override'];
        } else {
            unset($override['totale_override']);
        }
        $row->update([
            'etichetta'     => $data['etichetta'] ?? $row->etichetta,
            'override_voci' => $override,
        ]);
        return redirect()->route('owner.analisi.custom.show', $id);
    }

    public function rimuoviCommessa(int $id, int $pivotId)
    {
        AnalisiCustomCommessa::where('id', $pivotId)->where('analisi_id', $id)->delete();
        return redirect()->route('owner.analisi.custom.show', $id);
    }

    public function destroy(int $id)
    {
        AnalisiCustom::findOrFail($id)->delete();
        return redirect()->route('owner.analisi.custom.index');
    }

    /**
     * #11 Duplica analisi (copia con tutte le commesse).
     */
    public function duplica(int $id)
    {
        $orig = AnalisiCustom::with('commesse')->findOrFail($id);
        $copia = AnalisiCustom::create([
            'nome'        => $orig->nome . ' (copia)',
            'descrizione' => $orig->descrizione,
            'autore'      => session('admin_nome') ?? session('operatore_nome') ?? 'owner',
            'filtri'      => $orig->filtri,
            'opzioni_view' => $orig->opzioni_view,
            'ultimo_accesso' => now(),
        ]);
        foreach ($orig->commesse as $c) {
            AnalisiCustomCommessa::create([
                'analisi_id'    => $copia->id,
                'commessa'      => $c->commessa,
                'etichetta'     => $c->etichetta,
                'override_voci' => $c->override_voci,
                'ordine'        => $c->ordine,
            ]);
        }
        return redirect()->route('owner.analisi.custom.show', $copia->id)->with('success', 'Analisi duplicata');
    }

    /**
     * #10 Aggiungi voce manuale ad-hoc all'analisi (non a una commessa specifica).
     */
    public function aggiungiVoceCustom(Request $request, int $id)
    {
        $data = $request->validate([
            'descrizione' => 'required|string|max:200',
            'importo'     => 'required|numeric',
        ]);
        $analisi = AnalisiCustom::findOrFail($id);
        $opt = is_array($analisi->opzioni_view) ? $analisi->opzioni_view : [];
        $opt['voci_custom'] = $opt['voci_custom'] ?? [];
        $opt['voci_custom'][] = [
            'id'          => uniqid(),
            'descrizione' => $data['descrizione'],
            'importo'     => (float) $data['importo'],
            'autore'      => session('admin_nome') ?? session('operatore_nome') ?? 'owner',
            'data'        => now()->format('Y-m-d H:i'),
        ];
        $analisi->update(['opzioni_view' => $opt]);
        return redirect()->route('owner.analisi.custom.show', $id);
    }

    public function rimuoviVoceCustom(int $id, string $voceId)
    {
        $analisi = AnalisiCustom::findOrFail($id);
        $opt = is_array($analisi->opzioni_view) ? $analisi->opzioni_view : [];
        if (isset($opt['voci_custom'])) {
            $opt['voci_custom'] = array_values(array_filter($opt['voci_custom'], fn($v) => ($v['id'] ?? '') !== $voceId));
        }
        $analisi->update(['opzioni_view' => $opt]);
        return redirect()->route('owner.analisi.custom.show', $id);
    }

    public function pdf(int $id, CostoConsuntivoService $costoService)
    {
        $view = $this->show($id, $costoService);
        $data = $view->getData();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.analisi_custom', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream("Analisi_{$data['analisi']->nome}_" . now()->format('Ymd') . ".pdf");
    }

    public function excel(int $id, CostoConsuntivoService $costoService)
    {
        $view = $this->show($id, $costoService);
        $data = $view->getData();
        $rows = [['Commessa', 'Cliente', 'Descrizione', 'Etichetta', 'Costo totale €']];
        foreach ($data['datiCommesse'] as $c) {
            $rows[] = [
                $c['commessa'],
                $c['cliente'],
                $c['descrizione'],
                $c['etichetta'] ?? '',
                round($c['totale'], 2),
            ];
        }
        $rows[] = ['', '', '', 'TOTALE', round($data['totaleGenerale'], 2)];

        $filename = "Analisi_{$data['analisi']->nome}_" . now()->format('Ymd') . ".csv";
        return response()->streamDownload(function() use ($rows) {
            $h = fopen('php://output', 'w');
            fputs($h, "\xEF\xBB\xBF"); // BOM UTF-8 per Excel italiano
            foreach ($rows as $r) fputcsv($h, $r, ';');
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function searchCommesse(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);
        $risultati = DB::table('ordini')
            ->where('commessa', 'LIKE', "%{$q}%")
            ->orWhere('cliente_nome', 'LIKE', "%{$q}%")
            ->orWhere('descrizione', 'LIKE', "%{$q}%")
            ->select('commessa', DB::raw('MAX(cliente_nome) as cliente'), DB::raw("GROUP_CONCAT(DISTINCT descrizione SEPARATOR ' · ') as descrizione"))
            ->groupBy('commessa')
            ->orderBy('commessa', 'desc')
            ->limit(10)
            ->get();
        return response()->json($risultati);
    }
}
