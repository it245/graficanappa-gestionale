# CI/CD — MES Grafica Nappa

## Workflow attivo

`.github/workflows/quality.yml` — esegue quality gate su push/PR.

### Cosa fa

1. Checkout codice
2. Setup PHP 8.2 (extensions: mbstring, intl, gd, zip, pdo_mysql, pdo_sqlite; snmp opzionale)
3. Cache `vendor/` (key: hash di `composer.lock`)
4. `composer install --no-scripts --prefer-dist`
5. **Pint** (`composer format-test`) — *warning, non-blocking* (baseline debt)
6. **PHPStan** (`composer stan`) — *strict, BLOCCANTE* (baseline copre 629 errori esistenti, zero nuovi accettabili)
7. **Unit test** (`php artisan test --testsuite=Unit`) — *warning, non-blocking* (Pusher dependency da risolvere)

### Quando triggera

- `push` su branch `def2.0` o `master`
- `pull_request` verso `master`

## Come fixare se fallisce

### PHPStan fail (BLOCCANTE)

```bash
composer stan
```

Se il fail è dovuto a **nuovi errori** introdotti dalle tue modifiche, correggi il codice. Non aggiungere mai gli errori al baseline (`phpstan-baseline.neon`) per nascondere bug — il baseline è solo per debt storico.

Per debug locale:
```bash
vendor/bin/phpstan analyse --memory-limit=2G path/to/file.php
```

### Pint fail (WARNING)

```bash
composer format       # auto-fix
composer format-test  # verifica
```

Pint è in `continue-on-error` perché il baseline ha tante violazioni. Non bloccare PR per Pint, ma idealmente formattare i file modificati.

### Unit test fail (WARNING)

Spesso causato da `pusher/pusher-php-server` o broadcast driver non configurato in CI. Verificare `phpunit.xml` env e `BROADCAST_DRIVER=log` in `.env.example`.

## Estensioni future (TODO)

- [ ] **Feature test con MySQL service** — aggiungere `services: mysql:8.0` al job, eseguire migration + seed minimal, abilitare `php artisan test` completo
- [ ] **Deploy automatico** — su tag `v*` o push `master`, SSH su server `.60` ed esegui `git pull && composer install --no-dev && php artisan migrate --force`
- [ ] **Rimuovere `continue-on-error` da Pint** — quando il codebase è interamente formattato (`composer format` su tutto il progetto + commit cleanup)
- [ ] **Coverage report** — aggiungere `xdebug` + upload artifact `coverage.xml`
- [ ] **Matrix PHP 8.2 / 8.3** — quando upgrade PHP runtime sul server
