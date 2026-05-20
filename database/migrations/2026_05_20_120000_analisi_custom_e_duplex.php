<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Workspace analisi custom (utente crea, aggiunge commesse, fa confronti)
        Schema::create('analisi_custom', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 200);
            $table->string('descrizione', 500)->nullable();
            $table->string('autore', 100)->nullable();
            $table->json('filtri')->nullable();        // filtri salvati (periodo, cliente, ecc)
            $table->json('opzioni_view')->nullable(); // colonne visibili, ordinamento, raggruppamenti
            $table->timestamp('ultimo_accesso')->nullable();
            $table->timestamps();
            $table->index(['autore', 'ultimo_accesso']);
        });

        Schema::create('analisi_custom_commesse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisi_id')->constrained('analisi_custom')->cascadeOnDelete();
            $table->string('commessa', 20);
            $table->json('override_voci')->nullable(); // override specifici di questa analisi (no globali)
            $table->string('etichetta', 100)->nullable(); // tag custom utente
            $table->integer('ordine')->default(0);
            $table->timestamps();
            $table->unique(['analisi_id', 'commessa']);
        });

        // 2. Duplex in fiery_accounting (per click × 2 Konica)
        if (Schema::hasTable('fiery_accounting') && !Schema::hasColumn('fiery_accounting', 'duplex')) {
            Schema::table('fiery_accounting', function (Blueprint $table) {
                $table->boolean('duplex')->default(false)->after('tipo_formato');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analisi_custom_commesse');
        Schema::dropIfExists('analisi_custom');
        if (Schema::hasTable('fiery_accounting') && Schema::hasColumn('fiery_accounting', 'duplex')) {
            Schema::table('fiery_accounting', function (Blueprint $table) {
                $table->dropColumn('duplex');
            });
        }
    }
};
