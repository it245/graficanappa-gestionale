<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('ordini', 'fustella_codice')) {
            Schema::table('ordini', function (Blueprint $table) {
                $table->string('fustella_codice', 30)->nullable()->after('note_prestampa');
            });
        }
        if (!Schema::hasColumn('ordini', 'colori')) {
            Schema::table('ordini', function (Blueprint $table) {
                $table->string('colori', 100)->nullable()->after('fustella_codice');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            if (Schema::hasColumn('ordini', 'fustella_codice')) $table->dropColumn('fustella_codice');
            if (Schema::hasColumn('ordini', 'colori')) $table->dropColumn('colori');
        });
    }
};
