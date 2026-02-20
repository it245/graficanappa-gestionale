<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fase_operatore', function (Blueprint $table) {
            $table->unsignedInteger('secondi_pausa')->default(0)->after('data_fine');
        });
    }

    public function down(): void
    {
        Schema::table('fase_operatore', function (Blueprint $table) {
            $table->dropColumn('secondi_pausa');
        });
    }
};
