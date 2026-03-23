<?php

namespace App\Http\Controllers;

use App\Models\CrmContatto;
use App\Models\CrmInterazione;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $query = CrmContatto::query();

        // Filtri
        if ($request->filled('cerca')) {
            $term = '%' . $request->cerca . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nome', 'like', $term)
                  ->orWhere('cognome', 'like', $term)
                  ->orWhere('azienda', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('priorita')) {
            $query->where('priorita', $request->priorita);
        }

        // Tab: tutti, da_contattare, scaduti
        $tab = $request->get('tab', 'tutti');

        if ($tab === 'scaduti') {
            $query->where('prossimo_followup', '<', now()->toDateString());
        } elseif ($tab === 'da_contattare') {
            $query->where('prossimo_followup', '<=', now()->addDays(7)->toDateString());
        }

        $ordinamento = $request->get('ordina', 'prossimo_followup');
        $direzione = $request->get('dir', 'asc');
        $query->orderByRaw("CASE WHEN prossimo_followup IS NULL THEN 1 ELSE 0 END")
              ->orderBy($ordinamento, $direzione);

        $contatti = $query->paginate(25)->appends($request->query());

        // Statistiche per la sidebar
        $totaleContatti = CrmContatto::count();
        $followupScaduti = CrmContatto::where('prossimo_followup', '<', now()->toDateString())->count();
        $followupSettimana = CrmContatto::where('prossimo_followup', '<=', now()->addDays(7)->toDateString())
            ->where('prossimo_followup', '>=', now()->toDateString())->count();

        return view('crm.index', compact(
            'contatti', 'tab', 'totaleContatti', 'followupScaduti', 'followupSettimana'
        ));
    }

    public function salva(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cognome' => 'nullable|string|max:255',
            'azienda' => 'nullable|string|max:255',
            'ruolo' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'categoria' => 'required|in:cliente,fornitore,partner,altro',
            'priorita' => 'required|in:alta,media,bassa',
            'frequenza_followup_giorni' => 'required|integer|min:1|max:365',
            'note' => 'nullable|string',
        ]);

        $contatto = CrmContatto::create($request->only([
            'nome', 'cognome', 'azienda', 'ruolo', 'email', 'telefono',
            'categoria', 'priorita', 'frequenza_followup_giorni', 'note',
        ]));

        return redirect()->route('crm.index')->with('success', 'Contatto aggiunto: ' . $contatto->nomeCompleto());
    }

    public function dettaglio(CrmContatto $contatto)
    {
        $contatto->load('interazioni');
        return view('crm.dettaglio', compact('contatto'));
    }

    public function aggiorna(Request $request, CrmContatto $contatto)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cognome' => 'nullable|string|max:255',
            'azienda' => 'nullable|string|max:255',
            'ruolo' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'categoria' => 'required|in:cliente,fornitore,partner,altro',
            'priorita' => 'required|in:alta,media,bassa',
            'frequenza_followup_giorni' => 'required|integer|min:1|max:365',
            'note' => 'nullable|string',
        ]);

        $contatto->update($request->only([
            'nome', 'cognome', 'azienda', 'ruolo', 'email', 'telefono',
            'categoria', 'priorita', 'frequenza_followup_giorni', 'note',
        ]));

        $contatto->ricalcolaProssimoFollowup();
        $contatto->save();

        return redirect()->route('crm.dettaglio', $contatto)->with('success', 'Contatto aggiornato.');
    }

    public function elimina(CrmContatto $contatto)
    {
        $nome = $contatto->nomeCompleto();
        $contatto->delete();

        return redirect()->route('crm.index')->with('success', "Contatto \"$nome\" eliminato.");
    }

    public function salvaInterazione(Request $request, CrmContatto $contatto)
    {
        $request->validate([
            'tipo' => 'required|in:telefonata,email,incontro,messaggio,altro',
            'note' => 'nullable|string',
            'data_interazione' => 'required|date',
        ]);

        $contatto->interazioni()->create([
            'tipo' => $request->tipo,
            'note' => $request->note,
            'data_interazione' => $request->data_interazione,
        ]);

        // Aggiorna ultimo_contatto e ricalcola prossimo followup
        $dataInterazione = Carbon::parse($request->data_interazione);
        if (!$contatto->ultimo_contatto || $dataInterazione->gte($contatto->ultimo_contatto)) {
            $contatto->ultimo_contatto = $dataInterazione->toDateString();
            $contatto->ricalcolaProssimoFollowup();
            $contatto->save();
        }

        return redirect()->route('crm.dettaglio', $contatto)->with('success', 'Interazione registrata.');
    }

    public function eliminaInterazione(CrmContatto $contatto, CrmInterazione $interazione)
    {
        $interazione->delete();

        // Ricalcola ultimo_contatto dall'interazione più recente
        $ultima = $contatto->interazioni()->orderByDesc('data_interazione')->first();
        $contatto->ultimo_contatto = $ultima ? $ultima->data_interazione->toDateString() : null;
        $contatto->ricalcolaProssimoFollowup();
        $contatto->save();

        return redirect()->route('crm.dettaglio', $contatto)->with('success', 'Interazione eliminata.');
    }
}
