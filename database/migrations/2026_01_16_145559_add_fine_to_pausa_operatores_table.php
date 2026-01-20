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
    Schema::table('pausa_operatores', function (Blueprint $table) {
        $table->timestamp('fine')->nullable()->after('created_at');
    });
}

public function down(): void
{
    Schema::table('pausa_operatores', function (Blueprint $table) {
        $table->dropColumn('fine');
    });
}
};
