<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiery_accounting', function (Blueprint $table) {
            $table->id();
            $table->string('job_title');                     // titolo job originale Fiery
            $table->string('commessa', 20)->nullable()->index(); // commessa MES estratta
            $table->date('data_stampa')->index();            // data del job
            $table->integer('fogli')->default(0);            // total sheets printed
            $table->integer('copie')->default(0);            // copies printed
            $table->integer('pagine_colore')->default(0);    // total color pages printed
            $table->integer('pagine_bn')->default(0);        // total bw pages printed
            $table->string('formato', 80)->nullable();       // media size (es. "330x480mm")
            $table->enum('tipo_formato', ['piccolo', 'grande'])->default('piccolo'); // A4 vs A3+
            $table->string('stato_job', 30)->nullable();     // print status
            $table->timestamps();

            $table->unique(['job_title', 'data_stampa', 'copie'], 'fiery_acc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiery_accounting');
    }
};
