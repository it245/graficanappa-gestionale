<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Contracts\AuditSinkInterface;
use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\LivelloSicurezza;
use App\Modules\Audit\Enums\TipoAzione;
use App\Modules\Audit\Rules\DatiSensibiliRule;
use App\Modules\Audit\ValueObjects\AuditEvent;
use App\Modules\Audit\ValueObjects\ContestoUtente;
use App\Modules\Audit\ValueObjects\DiffPayload;

/**
 * API principale del modulo Audit.
 *
 * Costruisce un {@see AuditEvent} sanitizzato e lo passa al sink configurato.
 * Sostituisce le chiamate sparse a `AuditService::log()` legacy + `AuditLog::create()`.
 *
 * Uso tipico (Service / Listener):
 *
 *   app(AuditLogService::class)->log(
 *       TipoAzione::Update,
 *       EntitaAudit::OrdineFase,
 *       $fase->id,
 *       prima: ['stato' => 1],
 *       dopo:  ['stato' => 2],
 *   );
 *
 * Tutti i fallimenti sono assorbiti dal sink (best-effort): il caller non
 * deve mai try/catch attorno a log() — l'audit non blocca il business.
 */
final class AuditLogService
{
    public function __construct(
        private readonly AuditSinkInterface $sink,
    ) {}

    /**
     * Persiste un evento di audit.
     *
     * @param array<string,mixed>|null $prima  stato precedente (sanitizzato in automatico)
     * @param array<string,mixed>|null $dopo   stato nuovo (sanitizzato in automatico)
     */
    public function log(
        TipoAzione $azione,
        EntitaAudit $entita,
        int|string|null $entitaId = null,
        ?array $prima = null,
        ?array $dopo = null,
        LivelloSicurezza $livello = LivelloSicurezza::Normale,
        ?string $extra = null,
    ): void {
        $diff = new DiffPayload(
            prima: DatiSensibiliRule::mask($prima),
            dopo:  DatiSensibiliRule::mask($dopo),
        );

        $contesto = ContestoUtente::dalContestoCorrente();

        $evento = new AuditEvent(
            azione:   $azione,
            entita:   $entita,
            entitaId: $entitaId,
            diff:     $diff,
            contesto: $contesto,
            livello:  $livello,
            extra:    $extra,
        );

        $this->sink->scrivi($evento);
    }

    /**
     * Shortcut: login riuscito.
     */
    public function logLogin(int $userId, string $userName): void
    {
        $contesto = new ContestoUtente(
            userId: $userId,
            userName: $userName,
            ip: function_exists('request') && request() ? request()->ip() : null,
            userAgent: function_exists('request') && request()
                ? substr((string) (request()->userAgent() ?? ''), 0, 500)
                : null,
        );

        $this->sink->scrivi(new AuditEvent(
            azione:   TipoAzione::Login,
            entita:   EntitaAudit::Sessione,
            entitaId: $userId,
            diff:     new DiffPayload(),
            contesto: $contesto,
            livello:  LivelloSicurezza::Sensibile,
        ));
    }

    /**
     * Shortcut: tentativo login fallito (no userId, solo username tentato).
     */
    public function logLoginFallito(?string $usernameTentato, ?string $motivo = null): void
    {
        $contesto = ContestoUtente::dalContestoCorrente();

        $this->sink->scrivi(new AuditEvent(
            azione:   TipoAzione::Failed,
            entita:   EntitaAudit::Sessione,
            entitaId: null,
            diff:     new DiffPayload(dopo: ['username_tentato' => $usernameTentato]),
            contesto: $contesto,
            livello:  LivelloSicurezza::Critico,
            extra:    $motivo,
        ));
    }
}
