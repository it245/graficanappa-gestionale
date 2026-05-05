<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            // Flag: scarico magazzino già eseguito per questa fase (evita doppio prelievo)
            $table->boolean('scarico_eseguito')->default(false)->index();
            // Flag: fase passata a 3 ma scarico non ancora confermato dall'operatore
            $table->boolean('pending_scarico')->default(false)->index();
            // Articolo magazzino effettivamente prelevato (può differire da ordini.cod_carta)
            $table->unsignedBigInteger('articolo_carta_id')->nullable();
            // Quantità totale prelevata (qta_prod + scarti, modificabile dall'operatore)
            $table->integer('qta_carta_prelevata')->nullable();
            // Lotto opzionale (se scansionato QR bancale)
            $table->string('lotto_carta', 50)->nullable();
            // Timestamp conferma scarico
            $table->timestamp('scarico_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn([
                'scarico_eseguito',
                'pending_scarico',
                'articolo_carta_id',
                'qta_carta_prelevata',
                'lotto_carta',
                'scarico_at',
            ]);
        });
    }
};
