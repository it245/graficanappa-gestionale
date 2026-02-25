<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->unsignedBigInteger('ddt_vendita_id')->nullable()->after('ordine_cliente');
            $table->decimal('qta_ddt_vendita', 10, 2)->nullable()->after('ddt_vendita_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropColumn(['ddt_vendita_id', 'qta_ddt_vendita']);
        });
    }
};
