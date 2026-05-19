<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commessa_altri_costi')) {
            Schema::table('commessa_altri_costi', function (Blueprint $table) {
                $table->decimal('importo', 10, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // no-op: lasciamo nullable
    }
};
