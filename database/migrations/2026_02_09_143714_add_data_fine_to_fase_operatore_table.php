<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fase_operatore', function (Blueprint $table) {
            if (!Schema::hasColumn('fase_operatore', 'data_fine')) {
                $table->timestamp('data_fine')->nullable()->after('data_inizio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fase_operatore', function (Blueprint $table) {
            $table->dropColumn('data_fine');
        });
    }
};