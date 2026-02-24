<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            if (!Schema::hasColumn('ordine_fasi', 'scarti')) {
                $table->integer('scarti')->default(0)->after('qta_prod');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn('scarti');
        });
    }
};
