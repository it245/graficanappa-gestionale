<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->string('commento_produzione', 255)->nullable()->after('responsabile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropColumn('commento_produzione');
        });
    }
};
