<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operatore_id')->nullable();
            $table->string('endpoint', 500)->unique();
            $table->string('p256dh_key', 255)->nullable();
            $table->string('auth_token', 255)->nullable();
            $table->string('ruolo', 50)->nullable(); // owner, operatore, spedizione
            $table->timestamps();

            $table->index('operatore_id');
            $table->index('ruolo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
