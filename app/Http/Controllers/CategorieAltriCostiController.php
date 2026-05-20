<?php

namespace App\Http\Controllers;

use App\Models\CategoriaAltroCosto;
use Illuminate\Http\Request;

class CategorieAltriCostiController extends Controller
{
    public function index()
    {
        $categorie = CategoriaAltroCosto::orderBy('ordine')->orderBy('nome')->get();
        return view('owner.costi.categorie_index', compact('categorie'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'        => 'required|string|max:100|unique:categorie_altri_costi,nome',
            'descrizione' => 'nullable|string|max:200',
            'ordine'      => 'nullable|integer',
        ]);
        CategoriaAltroCosto::create($data + ['attiva' => true]);
        return redirect()->route('owner.costi.categorie.index');
    }

    public function update(Request $request, int $id)
    {
        $cat = CategoriaAltroCosto::findOrFail($id);
        $data = $request->validate([
            'nome'        => 'required|string|max:100|unique:categorie_altri_costi,nome,' . $id,
            'descrizione' => 'nullable|string|max:200',
            'ordine'      => 'nullable|integer',
            'attiva'      => 'nullable|boolean',
        ]);
        $cat->update($data + ['attiva' => $request->boolean('attiva')]);
        return redirect()->route('owner.costi.categorie.index');
    }

    public function destroy(int $id)
    {
        CategoriaAltroCosto::findOrFail($id)->delete();
        return redirect()->route('owner.costi.categorie.index');
    }
}
