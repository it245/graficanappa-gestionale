<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->boolean('esterno')->default(false)->after('priorita_manuale');
        });
    }

    public function down()
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn('esterno');
        });
    }
};
