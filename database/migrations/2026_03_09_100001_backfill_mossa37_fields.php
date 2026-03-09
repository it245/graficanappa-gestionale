<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\PriorityService;
use App\Models\OrdineFase;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill sequenza su tutte le fasi esistenti
        $sequenze = config('sequenza_fasi', []);

        foreach ($sequenze as $fase => $seq) {
            OrdineFase::where('fase', $fase)
                ->whereNull('deleted_at')
                ->update(['sequenza' => $seq]);
        }

        // Ricalcola tutti i campi Mossa 37 (disponibilità, urgenza, batch, priorità)
        PriorityService::ricalcolaTutti();
    }

    public function down(): void
    {
        // Reset campi Mossa 37
        OrdineFase::whereNull('deleted_at')->update([
            'sequenza' => 500,
            'disponibile' => false,
            'urgenza_reale' => null,
            'fascia_urgenza' => null,
            'giorni_lavoro_residuo' => null,
            'batch_key' => null,
        ]);
    }
};
