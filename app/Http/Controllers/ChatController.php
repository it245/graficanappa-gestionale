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

    /**
     * Invia messaggio audio (vocale).
     * Body multipart: canale, audio (file), durata (sec)
     */
    public function inviaAudio(Request $request)
    {
        $request->validate([
            'canale' => 'required|string|max:50',
            'audio' => 'required|file|mimes:webm,mp3,ogg,wav,m4a|max:5120', // 5MB
            'durata' => 'nullable|integer|min:1|max:300',
        ]);

        if (!$this->operatoreVedeCanale($request, $request->canale)) {
            return response()->json(['ok' => false, 'errore' => 'Canale non autorizzato'], 403);
        }

        $operatoreId = $this->getOperatoreId($request);
        $file = $request->file('audio');
        $nome = 'chat_' . $operatoreId . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('chat-audio', $nome, 'public');

        $chatMessage = ChatMessage::create([
            'operatore_id'    => $operatoreId,
            'messaggio'       => '[Vocale]',
            'canale'          => $request->canale,
            'audio_path'      => $path,
            'audio_durata_sec' => $request->input('durata'),
        ]);
        $chatMessage->load('operatore');

        try { broadcast(new \App\Events\NuovoMessaggioChat($chatMessage)); } catch (\Throwable $e) {}

        return response()->json([
            'ok' => true,
            'id' => $chatMessage->id,
            'audio_url' => asset('storage/' . $path),
            'durata' => $chatMessage->audio_durata_sec,
            'utente' => $chatMessage->operatore->nome ?? 'Utente',
            'timestamp' => $chatMessage->created_at->format('H:i'),
        ]);
    }

    /**
     * Upload allegato (immagine/PDF/doc generico). Max 10MB.
     * Body multipart: canale, file, messaggio (opzionale caption)
     */
    public function allega(Request $request)
    {
        $request->validate([
            'canale' => 'required|string|max:50',
            'file' => 'required|file|max:10240', // 10MB
            'messaggio' => 'nullable|string|max:500',
        ]);

        if (!$this->operatoreVedeCanale($request, $request->canale)) {
            return response()->json(['ok' => false, 'errore' => 'Canale non autorizzato'], 403);
        }

        $operatoreId = $this->getOperatoreId($request);
        $file = $request->file('file');
        $nome = 'chat_' . $operatoreId . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('chat-allegati', $nome, 'public');

        $chatMessage = ChatMessage::create([
            'operatore_id'     => $operatoreId,
            'messaggio'        => $request->input('messaggio') ?: '[Allegato]',
            'canale'           => $request->canale,
            'attachment_path'  => $path,
            'attachment_name'  => $file->getClientOriginalName(),
            'attachment_size'  => $file->getSize(),
            'attachment_mime'  => $file->getMimeType(),
        ]);
        $chatMessage->load('operatore');

        try { broadcast(new \App\Events\NuovoMessaggioChat($chatMessage)); } catch (\Throwable $e) {}

        return response()->json([
            'ok' => true,
            'id' => $chatMessage->id,
            'attachment_url' => asset('storage/' . $path),
            'attachment_name' => $chatMessage->attachment_name,
            'attachment_mime' => $chatMessage->attachment_mime,
            'messaggio' => $chatMessage->messaggio,
            'utente' => $chatMessage->operatore->nome ?? 'Utente',
            'timestamp' => $chatMessage->created_at->format('H:i'),
        ]);
    }

    /**
     * Fissa/rimuovi pin con durata. Body: durata_min (1h=60, 8h=480, 24h=1440, 7gg=10080, 0=illimitato).
     * Solo autore o owner/admin.
     */
    public function togglePin(Request $request, int $id)
    {
        $msg = ChatMessage::find($id);
        if (!$msg) return response()->json(['ok' => false], 404);

        $opId = $this->getOperatoreId($request);
        $ruolo = $request->attributes->get('operatore_ruolo') ?? session('operatore_ruolo') ?? '';
        $isOwnerAdmin = in_array($ruolo, ['owner', 'admin']);
        if (!$isOwnerAdmin && $msg->operatore_id !== $opId) {
            return response()->json(['ok' => false, 'errore' => 'Non autorizzato'], 403);
        }

        $durataMin = (int) $request->input('durata_min', 1440);

        // Esplicito unpin: durata_min == -1 oppure pin gia' esistente con durata != -1
        $existing = \DB::table('chat_message_pins')->where('chat_message_id', $id)->first();
        if ($durataMin === -1) {
            if ($existing) {
                \DB::table('chat_message_pins')->where('chat_message_id', $id)->delete();
            }
            return response()->json(['ok' => true, 'pinned' => false]);
        }
        if ($existing) {
            // gia' pinnato e nuova durata richiesta -> aggiorna scadenza (no unpin implicito)
            $scadeAtUpd = $durataMin > 0 ? now()->addMinutes($durataMin) : null;
            \DB::table('chat_message_pins')->where('chat_message_id', $id)->update([
                'scade_at' => $scadeAtUpd,
                'pinned_by' => $opId,
                'updated_at' => now(),
            ]);
            return response()->json(['ok' => true, 'pinned' => true, 'scade_at' => $scadeAtUpd?->format('d/m H:i')]);
        }

        $scadeAt = $durataMin > 0 ? now()->addMinutes($durataMin) : null;

        // Rimuovi eventuali pin scaduti dello stesso canale
        \DB::table('chat_message_pins')
            ->where('canale', $msg->canale)
            ->whereNotNull('scade_at')
            ->where('scade_at', '<', now())
            ->delete();

        \DB::table('chat_message_pins')->insert([
            'chat_message_id' => $id,
            'canale' => $msg->canale,
            'pinned_by' => $opId,
            'scade_at' => $scadeAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'pinned' => true, 'scade_at' => $scadeAt?->format('d/m H:i')]);
    }

    /**
     * Toggle stellina personale (preferito utente).
     */
    public function toggleStar(Request $request, int $id)
    {
        $opId = $this->getOperatoreId($request);
        if (!$opId) return response()->json(['ok' => false], 401);

        $msg = ChatMessage::find($id);
        if (!$msg) return response()->json(['ok' => false], 404);

        $existing = \DB::table('chat_message_stars')
            ->where('chat_message_id', $id)
            ->where('operatore_id', $opId)
            ->first();

        if ($existing) {
            \DB::table('chat_message_stars')
                ->where('chat_message_id', $id)
                ->where('operatore_id', $opId)
                ->delete();
            return response()->json(['ok' => true, 'starred' => false]);
        }

        \DB::table('chat_message_stars')->insert([
            'chat_message_id' => $id,
            'operatore_id' => $opId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true, 'starred' => true]);
    }

    /**
     * Lista messaggi stellati dall'utente corrente.
     */
    public function getStarred(Request $request)
    {
        $opId = $this->getOperatoreId($request);
        if (!$opId) return response()->json([]);

        $rows = \DB::table('chat_message_stars')
            ->join('chat_messages', 'chat_messages.id', '=', 'chat_message_stars.chat_message_id')
            ->leftJoin('operatori', 'operatori.id', '=', 'chat_messages.operatore_id')
            ->where('chat_message_stars.operatore_id', $opId)
            ->orderBy('chat_message_stars.created_at', 'desc')
            ->select(
                'chat_messages.id',
                'chat_messages.messaggio',
                'chat_messages.canale',
                'chat_messages.created_at',
                'chat_messages.attachment_path',
                'chat_messages.attachment_mime',
                'operatori.nome as utente'
            )
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'messaggio' => $r->messaggio,
                    'canale' => $r->canale,
                    'utente' => $r->utente ?? 'Utente',
                    'timestamp' => \Carbon\Carbon::parse($r->created_at)->format('d/m H:i'),
                    'attachment_url' => $r->attachment_path ? asset('storage/' . $r->attachment_path) : null,
                    'attachment_mime' => $r->attachment_mime,
                ];
            });
        return response()->json($rows);
    }

    /**
     * Ritorna pin attivi (non scaduti) per canale.
     */
    public function getPinAttivi(Request $request)
    {
        $canale = $request->query('canale', 'Tutti');
        if (!$this->operatoreVedeCanale($request, $canale)) return response()->json([]);

        $pins = \DB::table('chat_message_pins')
            ->join('chat_messages', 'chat_messages.id', '=', 'chat_message_pins.chat_message_id')
            ->where('chat_message_pins.canale', $canale)
            ->where(function ($q) {
                $q->whereNull('chat_message_pins.scade_at')
                  ->orWhere('chat_message_pins.scade_at', '>', now());
            })
            ->select('chat_messages.id', 'chat_messages.messaggio', 'chat_messages.operatore_id', 'chat_message_pins.scade_at')
            ->orderBy('chat_message_pins.created_at', 'desc')
            ->get();

        return response()->json($pins);
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

    /**
     * Registra visualizzazione (idempotente). Solo operatori loggati.
     */
    public function visualizza(Request $request, int $id)
    {
        $opId = $this->getOperatoreId($request);
        if (!$opId) return response()->json(['ok' => false], 401);

        $msg = ChatMessage::find($id);
        if (!$msg) return response()->json(['ok' => false], 404);
        if ($msg->operatore_id === $opId) return response()->json(['ok' => true, 'skip' => 'self']);

        \App\Models\ChatMessageLettura::firstOrCreate(
            ['chat_message_id' => $id, 'operatore_id' => $opId],
            ['letto_at' => now()]
        );
        return response()->json(['ok' => true]);
    }

    /**
     * Numero di operatori destinatari di un canale (escluso autore).
     * Usato per "✓✓ blu" quando tutti hanno letto.
     */
    private function destinatariCanale(string $canale, int $escludiOpId): int
    {
        $query = \App\Models\Operatore::where('attivo', true)
            ->where('id', '!=', $escludiOpId);

        if ($canale === 'Tutti' || $canale === 'Urgenze') {
            return $query->count();
        }

        $map = $this->canaliRepartiMap();
        $repartiTarget = $map[$canale] ?? [];
        if (empty($repartiTarget)) return 0;

        return $query->whereHas('reparti', function ($q) use ($repartiTarget) {
            $q->whereIn(\DB::raw('LOWER(nome)'), array_map('strtolower', $repartiTarget));
        })->count();
    }

    public function messaggi(Request $request)
    {
        $canale = $request->query('canale', 'generale');
        $after = $request->query('after', 0);
        $operatoreId = $this->getOperatoreId($request);

        if (!$this->operatoreVedeCanale($request, $canale)) {
            return response()->json([]);
        }

        $messaggi = ChatMessage::with(['operatore', 'letture.operatore'])
            ->withTrashed()
            ->where('canale', $canale)
            ->where('id', '>', $after)
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        // Set di ID stellati dall'utente corrente in un colpo
        $starredIds = $operatoreId ? \DB::table('chat_message_stars')
            ->where('operatore_id', $operatoreId)
            ->whereIn('chat_message_id', $messaggi->pluck('id'))
            ->pluck('chat_message_id')
            ->toArray() : [];

        $messaggi = $messaggi
            ->filter(fn($m) => !$m->isHiddenFor($operatoreId))
            ->map(function ($m) use ($operatoreId, $canale, $starredIds) {
                $letture = $m->letture->map(fn($l) => [
                    'operatore_id' => $l->operatore_id,
                    'nome' => $l->operatore->nome ?? 'Utente',
                    'letto_at' => $l->letto_at->format('d/m H:i'),
                ])->values();
                $destinatari = $m->operatore_id === $operatoreId
                    ? $this->destinatariCanale($canale, $m->operatore_id)
                    : 0;
                $audioUrl = $m->audio_path ? asset('storage/' . $m->audio_path) : null;
                $attUrl = $m->attachment_path ? asset('storage/' . $m->attachment_path) : null;
                return [
                    'id' => $m->id,
                    'messaggio' => $m->messaggio,
                    'audio_url' => $audioUrl,
                    'audio_durata_sec' => $m->audio_durata_sec,
                    'attachment_url' => $attUrl,
                    'attachment_name' => $m->attachment_name,
                    'attachment_mime' => $m->attachment_mime,
                    'attachment_size' => $m->attachment_size,
                    'is_pinned' => (bool) $m->is_pinned,
                    'utente' => $m->operatore->nome ?? 'Utente',
                    'timestamp' => $m->created_at->format('H:i'),
                    'mio' => $m->operatore_id === $operatoreId,
                    'autore_id' => $m->operatore_id,
                    'eta_min' => (int) $m->created_at->diffInMinutes(now()),
                    'eliminato' => $m->trashed(),
                    'letture_count' => $letture->count(),
                    'letture' => $letture,
                    'destinatari_count' => $destinatari,
                    'is_starred' => in_array($m->id, $starredIds),
                ];
            })
            ->values();

        return response()->json($messaggi);
    }
}
