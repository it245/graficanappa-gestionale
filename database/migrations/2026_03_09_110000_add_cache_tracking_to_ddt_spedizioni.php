<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ddt_spedizioni', function (Blueprint $table) {
            $table->string('brt_stato', 50)->nullable()->after('qta');
            $table->string('brt_data_consegna', 30)->nullable()->after('brt_stato');
            $table->string('brt_destinatario', 150)->nullable()->after('brt_data_consegna');
            $table->unsignedInteger('brt_colli')->nullable()->after('brt_destinatario');
            $table->timestamp('brt_cache_at')->nullable()->after('brt_colli');
        });
    }

    public function down(): void
    {
        Schema::table('ddt_spedizioni', function (Blueprint $table) {
            $table->dropColumn(['brt_stato', 'brt_data_consegna', 'brt_destinatario', 'brt_colli', 'brt_cache_at']);
        });
    }
};
