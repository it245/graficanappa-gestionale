<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->string('carta', 255)->nullable()->change();
            $table->string('cod_carta', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->string('carta', 50)->nullable()->change();
            $table->string('cod_carta', 50)->nullable()->change();
        });
    }
};
