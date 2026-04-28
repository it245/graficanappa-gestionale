# Architettura Multi-Tenant — MES SaaS

## Cos'è
Trasformazione del MES Grafica Nappa da prodotto single-tenant (1 azienda) a SaaS multi-tenant (N clienti separati sullo stesso codebase, ognuno con dati isolati).

## Componenti

### 1. Migration `tenant_id` su tabelle business
File: `database/migrations/2026_04_28_120000_add_tenant_id_to_business_tables.php`

Tabelle interessate:
- `ordini`, `ordine_fasi`, `fase_operatore`, `operatori`, `reparti`, `fasi_catalogo`
- `cliche_anagrafica`
- `magazzino_articoli`, `magazzino_giacenze`, `magazzino_movimenti`, `magazzino_etichette`
- `ddt_spedizioni`, `turni`, `note_consegne`

Tutti i record esistenti diventano `tenant_id = 'grafica_nappa'`.

### 2. Tabella `tenant_config`
File: `database/migrations/2026_04_28_120100_create_tenant_config_table.php`

Una riga per cliente SaaS. Contiene:
- branding (nome, logo, colori)
- tipo ERP / macchine
- pesi Mossa 37 (configurabili)
- feature flags (moduli abilitati)
- license key + scadenza

Tenant `grafica_nappa` precaricato con scadenza +50 anni.

### 3. Helper `tenant_id()` / `tenant_config()`
File: `app/Helpers/TenantHelper.php` (autoload via composer)

```php
tenant_id();              // 'grafica_nappa' o '4graph'
set_tenant('4graph');     // imposta tenant in sessione
tenant_config('logo_url'); // valore singolo
tenant_config();           // record completo
```

### 4. Trait `BelongsToTenant`
File: `app/Models/Concerns/BelongsToTenant.php`

Aggiunto a modelli business → ogni query filtra per `tenant_id` corrente.

```php
class Ordine extends Model {
    use BelongsToTenant;
    // ...
}
```

Bypass:
```php
Ordine::withoutGlobalScope('tenant')->all(); // tutti i tenant
```

### 5. Middleware `ResolveTenant`
File: `app/Http/Middleware/ResolveTenant.php`
Registrato in `bootstrap/app.php` con `prepend()`.

Risoluzione (ordine):
1. Header `X-Tenant-ID` (per API/agente)
2. Subdomain (`4graph.mes.graficanappa.com` → `4graph`)
3. Default `app.default_tenant_id` = `grafica_nappa`

### 6. Middleware `CheckLicense`
File: `app/Http/Middleware/CheckLicense.php`

Controlla `tenant_config.license_expires_at`. Se scaduta → blocca accesso.
Tenant `grafica_nappa` ha scadenza 50 anni (di fatto perpetua).

## URL multi-tenant

```
mes.graficanappa.com           → grafica_nappa (default, retrocompatibile)
4graph.mes.graficanappa.com    → 4graph
clienteX.mes.graficanappa.com  → clienteX
```

## Onboarding nuovo cliente

1. INSERT in `tenant_config` (riga nuova)
2. Genera license key univoca
3. Setup DNS (sottodominio o entry CNAME)
4. Cliente accede via subdomain con utenti dedicati

## Compatibilità retroattiva

- DB esistente Grafica Nappa: zero impatto, dati restano `grafica_nappa`
- Trait global scope filtra per `grafica_nappa` quando login interno (default)
- Nessun cambio per utenti esistenti

## Sicurezza

- Trait blocca accidentalmente leak cross-tenant a livello query
- License key check su ogni request (cached 5min per perf)
- Tenant inattivo → blocco automatico

## TODO post-merge

- [ ] Aggiungere trait `BelongsToTenant` ai modelli business (uno alla volta, con test)
- [ ] Login per tenant (autenticazione separata)
- [ ] Dashboard admin tenant_config (CRUD tenant da UI)
- [ ] License renewal endpoint API
- [ ] Migrate dati storici eventualmente per altri tenant
