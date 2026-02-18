<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dati produzione sulla fase (salvati al termine automatico)
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->integer('fogli_buoni')->nullable()->after('qta_prod');
            $table->integer('fogli_scarto')->nullable()->after('fogli_buoni');
            $table->integer('tempo_avviamento_sec')->nullable()->after('fogli_scarto');
            $table->integer('tempo_esecuzione_sec')->nullable()->after('tempo_avviamento_sec');
        });

        // Tracking fogli buoni/scarto nel log sync
        Schema::table('prinect_sync_log', function (Blueprint $table) {
            $table->integer('fogli_buoni')->nullable()->after('fogli_totali');
            $table->integer('fogli_scarto')->nullable()->after('fogli_buoni');
        });

        // Tracking fogli buoni/scarto nello stato macchina (ultimo valore noto)
        Schema::table('prinect_stato_macchina', function (Blueprint $table) {
            $table->integer('fogli_buoni')->nullable()->after('ultimo_status');
            $table->integer('fogli_scarto')->nullable()->after('fogli_buoni');
        });

        // Storico attivita importato dall'API activity
        Schema::create('prinect_attivita', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->string('activity_id')->nullable();       // "4000", "4100"
            $table->string('activity_name')->nullable();      // "Avviamento", "Produzione fogli buoni"
            $table->string('time_type_name')->nullable();     // "Tempo di avviamento", "Tempo di esecuzione"
            $table->string('time_type_group')->nullable();    // "Tempo di produzione"
            $table->string('prinect_job_id')->nullable();     // job.id es "66455"
            $table->string('prinect_job_name')->nullable();   // job.name es "FS2144 astucci perle modena"
            $table->string('commessa_gestionale')->nullable();
            $table->string('workstep_name')->nullable();      // es "FB 002  6/0"
            $table->integer('good_cycles')->default(0);
            $table->integer('waste_cycles')->default(0);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('operatore_prinect')->nullable();  // nome completo da API
            $table->string('cost_center')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'prinect_job_id']);
            $table->index('start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prinect_attivita');

        Schema::table('prinect_stato_macchina', function (Blueprint $table) {
            $table->dropColumn(['fogli_buoni', 'fogli_scarto']);
        });

        Schema::table('prinect_sync_log', function (Blueprint $table) {
            $table->dropColumn(['fogli_buoni', 'fogli_scarto']);
        });

        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropColumn(['fogli_buoni', 'fogli_scarto', 'tempo_avviamento_sec', 'tempo_esecuzione_sec']);
        });
    }
};
