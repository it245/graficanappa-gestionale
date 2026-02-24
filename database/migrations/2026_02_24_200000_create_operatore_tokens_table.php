<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operatore_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operatore_id');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('operatore_id')->references('id')->on('operatori')->onDelete('cascade');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operatore_tokens');
    }
};
