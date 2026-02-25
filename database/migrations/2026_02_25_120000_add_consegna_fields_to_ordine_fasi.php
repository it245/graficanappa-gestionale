<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->string('tipo_consegna', 20)->nullable()->after('note'); // 'totale' o 'parziale'
            $table->integer('qta_consegnata')->nullable()->after('tipo_consegna');
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn(['tipo_consegna', 'qta_consegnata']);
        });
    }
};
