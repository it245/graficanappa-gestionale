<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoGiacenza;
use App\Services\MagazzinoService;
use App\Modules\Magazzino\Services\GiacenzaService;
use App\Modules\Magazzino\Services\MovimentoService;
use App\Modules\Magazzino\Enums\TipoMovimento;

/**
 * Controller carico/scarico/reso/rettifica.
 *
 * Strangler Fig (def2.0):
 *  - Iniezione di App\Modules\Magazzino\Services\GiacenzaService e MovimentoService
 *    via constructor (Laravel container DI, no bind necessario per concrete class).
 *  - Le mutazioni di giacenza passano dal modulo (lockForUpdate + dispatch
 *    SottoSogliaEvento), il legacy App\Services\MagazzinoService resta solo
 *    per i flussi che dipendono da artefatti collaterali (etichetta QR sul
 *    carico, rettifica per giacenza_id puntuale).
 *  - Response shape JSON/redirect invariata: il frontend non cambia.
 */
class MagazzinoMovimentoController extends Controller
{
    public function __construct(
        private readonly GiacenzaService $giacenze,
        private readonly MovimentoService $movimenti,
    ) {
    }

    /**
     * Form registrazione carico (bolla fornitore).
     */
    public function formCarico(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $articoli = MagazzinoArticolo::where('attivo', true)->orderBy('descrizione')->get();

        return view('magazzino.carico', [
            'operatore' => $operatore,
            'articoli' => $articoli,
            'ocrDati' => session('ocrDati', []),
        ]);
    }

