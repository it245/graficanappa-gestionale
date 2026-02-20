<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->decimal('valore_ordine', 12, 2)->nullable()->after('UM_carta')
                  ->comment('TotMerce da Onda (netto IVA)');
            $table->decimal('costo_materiali', 10, 2)->nullable()->after('valore_ordine')
                  ->comment('SUM costo materiali da Onda PRDDocRighe');
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropColumn(['valore_ordine', 'costo_materiali']);
        });
    }
};
