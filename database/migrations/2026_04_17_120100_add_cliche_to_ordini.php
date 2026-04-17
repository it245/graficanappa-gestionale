<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->integer('cliche_numero')->nullable()->index();
            $table->enum('cliche_match_type', ['auto', 'manual'])->nullable();
            $table->timestamp('cliche_matched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ordini', function (Blueprint $table) {
            $table->dropColumn(['cliche_numero', 'cliche_match_type', 'cliche_matched_at']);
        });
    }
};