    /**
     * Registra carico da bolla.
     *
     * Mantiene MagazzinoService::registraCarico legacy perché crea anche
     * l'etichetta QR (artefatto fuori dominio modulo Magazzino). La giacenza
     * viene comunque protetta da lockForUpdate dentro il legacy.
     */
    public function registraCarico(Request $request)
    {
        $request->validate([
            'quantita' => 'required|numeric|min:0.01',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        // Se non selezionato un articolo esistente, creane uno nuovo
        $articoloId = $request->articolo_id;
        if (!$articoloId) {
            $tipoCarta = trim($request->categoria ?? '');
            $formato = trim($request->formato ?? '');
            $grammatura = $request->grammatura ? (int) $request->grammatura : null;

            if (!$tipoCarta) {
                return back()->withInput()->with('error', 'Seleziona un articolo esistente o compila il tipo carta.');
            }

            // Genera codice da tipo carta + formato + grammatura
            $codice = strtoupper(preg_replace('/[^A-Z0-9]/', '', $tipoCarta));
            if ($formato) $codice .= '.' . str_replace('x', 'X', $formato);
            if ($grammatura) $codice .= '.' . $grammatura;

            $descrizione = $tipoCarta;
            if ($formato) $descrizione .= ' ' . $formato;
            if ($grammatura) $descrizione .= ' ' . $grammatura . 'g';

            $articolo = MagazzinoArticolo::firstOrCreate(
                ['codice' => $codice],
                [
                    'descrizione' => $descrizione,
                    'categoria' => $tipoCarta,
                    'formato' => $formato ?: null,
                    'grammatura' => $grammatura,
                    'fornitore' => $request->fornitore,
                ]
            );
            $articoloId = $articolo->id;
        }

        $result = MagazzinoService::registraCarico([
            'articolo_id' => $articoloId,
            'quantita' => $request->quantita,
            'lotto' => $request->lotto,
            'fornitore' => $request->fornitore,
            'operatore_id' => $operatore?->id,
            'note' => $request->note,
            'foto_bolla' => $request->session()->get('ocr_foto_bolla'),
            'ocr_raw' => $request->session()->get('ocr_raw'),
        ]);

        Log::info('Movimento magazzino', [
            'tipo' => 'carico',
            'articolo_id' => $articoloId,
            'quantita' => (float) $request->quantita,
            'lotto' => $request->lotto,
            'fornitore' => $request->fornitore,
            'operatore_id' => $operatore?->id,
        ]);

        $request->session()->forget(['ocr_foto_bolla', 'ocr_raw', 'ocrDati']);

        return redirect()->route('magazzino.etichetta.stampa', [
            'id' => $result['etichetta']->id,
            'op_token' => $request->get('op_token'),
        ])->with('success', 'Carico registrato — stampa etichetta QR');
    }

    /**
     * Form prelievo per produzione.
     */
    public function formPrelievo(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $giacenze = MagazzinoGiacenza::with(['articolo', 'ubicazione'])
            ->where('quantita', '>', 0)
            ->get();

        return view('magazzino.prelievo', [
            'operatore' => $operatore,
            'giacenze' => $giacenze,
        ]);
    }

    /**
     * Form reso (rientro fogli avanzati).
     */
    public function formReso(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $articoli = MagazzinoArticolo::where('attivo', true)->orderBy('descrizione')->get();

        return view('magazzino.reso', [
            'operatore' => $operatore,
            'articoli' => $articoli,
        ]);
    }

    /**
     * Registra reso (rientro fogli avanzati) tramite MovimentoService.
     */
    public function registraReso(Request $request)
    {
        $request->validate([
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|numeric|min:0.01',
            'commessa' => 'nullable|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        $articolo = MagazzinoArticolo::findOrFail($request->articolo_id);

        try {
            $this->movimenti->registraMovimento(
                codArt: $articolo->codice,
                tipo: TipoMovimento::Reso,
                quantita: (float) $request->quantita,
                causale: 'RESO',
                meta: [
                    'lotto' => $request->lotto ?: null,
                    'commessa' => $request->commessa,
                    'operatore_id' => $operatore?->id,
                    'note' => $request->note,
                ],
            );

            Log::info('Movimento magazzino', [
                'tipo' => 'reso',
                'articolo_id' => $articolo->id,
                'quantita' => (float) $request->quantita,
                'lotto' => $request->lotto,
                'commessa' => $request->commessa,
                'operatore_id' => $operatore?->id,
            ]);

            return redirect()->route('magazzino.dashboard', ['op_token' => $request->get('op_token')])
                ->with('success', 'Reso registrato');
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Reso non valido';
            return back()->withInput()->with('error', $msg);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Form rettifica inventariale.
     */
    public function formRettifica(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $giacenze = MagazzinoGiacenza::with(['articolo'])
            ->orderBy('articolo_id')
            ->get();

        return view('magazzino.rettifica', [
            'operatore' => $operatore,
            'giacenze' => $giacenze,
        ]);
    }

    /**
     * Registra rettifica.
     *
     * Resta su MagazzinoService legacy: la rettifica lavora per giacenza_id
     * puntuale (riga specifica con ubicazione/lotto), mentre il modulo aggrega
     * sulla riga "principale". Il legacy ha già lockForUpdate sulla riga.
     */
    public function registraRettifica(Request $request)
    {
        $request->validate([
            'giacenza_id' => 'required|exists:magazzino_giacenze,id',
            'nuova_quantita' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        try {
            MagazzinoService::rettifica(
                $request->giacenza_id,
                $request->nuova_quantita,
                $operatore?->id,
                $request->note
            );

            Log::info('Movimento magazzino', [
                'tipo' => 'rettifica',
                'giacenza_id' => (int) $request->giacenza_id,
                'nuova_quantita' => (float) $request->nuova_quantita,
                'operatore_id' => $operatore?->id,
            ]);

            return redirect()->route('magazzino.giacenze', ['op_token' => $request->get('op_token')])
                ->with('success', 'Rettifica registrata');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Registra prelievo (scarico per produzione, fase STAMPA*).
     *
     * Validazione fase STAMPA mantenuta inline (regola di business legacy);
     * lo scarico effettivo passa da MovimentoService -> GiacenzaService con
     * lockForUpdate per evitare race condition tra tablet di reparto.
     */
    public function registraPrelievo(Request $request)
    {
        $request->validate([
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|numeric|min:0.01',
            'commessa' => 'required|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $fase = strtoupper(trim($request->fase ?? 'STAMPA'));
        if (!str_starts_with($fase, 'STAMPA')) {
            return back()->withInput()->with(
                'error',
                'Scarico consentito solo per fasi di stampa (STAMPA, STAMPAXL106, STAMPAINDIGO, ecc.)'
            );
        }

        $articolo = MagazzinoArticolo::findOrFail($request->articolo_id);

        try {
            $this->movimenti->registraMovimento(
                codArt: $articolo->codice,
                tipo: TipoMovimento::Scarico,
                quantita: (float) $request->quantita,
                causale: 'PRODUZIONE',
                meta: [
                    'ubicazione_id' => $request->ubicazione_id ?: null,
                    'lotto' => $request->lotto ?: null,
                    'commessa' => $request->commessa,
                    'fase' => $fase,
                    'operatore_id' => $operatore?->id,
                    'note' => $request->note,
                ],
            );

            Log::info('Movimento magazzino', [
                'tipo' => 'scarico',
                'articolo_id' => $articolo->id,
                'quantita' => (float) $request->quantita,
                'commessa' => $request->commessa,
                'fase' => $fase,
                'operatore_id' => $operatore?->id,
            ]);

            return redirect()->route('magazzino.dashboard', ['op_token' => $request->get('op_token')])
                ->with('success', 'Prelievo registrato');
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Prelievo non valido';
            return back()->withInput()->with('error', $msg);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
