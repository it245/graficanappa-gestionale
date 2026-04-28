<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenant: aggiunge tenant_id alle tabelle business.
 * Default 'grafica_nappa' per dati esistenti, così Grafica Nappa
 * resta tenant nativo senza interruzione.
 */
return new class extends Migration
{
    private array $tables = [
        'ordini',
        'ordine_fasi',
        'fase_operatore',
        'operatori',
        'reparti',
        'fasi_catalogo',
        'cliche_anagrafica',
        'magazzino_articoli',
        'magazzino_giacenze',
        'magazzino_movimenti',
        'magazzino_etichette',
        'ddt_spedizioni',
        'turni',
        'note_consegne',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->string('tenant_id', 50)->default('grafica_nappa')->after('id');
                $t->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex([$t->getTable() . '_tenant_id_index']);
                $t->dropColumn('tenant_id');
            });
        }
    }
};
