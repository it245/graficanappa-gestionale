# Modulo `Reparti`

## Scopo

Tipizzazione dei 12 reparti di Grafica Nappa (tabella `reparti`) e calcolo
di capacità + turni per lo scheduler Mossa 37 e per la dashboard owner.

Il modulo NON tocca il DB se non in `RepartoService` (cache 1h sui
record Eloquent). Tutto il resto è logica pura: enum, value object,
rule statiche.

## API Pubblica

```
enum CodiceReparto: string                  // 12 cases (slug DB)
    ::id(): int                             // → App\Constants\RepartoId
    ::nome(): string
    ::tipo(): TipoReparto
    ::oreSettimana(): int
    ::fromId(int): ?CodiceReparto

enum TipoReparto: string                    // PRESTAMPA/STAMPA/FINITURA/LOGISTICA/SPEDIZIONE/ESTERNO

ValueObjects:
    CapacitaReparto(reparto, macchineAttive, oreMacchina)
    OrarioTurno(oraInizio, oraFine, turno)
        ::mattina() / ::pomeriggio() / ::notte()
        ::oreInGiorno(Carbon): [CarbonImmutable, CarbonImmutable]

Rules (pure functions):
    AssegnazioneFaseReparto::reparto(string $codiceFase): CodiceReparto
    CompatibilitaMacchinaReparto::reparto(string $codiceMacchina): ?CodiceReparto

Services:
    RepartoService::tutti() / bySlug() / byId() / byCodice()
    TurniService::turniPerReparto(CodiceReparto, Carbon): list<OrarioTurno>
    CapacitaService::oreDisponibiliGiorno(...): int
        emette RepartoCapacitaSuperata se allocazione > disponibili

Events:
    RepartoCapacitaSuperata(reparto, giorno, oreRichieste, oreDisponibili)
```

## Esempio

```php
use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Rules\AssegnazioneFaseReparto;
use App\Modules\Reparti\Services\CapacitaService;

// Risoluzione fase → reparto (pure)
$reparto = AssegnazioneFaseReparto::reparto('STAMPAXL106');
// CodiceReparto::STAMPA_OFFSET, ->id() = 11

// Capacità reparto in un giorno
$ore = app(CapacitaService::class)
    ->oreDisponibiliGiorno($reparto, now()); // 24h se feriale, 8h se sabato, 0 se festivo
```

## Ambiguità note

Il seeder `FasiCatalogoSeeder` referenzia "pseudo-reparti" che NON esistono
nella tabella `reparti` (12 righe canoniche):
  - "fustella piana" / "fustella cilindrica" → mappati su `FUSTELLA`
  - "tagliacarte"                            → mappato su `LEGATORIA`
  - "finitura digitale"                      → mappato su `DIGITALE`
  - "finestratura"                           → mappato su `PIEGAINCOLLA`

Quando il seeder `RepartiSeeder` verrà esteso (es. introducendo "tagliacarte"
come reparto autonomo), aggiornare:
  1. `App\Constants\RepartoId`
  2. `CodiceReparto` (cases + match)
  3. `AssegnazioneFaseReparto::mappa()`

## Dipendenze

- `App\Constants\RepartoId` (sorgente di verità ID interi)
- `App\Models\Reparto` (solo lettura via cache)
- Usato da: `Modules/Scheduling`, `Modules/Fasi`, dashboard owner.
