<?php

namespace App\Http\Controllers;

use App\Events\NuovoMessaggioChat;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private function getOperatoreId(Request $request): int
    {
        return $request->attributes->get('operatore_id') ?? session('operatore_id') ?? 0;
    }

    /**
     * Mappa canale chat -> elenco reparti che possono leggerlo/scriverlo.
     * Canali aperti a tutti: Tutti, Urgenze.
     */
    private function canaliRepartiMap(): array
    {
        return [
            'Stampa Offset'   => ['stampa offset'],
            'Stampa a Caldo'  => ['stampa a caldo'],
            'Fustella'        => ['fustella piana', 'fustella cilindrica'],
            'Piegaincolla'    => ['piegaincolla'],
            'Legatoria'       => ['legatoria'],
            'Spedizione'      => ['spedizione'],
            'Prestampa'       => ['prestampa'],
            'Digitale'        => ['digitale'],
            'Finestratura'    => ['finestratura'],
            'Plastificazione' => ['plastificazione'],
            'Finitura digitale' => ['finitura digitale'],
            'Tagliacarte'     => ['tagliacarte'],
        ];
    }

    /**
     * True se l'operatore puo' leggere/scrivere il canale.
     * Owner/admin (session operatore_ruolo) sempre true.
     */
    private function operatoreVedeCanale(Request $request, string $canale): bool
    {
        if ($canale === 'Tutti' || $canale === 'Urgenze') return true;

        $ruolo = $request->attributes->get('operatore_ruolo') ?? session('operatore_ruolo') ?? '';
        if (in_array($ruolo, ['owner', 'admin'])) return true;

        $opId = $this->getOperatoreId($request);
        if (!$opId) return false;

        $op = \App\Models\Operatore::with('reparti')->find($opId);
        if (!$op) return false;

        $repartiOp = $op->reparti->pluck('nome')->map(fn($n) => strtolower(trim($n)))->toArray();
        if (empty($repartiOp) && $op->reparto) {
            $repartiOp = array_map('trim', array_map('strtolower', explode(',', $op->reparto)));
        }

        $map = $this->canaliRepartiMap();
        $repartiCanale = $map[$canale] ?? [];
        foreach ($repartiCanale as $r) {
            if (in_array(strtolower($r), $repartiOp)) return true;
        }
        return false;
    }

    public function index(Request $request)
    {
        $canale = $request->query('canale', 'generale');
        $operatoreId = $this->getOperatoreId($request);

        $messaggi = ChatMessage::with('operatore')
            ->where('canale', $canale)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $canali = ['generale', 'stampa', 'fustella', 'legatoria', 'spedizione'];

        return view('chat.index', compact('messaggi', 'canale', 'canali', 'operatoreId'));
    }

    public function invia(Request $request)
    {
        $request->validate([
            'messaggio' => 'required|string|max:1000',
            'canale' => 'required|string|max:50',
        ]);

        if (!$this->operatoreVedeCanale($request, $request->canale)) {
            return response()->json(['ok' => false, 'errore' => 'Canale non autorizzato'], 403);
        }

        $operatoreId = $this->getOperatoreId($request);

        $chatMessage = ChatMessage::create([
            'operatore_id' => $operatoreId,
            'messaggio' => $request->messaggio,
            'canale' => $request->canale,
        ]);

        $chatMessage->load('operatore');

        // Broadcast solo se Reverb è configurato (non in dev senza server)
        try {
            broadcast(new NuovoMessaggioChat($chatMessage));
        } catch (\Throwable $e) {
            // Reverb non attivo — il polling gestisce comunque
        }

        return response()->json([
            'ok' => true,
            'id' => $chatMessage->id,
            'messaggio' => $chatMessage->messaggio,
            'utente' => $chatMessage->operatore->nome ?? 'Utente',
            'timestamp' => $chatMessage->created_at->format('H:i'),
        ]);
    }

    /**
     * Elimina messaggio.
     * Scope 'me': nasconde solo per l'utente corrente (hidden_for array).
     * Scope 'all': soft delete totale. Permesso solo se autore (entro 5 min) o owner/admin.
     */
    public function elimina(Request $request, int $id)
    {
        $scope = $request->input('scope', 'me');
        $msg = ChatMessage::find($id);
        if (!$msg) return response()->json(['ok' => false, 'errore' => 'Messaggio non trovato'], 404);

        $opId = $this->getOperatoreId($request);
        $ruolo = $request->attributes->get('operatore_ruolo') ?? session('operatore_ruolo') ?? '';
        $isOwnerAdmin = in_array($ruolo, ['owner', 'admin']);

        if ($scope === 'all') {
            $isAutore = $msg->operatore_id === $opId;
            $entroFinestra = $msg->created_at && $msg->created_at->diffInMinutes(now()) <= 5;
            if (!$isOwnerAdmin && !($isAutore && $entroFinestra)) {
                return response()->json(['ok' => false, 'errore' => 'Non autorizzato'], 403);
            }
            $msg->delete(); // soft delete
            return response()->json(['ok' => true, 'scope' => 'all']);
        }

        // scope 'me'
        if (!$opId) return response()->json(['ok' => false, 'errore' => 'Sessione richiesta'], 401);
        $hidden = $msg->hidden_for ?? [];
        if (!in_array($opId, $hidden)) {
            $hidden[] = $opId;
            $msg->hidden_for = $hidden;
            $msg->save();
        }
        return response()->json(['ok' => true, 'scope' => 'me']);
    }

    public function messaggi(Request $request)
    {
        $canale = $request->query('canale', 'generale');
        $after = $request->query('after', 0);
        $operatoreId = $this->getOperatoreId($request);

        if (!$this->operatoreVedeCanale($request, $canale)) {
            return response()->json([]);
        }

        $messaggi = ChatMessage::with('operatore')
            ->withTrashed() // include soft-deleted per mostrare tombstone "Messaggio eliminato"
            ->where('canale', $canale)
            ->where('id', '>', $after)
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->filter(fn($m) => !$m->isHiddenFor($operatoreId))
            ->map(fn($m) => [
                'id' => $m->id,
                'messaggio' => $m->messaggio,
                'utente' => $m->operatore->nome ?? 'Utente',
                'timestamp' => $m->created_at->format('H:i'),
                'mio' => $m->operatore_id === $operatoreId,
                'autore_id' => $m->operatore_id,
                'eta_min' => (int) $m->created_at->diffInMinutes(now()),
                'eliminato' => $m->trashed(),
            ])
            ->values();

        return response()->json($messaggi);
    }
}
