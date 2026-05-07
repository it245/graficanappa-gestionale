<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Audit;

use App\Modules\Audit\Contracts\AuditSinkInterface;
use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\LivelloSicurezza;
use App\Modules\Audit\Enums\TipoAzione;
use App\Modules\Audit\Services\AuditLogService;
use App\Modules\Audit\ValueObjects\AuditEvent;
use PHPUnit\Framework\TestCase;

/**
 * Verifica AuditLogService:
 *  - costruisce AuditEvent corretto
 *  - sanitizza payload sensibili prima della scrittura
 *  - non lancia eccezioni se sink fallisce
 */
final class AuditLogServiceTest extends TestCase
{
    public function test_log_costruisce_evento_e_lo_passa_al_sink(): void
    {
        $sink = new InMemorySink();
        $service = new AuditLogService($sink);

        $service->log(
            azione:   TipoAzione::Update,
            entita:   EntitaAudit::OrdineFase,
            entitaId: 42,
            prima:    ['stato' => 1],
            dopo:     ['stato' => 2],
            livello:  LivelloSicurezza::Normale,
            extra:    'test',
        );

        $this->assertCount(1, $sink->eventi);
        $evento = $sink->eventi[0];
        $this->assertInstanceOf(AuditEvent::class, $evento);
        $this->assertSame(TipoAzione::Update, $evento->azione);
        $this->assertSame(EntitaAudit::OrdineFase, $evento->entita);
        $this->assertSame(42, $evento->entitaId);
        $this->assertSame(['stato' => 1], $evento->diff->prima);
        $this->assertSame(['stato' => 2], $evento->diff->dopo);
        $this->assertSame('test', $evento->extra);
    }

    public function test_payload_con_password_e_sanitizzato_prima_della_scrittura(): void
    {
        $sink = new InMemorySink();
        $service = new AuditLogService($sink);

        $service->log(
            azione:   TipoAzione::Update,
            entita:   EntitaAudit::Utente,
            entitaId: 1,
            prima:    ['email' => 'a@b.it', 'password' => 'old'],
            dopo:     ['email' => 'a@b.it', 'password' => 'new'],
        );

        $evento = $sink->eventi[0];
        $this->assertSame('***MASKED***', $evento->diff->prima['password']);
        $this->assertSame('***MASKED***', $evento->diff->dopo['password']);
        $this->assertSame('a@b.it', $evento->diff->prima['email']);
    }

    public function test_log_login_usa_livello_sensibile(): void
    {
        $sink = new InMemorySink();
        $service = new AuditLogService($sink);

        $service->logLogin(7, 'mario.rossi');

        $evento = $sink->eventi[0];
        $this->assertSame(TipoAzione::Login, $evento->azione);
        $this->assertSame(LivelloSicurezza::Sensibile, $evento->livello);
        $this->assertSame(7, $evento->entitaId);
    }

    public function test_log_login_fallito_usa_livello_critico(): void
    {
        $sink = new InMemorySink();
        $service = new AuditLogService($sink);

        $service->logLoginFallito('fakeuser', 'password errata');

        $evento = $sink->eventi[0];
        $this->assertSame(TipoAzione::Failed, $evento->azione);
        $this->assertSame(LivelloSicurezza::Critico, $evento->livello);
        $this->assertSame('password errata', $evento->extra);
        $this->assertSame('fakeuser', $evento->diff->dopo['username_tentato']);
    }
}

/**
 * Sink di test che colleziona eventi in array.
 */
final class InMemorySink implements AuditSinkInterface
{
    /** @var list<AuditEvent> */
    public array $eventi = [];

    public function scrivi(AuditEvent $evento): void
    {
        $this->eventi[] = $evento;
    }
}
