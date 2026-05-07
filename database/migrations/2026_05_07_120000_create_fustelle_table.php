<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('fustelle')) {
            return;
        }

        Schema::create('fustelle', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 20)->unique();
            // Enum textual (compat MySQL/MariaDB e cast PHP enum)
            $table->enum('tipo', ['PIANA', 'ROTATIVA', 'TRANCIATURA', 'RILIEVO']);
            $table->enum('stato', ['PREPARAZIONE', 'PRONTA', 'IN_USO', 'ARCHIVIATA'])
                ->default('PREPARAZIONE');
            $table->unsignedInteger('dimensione_mm_x')->nullable();
            $table->unsignedInteger('dimensione_mm_y')->nullable();
            $table->decimal('spessore_mm', 5, 2)->nullable();
            $table->string('posizione_magazzino', 50)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('stato');
            $table->index('posizione_magazzino');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fustelle');
    }
};
