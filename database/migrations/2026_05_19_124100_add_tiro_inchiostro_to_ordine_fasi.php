<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->decimal('tiro_cm_foil', 10, 2)->nullable()->after('fogli_scarto');
            $table->decimal('inchiostro_g', 10, 2)->nullable()->after('tiro_cm_foil');
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn(['tiro_cm_foil', 'inchiostro_g']);
        });
    }
};
