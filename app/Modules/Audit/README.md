# Modulo Audit

Logging centralizzato di eventi business + compliance GDPR / art. 4 Statuto Lavoratori.

## Struttura

```
Audit/
├── Contracts/AuditSinkInterface.php        astrazione storage
├── Adapters/{Database,File,Null}AuditSink  3 backend di persistenza
├── Services/
│   ├── AuditLogService                     API principale .log(...)
│   ├── AuditQueryService                   ricerca log (read-side)
│   └── ComplianceExportService             GDPR art. 15 + RSU aggregato
├── ValueObjects/{AuditEvent,DiffPayload,ContestoUtente}
├── Enums/{TipoAzione,EntitaAudit,LivelloSicurezza}
├── Rules/
│   ├── DatiSensibiliRule                   mask password/token/Bearer/sk-*
│   ├── RitenzioneRule                      GDPR retention policy
│   └── AccessoLogRule                      chi può leggere log
└── Listeners/                              ascolta eventi business
    ├── LogFaseAvviata
    ├── LogFaseTerminata
    ├── LogCommessaCompletata
    ├── LogMovimentoMagazzino
    ├── LogLoginRiuscito                    Auth\Events\Login
    └── LogLoginFallito                     Auth\Events\Failed
```

## API rapida

```php
use App\Modules\Audit\Services\AuditLogService;
use App\Modules\Audit\Enums\{TipoAzione, EntitaAudit, LivelloSicurezza};

app(AuditLogService::class)->log(
    azione:   TipoAzione::Update,
    entita:   EntitaAudit::OrdineFase,
    entitaId: $fase->id,
    prima:    ['stato' => 1],
    dopo:     ['stato' => 2],
    livello:  LivelloSicurezza::Normale,
);
```

## Sink configurabile

`config('mes.audit.sink')` → FQCN dell'adapter (default `DatabaseAuditSink`).

```php
// .env
MES_AUDIT_SINK=App\Modules\Audit\Adapters\DatabaseAuditSink
```

## Compliance

- **GDPR art. 15** (accesso): `ComplianceExportService::esportaPerOperatore()`
- **GDPR art. 17** (oblio): pseudonimizzazione user_name in job dedicato (TODO)
- **Statuto Lavoratori art. 4**: `ComplianceExportService::aggregatoSindacale()` —
  produce solo dati aggregati senza PII per condivisione RSU/RSA
- **Provv. Garante 27/11/2008** (admin di sistema): retention min 6 mesi soddisfatta

## Schema DB

Tabella `audit_logs` esistente (migration `2026_03_27_100000`):
`id, user_id, user_name, action, model, model_id, old_values, new_values, ip, user_agent, extra, created_at`.

Indici: user_id, action, model, created_at, (model, model_id).

Il VO `AuditEvent::toRow()` adatta automaticamente lo schema corrente —
nessuna migration aggiuntiva necessaria.

## Estensione

Per aggiungere un nuovo evento auditato:
1. Crea il listener in `Listeners/Log{Evento}.php`
2. Registralo in `ModulesServiceProvider::LISTEN`
3. Inietta `AuditLogService` via constructor (no facade)
