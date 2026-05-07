# Modulo `Stampa`

## Scopo

Astrazione comune sopra le due integrazioni di stampa:

- **Prinect** (Heidelberg XL106 offset, REST API).
- **Fiery** (HP Indigo digitale, Accounting API + v5 jobs).

Espone un `StampaIntegrationInterface` unico cosi i Controller della
dashboard non devono distinguere il vendor. I service legacy
(`App\Http\Services\PrinectService`, `FieryService`) restano intoccati
dietro gli `Adapters/` (Anti-Corruption Layer).

## API Pubblica

```
interface StampaIntegrationInterface
    getId(): string                                       // 'prinect' | 'fiery'
    getJobInStampa(): ?array                              // job live o null
    getCopieFatte(string $jobId): int
    getTempiAvviamentoEsecuzione(string $jobId): array    // ['avv'=>sec,'esec'=>sec]

PrinectAdapter implements StampaIntegrationInterface
FieryAdapter   implements StampaIntegrationInterface

StampaQuotaService::quotaResidua(int $tiratura, int $fatte): int
```

**Enums:** `TipoStampa::Offset`, `::Digitale`.
**ValueObjects:** `Tiratura`.
**Rules:** `AvviamentoRule`, `CalcoloOreStampaRule`.

## Esempio

```php
use App\Modules\Stampa\Adapters\PrinectAdapter;

$adapter = app(PrinectAdapter::class);
$job = $adapter->getJobInStampa();
// ['jobId' => '...', 'nome' => '66811-XL', 'copieFatte' => 3200, ...]
```

## Dipendenze

- Wrappa `App\Http\Services\PrinectService` (REST Heidelberg).
- Wrappa `App\Http\Services\FieryService` (REST EFI).
- Consumato da: `OwnerController`, `OperatoreController`, sync queue jobs.
- Produce dati consumati da: `Documenti` (sync Excel), `Commessa` (ore lav.).
