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

            $datiCommesse[] = [
                'commessa'      => $row->commessa,
                'etichetta'     => $row->etichetta,
                'cliente'       => $info->cliente_nome ?? '-',
                'descrizione'   => $info->descrizione ?? '-',
                'voci'          => $voci,
                'per_categoria' => $perCategoria,
                'totale'        => $totale,
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

        return view('owner.costi.analisi_custom_show', compact('analisi', 'datiCommesse', 'totaleGenerale', 'categorieTot'));
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
}
