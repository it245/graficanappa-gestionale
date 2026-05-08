<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Onda\Services\CommessaSyncService;
use App\Modules\Onda\Services\OrdineSyncService;
use App\Modules\Reparti\Services\RepartoService;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Strangler Fig completato per i metodi principali (sincronizza,
 *             sincronizzaSingolaCommessa, getMappaReparti, getTipoReparto).
 *
 *             Body trasferito in:
 *               - {@see \App\Modules\Onda\Services\OrdineSyncService::sync()}
 *                 (era ~870 righe di sincronizza())
 *               - {@see \App\Modules\Onda\Services\CommessaSyncService::sync()}
 *                 (era ~280 righe di sincronizzaSingolaCommessa())
 *               - {@see \App\Modules\Reparti\Services\RepartoService::mappaSlugToId()}
 *               - {@see \App\Modules\Reparti\Services\RepartoService::tipoFromCodice()}
 *
 *             Questa classe ora è un wrapper sottile (~200 righe) che mantiene
 *             la stabile API statica per i caller legacy:
 *               - cron `php artisan onda:sync`
 *               - DashboardOwnerController, DashboardSpedizioneController
 *               - ImportExcelTutto (Reflection su getMappaReparti)
 *
 *             I metodi sincronizzaDDT* (Fornitore / FornitureLavorazioni / Vendita)
 *             restano qui finché non saranno migrati nei rispettivi moduli
 *             (Spedizione::DdtSyncService gestisce gia DDT vendita).
 */
class OndaSyncService
{
    /**
     * Adapter Onda risolto dal container (lazy).
     */
    private static function onda(): OndaErpInterface
    {
        return app(OndaErpInterface::class);
    }

    /**
     * Sincronizza ordini e fasi dal gestionale Onda al MES.
     *
     * Orchestratore THIN: delega tutto a {@see OrdineSyncService::sync()}.
     *
     * @return array<string, mixed> Riepilogo: ordini creati/aggiornati, fasi create, log dettagliato.
     */
    public static function sincronizza(): array
    {
        $r = app(OrdineSyncService::class)->sync();

        return [
            'ordini_creati'         => $r['ordini_creati'] ?? 0,
            'ordini_aggiornati'     => $r['ordini_aggiornati'] ?? 0,
            'fasi_create'           => $r['fasi_create'] ?? 0,
            'duplicati_rimossi'     => $r['duplicati_rimossi'] ?? 0,
            'log_ordini_creati'     => $r['log_ordini_creati'] ?? [],
            'log_ordini_aggiornati' => $r['log_ordini_aggiornati'] ?? [],
            'log_fasi_create'       => $r['log_fasi_create'] ?? [],
        ];
    }

    /**
     * Sincronizza una singola commessa da Onda, senza filtro data.
     * Utile per commesse vecchie che il sync normale non prende.
     *
     * Orchestratore THIN: delega a {@see CommessaSyncService::sync()}.
     */
    public static function sincronizzaSingolaCommessa(string $codCommessa): array
    {
        return app(CommessaSyncService::class)->sync($codCommessa);
    }

    /**
     * @deprecated Wrapper di compatibilità. Usa {@see \App\Modules\Spedizione\Services\DdtFornitoreSyncService::sync()}.
     */
    public static function sincronizzaDDTFornitore(): int
    {
        return app(\App\Modules\Spedizione\Services\DdtFornitoreSyncService::class)->sync(30);
    }

    /**
     * @deprecated Wrapper di compatibilità. Usa {@see \App\Modules\Spedizione\Services\DdtLavorazioniSyncService::sync()}.
     */
    public static function sincronizzaDDTFornitureLavorazioni(): int
    {
        return app(\App\Modules\Spedizione\Services\DdtLavorazioniSyncService::class)->sync(30);
    }

    /**
     * @deprecated Wrapper di compatibilità. Usa {@see \App\Modules\Spedizione\Services\DdtSyncService::syncFromOnda()}.
     *
     * Mantenuto per non rompere i caller esistenti (cron `php artisan onda:sync`,
     * DashboardOwnerController, DashboardSpedizioneController).
     */
    public static function sincronizzaDDTVendita(): int
    {
        $risultato = app(\App\Modules\Spedizione\Services\DdtSyncService::class)->syncFromOnda(7);

        return ($risultato['inseriti'] ?? 0) + ($risultato['aggiornati'] ?? 0);
    }

    /**
     * Mappa codice fase Onda → reparto MES.
     *
     * @deprecated Migrato in {@see RepartoService::mappaSlugToId()}.
     *             Wrapper mantenuto per backward-compat con ImportExcelTutto
     *             (Reflection) e script standalone (import_commessa.php,
     *             import_commessa_onda.php, confronta_tutte.php).
     *
     * @return array<string, string> Chiave = codice fase Onda, valore = nome reparto.
     */
    public static function getMappaReparti(): array
    {
        return app(RepartoService::class)->mappaSlugToId();
    }

    /**
     * Mappa codice fase Onda → tipo (multifase/monofase/max 2 fasi).
     *
     * @deprecated Migrato in {@see RepartoService::tipoFromCodice()}.
     *
     * @return array<string, string> Chiave = codice fase Onda, valore = tipo reparto.
     */
    public static function getTipoReparto(): array
    {
        return app(RepartoService::class)->tipoFromCodice();
    }
}
