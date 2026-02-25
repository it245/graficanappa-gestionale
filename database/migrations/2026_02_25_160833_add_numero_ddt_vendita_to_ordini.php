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
            $table->string('numero_ddt_vendita', 20)->nullable()->after('ddt_vendita_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropColumn('numero_ddt_vendita');
        });
    }
};
