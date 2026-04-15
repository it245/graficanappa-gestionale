<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge formato supporto reale (da OC_ATTDocRigheExt di Onda).
 * Il cod_carta ha il formato dell'anagrafica (es. 64x100),
 * ma in produzione si usa un formato diverso (es. 64x50).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->float('supp_base_cm')->nullable()->after('UM_carta');
            $table->float('supp_altezza_cm')->nullable()->after('supp_base_cm');
            $table->integer('resa')->nullable()->after('supp_altezza_cm');
            $table->integer('tot_supporti')->nullable()->after('resa');
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropColumn(['supp_base_cm', 'supp_altezza_cm', 'resa', 'tot_supporti']);
        });
    }
};
