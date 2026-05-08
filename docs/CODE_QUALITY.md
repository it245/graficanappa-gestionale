# Code Quality — MES Grafica Nappa

Strumenti automatici per mantenere codice pulito senza pesare sullo sviluppo.

## Strumenti installati

| Tool | Cosa fa | Comando |
|---|---|---|
| **Pint** | Format codice PSR-12/Laravel preset | `composer format` |
| **PHPStan + Larastan** | Analisi statica level 5 (trova bug pre-runtime) | `composer stan` |

## Comandi rapidi

```bash
composer format        # auto-fix formato (modifica file)
composer format-test   # check senza modificare (CI-friendly)
composer stan          # analisi statica (zero errori = OK)
composer quality       # esegue entrambi (format-test + stan)
```

## Politica

### Zero nuovi errori PHPStan

- Baseline `phpstan-baseline.neon` contiene **629 errori esistenti** ("debt acceptable")
- Da oggi: ogni commit nuovo deve passare `composer stan` → zero errori nuovi
- Errori in baseline si pagano gradualmente: 1 file alla volta in PR dedicate

### Format obbligatorio prima commit

```bash
composer format && git add -A && git commit
```

### File esclusi da PHPStan

- `app/Services/OndaSyncService.php` (wrapper @deprecated)
- `app/Http/Services/PrinectService.php` (wrapper @deprecated)
- `app/Http/Services/FieryService.php` (wrapper @deprecated)
- `app/Http/Services/PrinectSyncService.php` (legacy)
- `app/Http/Services/ExcelSyncService.php` (legacy 600 righe)
- `app/Console/Commands/Telegram*.php` (legacy bot)

Quando wrapper legacy verrà eliminato, si rimuove anche dall'exclude.

## Quando applicare

| Scenario | Azione |
|---|---|
| Nuovo file in `app/Modules/` | `composer format` + `composer stan` obbligatori |
| Modifica controller esistente | `composer format` consigliato |
| Hotfix produzione | Format opzionale, stan obbligatorio |
| File legacy (in exclude) | Skip stan, format consigliato |

## Pagare la baseline (futuro)

Strategia: 1 file/settimana fuori dalla baseline.

```bash
# 1. Rimuovi file da baseline manualmente (taglia righe relative)
# 2. Esegui stan
composer stan
# 3. Fix errori uno alla volta
# 4. Commit + push
```

## CI/CD futuro

GitHub Actions su push def2.0/master:
1. `composer install`
2. `composer quality`
3. `php artisan test`
4. Block merge se fallisce

Da implementare prossima sessione (TODO `MEMORY.md`).

## Riferimenti

- [Pint docs](https://laravel.com/docs/pint)
- [PHPStan docs](https://phpstan.org/)
- [Larastan rules](https://github.com/larastan/larastan)
