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
        $table->integer('quantita')->default(0);
        $table->string('cod_carta', 50)->nullable();
        $table->integer('qta_fase')->default(0);
        $table->string('carta', 50)->nullable();
        $table->integer('qta_carta')->default(0);
        $table->string('UM_carta', 10)->nullable();
    });
}

public function down(): void
{
    Schema::table('ordini', function (Blueprint $table) {
        $table->dropColumn(['quantita','cod_carta','qta_fase','carta','qta_carta','UM_carta']);
    });
}
};
