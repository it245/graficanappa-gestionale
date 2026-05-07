<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;

/**
 * Esportazione log per finalità compliance.
 *
 * Casi d'uso:
 *  - GDPR art. 15 — diritto di accesso: l'operatore richiede tutti i log
 *    su sé stesso; restituiamo elenco filtrato + pseudonimizzato.
 *  - GDPR art. 17 — diritto all'oblio: NON cancelliamo audit, ma
 *    pseudonimizziamo (user_name → 'EX-USER-{id}') tramite job dedicato.
 *  - Procedura sindacale art. 4 SL: export per RSU/RSA su periodo
 *    concordato, aggregato per produttività.
 *  - Audit ITL / ispettori: export non aggregato, completo, autentico.
 *
 * Il servizio NON fa enforcement permessi: chiamarlo dietro
 * AccessoLogRule::puoEsportare() / Policy.
 */
final class ComplianceExportService
{
    public function __construct(
        private readonly AuditQueryService $query,
    ) {}

    /**
     * Export GDPR art. 15 per uno specifico operatore.
     *
     * @return list<array<string,mixed>>
     */
    public function esportaPerOperatore(
        int $userId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $rows = $this->query->perUtente($userId, $from, $to);

        return $rows->map(function ($r) {
            return [
                'data'      => $r->created_at,
                'azione'    => $r->action,
                'entita'    => $r->model,
                'entita_id' => $r->model_id,
                'prima'     => $r->old_values !== null ? json_decode($r->old_values, true) : null,
                'dopo'      => $r->new_values !== null ? json_decode($r->new_values, true) : null,
                'ip'        => $r->ip,
                // user_agent escluso: non utile in export per l'operatore stesso
                // e potenzialmente fingerprinting cross-device (minimizzazione GDPR)
            ];
        })->values()->all();
    }

    /**
     * Aggregato per RSU: conta eventi per giorno e per azione, no payload.
     * Pensato per art. 4 Statuto Lavoratori (controllo collettivo, non individuale).
     *
     * @return list<array<string,mixed>>
     */
    public function aggregatoSindacale(
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $da = CarbonImmutable::parse($from)->startOfDay();
        $a  = CarbonImmutable::parse($to)->endOfDay();

        // Query separata: aggregato puro (no PII)
        $rows = \DB::table('audit_logs')
            ->selectRaw('DATE(created_at) as giorno, action, COUNT(*) as totale')
            ->whereBetween('created_at', [$da->toDateTimeString(), $a->toDateTimeString()])
            ->groupBy('giorno', 'action')
            ->orderBy('giorno')
            ->get();

        return $rows->map(fn ($r) => [
            'giorno' => $r->giorno,
            'azione' => $r->action,
            'totale' => (int) $r->totale,
        ])->all();
    }
}
