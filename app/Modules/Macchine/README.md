# Modulo `Macchine`

## Scopo

Registry in-memory delle macchine fisiche di Grafica Nappa con regole
specifiche di ognuna (turni, capacità, vincoli setup):

- **XL106** (Heidelberg offset, 24h lun-ven)
- **JOH** (caldo, 6-22 lun-ven)
- **BOBST** (1 macchina, 2 config rilievi/fustelle, cambio 1h)
- **Piegaincolla** (1 macchina, 3 config PI01/PI02/PI03, cambio 1h)
- Standard 6-22: `STEL`, `PLAST`, `FIN`, `INDIGO`, `TAGLIO`, `LEGAT`, `ZUND`, `MGI`

In futuro la sorgente passerà a una tabella `macchine` senza modifiche al
chiamante.

## API Pubblica

```
MacchinaRegistry::all(): array<string, MacchinaInterface>
MacchinaRegistry::find(string $id): MacchinaInterface  // throws InvalidArgumentException
MacchinaRegistry::exists(string $id): bool
MacchinaRegistry::flush(): void                        // utility test

CalcoloOreService::stima(MacchinaInterface $m, int $copie): float
```

Implementazioni di `MacchinaInterface`: `RegoleXL106`, `RegoleJOH`,
`RegoleBOBST`, `RegolePiegaincolla`, `RegoleStandard`.

## Esempio

```php
use App\Modules\Macchine\MacchinaRegistry;
use App\Modules\Macchine\Services\CalcoloOreService;

$xl106 = MacchinaRegistry::find('XL106');
$ore   = app(CalcoloOreService::class)->stima($xl106, copie: 5000);
```

## Dipendenze

- Nessun service esterno.
- Nessuna dipendenza DB (tutto in-memory, cacheato in `self::$cache`).
- Consumato da: `Scheduling` (capacità per priorità), `Stampa` (calcolo ore).
