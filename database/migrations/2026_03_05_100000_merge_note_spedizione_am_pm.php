<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Unisci contenuto_am e contenuto_pm in un unico campo 'contenuto'
        Schema::table('note_spedizione', function (Blueprint $table) {
            $table->text('contenuto')->nullable()->after('data');
        });

        // Migra dati esistenti
        DB::table('note_spedizione')->get()->each(function ($nota) {
            $parti = array_filter([
                $nota->contenuto_am ? "AM: {$nota->contenuto_am}" : null,
                $nota->contenuto_pm ? "PM: {$nota->contenuto_pm}" : null,
            ]);
            if ($parti) {
                DB::table('note_spedizione')
                    ->where('id', $nota->id)
                    ->update(['contenuto' => implode("\n", $parti)]);
            }
        });

        Schema::table('note_spedizione', function (Blueprint $table) {
            $table->dropColumn(['contenuto_am', 'contenuto_pm']);
        });
    }

    public function down(): void
    {
        Schema::table('note_spedizione', function (Blueprint $table) {
            $table->text('contenuto_am')->nullable();
            $table->text('contenuto_pm')->nullable();
        });

        Schema::table('note_spedizione', function (Blueprint $table) {
            $table->dropColumn('contenuto');
        });
    }
};
