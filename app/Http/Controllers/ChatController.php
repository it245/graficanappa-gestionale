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

    public function messaggi(Request $request)
    {
        $canale = $request->query('canale', 'generale');
        $after = $request->query('after', 0);
        $operatoreId = $this->getOperatoreId($request);

        $messaggi = ChatMessage::with('operatore')
            ->where('canale', $canale)
            ->where('id', '>', $after)
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'messaggio' => $m->messaggio,
                'utente' => $m->operatore->nome ?? 'Utente',
                'timestamp' => $m->created_at->format('H:i'),
                'mio' => $m->operatore_id === $operatoreId,
            ]);

        return response()->json($messaggi);
    }
}
