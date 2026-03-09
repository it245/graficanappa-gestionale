<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->unsignedSmallInteger('sequenza')->default(500)->after('priorita_manuale');
            $table->boolean('disponibile')->default(false)->after('sequenza');
            $table->decimal('urgenza_reale', 8, 2)->nullable()->after('disponibile');
            $table->tinyInteger('fascia_urgenza')->nullable()->after('urgenza_reale');
            $table->decimal('giorni_lavoro_residuo', 6, 2)->nullable()->after('fascia_urgenza');
            $table->string('batch_key', 100)->nullable()->after('giorni_lavoro_residuo');

            $table->index('disponibile');
            $table->index('fascia_urgenza');
            $table->index('batch_key');
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropIndex(['disponibile']);
            $table->dropIndex(['fascia_urgenza']);
            $table->dropIndex(['batch_key']);
            $table->dropColumn([
                'sequenza', 'disponibile', 'urgenza_reale',
                'fascia_urgenza', 'giorni_lavoro_residuo', 'batch_key',
            ]);
        });
    }
};
