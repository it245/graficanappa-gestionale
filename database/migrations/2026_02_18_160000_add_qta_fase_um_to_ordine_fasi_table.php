<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            if (!Schema::hasColumn('ordine_fasi', 'qta_fase')) {
                $table->integer('qta_fase')->default(0)->after('fase_catalogo_id');
            }
            if (!Schema::hasColumn('ordine_fasi', 'um')) {
                $table->string('um', 10)->nullable()->after('qta_fase');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn(['qta_fase', 'um']);
        });
    }
};
