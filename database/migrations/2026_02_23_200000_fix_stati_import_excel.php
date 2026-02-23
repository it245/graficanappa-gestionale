<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix stati fasi importate da Excel:
     * - stato 2 (Avviato) → 3 (Terminato)
     * - stato 1 (Pronto) → 2 (Avviato)
     * ORDINE IMPORTANTE: prima 2→3, poi 1→2
     */
    public function up(): void
    {
        DB::statement("UPDATE ordine_fasi SET stato = '3' WHERE stato = '2'");
        DB::statement("UPDATE ordine_fasi SET stato = '2' WHERE stato = '1'");
    }

    public function down(): void
    {
        DB::statement("UPDATE ordine_fasi SET stato = '1' WHERE stato = '2'");
        DB::statement("UPDATE ordine_fasi SET stato = '2' WHERE stato = '3'");
    }
};
