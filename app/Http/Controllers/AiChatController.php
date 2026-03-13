<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiChatController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'storico' => 'nullable|array',
        ]);

        $messaggio = $request->input('message');
        $storico = $request->input('storico', []);

        $risposta = AiService::chat($messaggio, $storico);

        // Salva in DB
        $operatoreId = session('operatore_id');
        DB::table('ai_chat_messages')->insert([
            'operatore_id' => $operatoreId,
            'user_message' => $messaggio,
            'ai_response' => $risposta,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'response' => $risposta,
        ]);
    }

    public function storico()
    {
        $operatoreId = session('operatore_id');

        $messaggi = DB::table('ai_chat_messages')
            ->where('operatore_id', $operatoreId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'messaggi' => $messaggi,
        ]);
    }

    public function cancella()
    {
        $operatoreId = session('operatore_id');
        DB::table('ai_chat_messages')->where('operatore_id', $operatoreId)->delete();

        return response()->json(['success' => true]);
    }
}
