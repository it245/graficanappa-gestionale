<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            if (!Schema::hasColumn('ordine_fasi', 'descrizione_fase')) {
                $table->text('descrizione_fase')->nullable()->after('fase');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            if (Schema::hasColumn('ordine_fasi', 'descrizione_fase')) {
                $table->dropColumn('descrizione_fase');
            }
        });
    }
};
