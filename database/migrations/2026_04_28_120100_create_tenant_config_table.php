<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Tabella tenant_config: ogni cliente SaaS ha sua configurazione.
 * - branding (nome, logo, colori)
 * - tipo ERP / macchine / regole specifiche
 * - license key + scadenza
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_config')) {
            return;
        }
        Schema::create('tenant_config', function (Blueprint $t) {
            $t->string('tenant_id', 50)->primary();
            $t->string('nome_azienda', 150);
            $t->string('logo_url', 500)->nullable();
            $t->string('color_primary', 7)->default('#0d6efd');
            $t->string('color_secondary', 7)->default('#6c757d');
            $t->string('erp_type', 50)->nullable(); // onda, galileo, custom, none
            $t->string('macchine_offset_brand', 50)->nullable();
            $t->string('macchine_digitali_brand', 50)->nullable();
            $t->json('mossa37_pesi')->nullable(); // pesi priorità configurabili
            $t->json('feature_flags')->nullable(); // moduli abilitati
            $t->string('license_key', 100)->nullable();
            $t->dateTime('license_expires_at')->nullable();
            $t->boolean('attivo')->default(true);
            $t->timestamps();
        });

        // Tenant nativo Grafica Nappa
        DB::table('tenant_config')->insert([
            'tenant_id' => 'grafica_nappa',
            'nome_azienda' => 'Grafica Nappa',
            'color_primary' => '#0d6efd',
            'color_secondary' => '#6c757d',
            'erp_type' => 'onda',
            'macchine_offset_brand' => 'heidelberg',
            'macchine_digitali_brand' => 'fiery',
            'mossa37_pesi' => json_encode([
                'urgenza' => 1000,
                'ritardo' => 500,
                'affinita_batch' => 200,
                'sequenza_ciclo' => 100,
                'formato' => 50,
            ]),
            'feature_flags' => json_encode([
                'mossa37' => true,
                'magazzino_ai' => true,
                'telegram_bot' => true,
                'brt' => true,
                'nettime' => true,
            ]),
            'license_key' => 'GRAFICA-NAPPA-NATIVE',
            'license_expires_at' => now()->addYears(50),
            'attivo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_config');
    }
};
