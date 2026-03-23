<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contatti', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cognome')->nullable();
            $table->string('azienda')->nullable();
            $table->string('ruolo')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('categoria')->default('altro'); // cliente, fornitore, partner, altro
            $table->string('priorita')->default('media');  // alta, media, bassa
            $table->integer('frequenza_followup_giorni')->default(30);
            $table->date('ultimo_contatto')->nullable();
            $table->date('prossimo_followup')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_interazioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contatto_id')->constrained('crm_contatti')->onDelete('cascade');
            $table->string('tipo'); // telefonata, email, incontro, messaggio, altro
            $table->text('note')->nullable();
            $table->datetime('data_interazione');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_interazioni');
        Schema::dropIfExists('crm_contatti');
    }
};
