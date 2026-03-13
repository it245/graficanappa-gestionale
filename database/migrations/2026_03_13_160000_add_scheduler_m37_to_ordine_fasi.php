<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->boolean('disponibile_m37')->default(false)->index()->after('scarti_previsti');
            $table->float('urgenza_reale')->nullable()->after('disponibile_m37');
            $table->tinyInteger('fascia_urgenza')->nullable()->index()->after('urgenza_reale');
            $table->float('giorni_lavoro_residuo')->nullable()->after('fascia_urgenza');
            $table->string('batch_key', 50)->nullable()->index()->after('giorni_lavoro_residuo');
            $table->integer('sequenza_m37')->nullable()->index()->after('batch_key');
            $table->float('priorita_m37')->nullable()->index()->after('sequenza_m37');
            $table->integer('sched_posizione')->nullable()->after('priorita_m37');
            $table->string('sched_macchina', 20)->nullable()->after('sched_posizione');
            $table->dateTime('sched_inizio')->nullable()->after('sched_macchina');
            $table->dateTime('sched_fine')->nullable()->after('sched_inizio');
            $table->float('sched_setup_h')->nullable()->after('sched_fine');
            $table->string('sched_setup_tipo', 40)->nullable()->after('sched_setup_h');
            $table->string('sched_batch_group', 80)->nullable()->after('sched_setup_tipo');
            $table->timestamp('sched_calcolato_at')->nullable()->after('sched_batch_group');
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn([
                'disponibile_m37', 'urgenza_reale', 'fascia_urgenza',
                'giorni_lavoro_residuo', 'batch_key', 'sequenza_m37', 'priorita_m37',
                'sched_posizione', 'sched_macchina', 'sched_inizio', 'sched_fine',
                'sched_setup_h', 'sched_setup_tipo', 'sched_batch_group', 'sched_calcolato_at',
            ]);
        });
    }
};
