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
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->timestamp('timeout')->nullable()->after('stato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn('timeout');
        });
    }
};
