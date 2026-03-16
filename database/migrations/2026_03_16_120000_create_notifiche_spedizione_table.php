<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifiche_spedizione')) {
            Schema::create('notifiche_spedizione', function (Blueprint $table) {
                $table->id();
                $table->string('tipo', 30); // 'invio_esterno', 'rientro_esterno', etc.
                $table->string('commessa', 30);
                $table->string('fase', 50);
                $table->string('fornitore', 100)->nullable();
                $table->string('messaggio');
                $table->boolean('letto')->default(false);
                $table->timestamps();
                $table->index(['letto', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifiche_spedizione');
    }
};
