<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campi 2FA sull'operatore
        Schema::table('operatori', function (Blueprint $table) {
            $table->string('two_factor_secret', 500)->nullable()->after('password');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
        });

        // Dispositivi fidati (stile Fortnite)
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operatore_id');
            $table->string('device_token_hash', 128)->unique();
            $table->string('device_name', 255)->nullable(); // User-Agent parsed
            $table->string('ip_first_use', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('operatore_id')->references('id')->on('operatori')->onDelete('cascade');
            $table->index('operatore_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');

        Schema::table('operatori', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_enabled', 'two_factor_recovery_codes']);
        });
    }
};
