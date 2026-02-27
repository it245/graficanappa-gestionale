<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->integer('scarti_previsti')->nullable()->default(null)->after('scarti');
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn('scarti_previsti');
        });
    }
};
