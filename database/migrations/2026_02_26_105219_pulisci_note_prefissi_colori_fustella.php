<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rimuovi i prefissi [COL: ...] e [FS: ...] salvati per errore nelle note.
     */
    public function up(): void
    {
        $fasi = DB::table('ordine_fasi')
            ->where(function ($q) {
                $q->where('note', 'LIKE', '%[COL:%')
                  ->orWhere('note', 'LIKE', '%[FS:%');
            })
            ->get();

        foreach ($fasi as $fase) {
            $note = $fase->note;

            // Rimuovi tutti i prefissi [COL: ...] e [FS: ...]
            $note = preg_replace('/\[COL:\s*[^\]]*\]\s*/i', '', $note);
            $note = preg_replace('/\[FS:\s*[^\]]*\]\s*/i', '', $note);

            // Pulisci spazi e newline residui
            $note = trim($note);

            // Se rimane vuoto, metti null
            $note = $note === '' ? null : $note;

            DB::table('ordine_fasi')
                ->where('id', $fase->id)
                ->update(['note' => $note]);
        }
    }

    public function down(): void
    {
        // Non reversibile - i prefissi erano dati spuri
    }
};
