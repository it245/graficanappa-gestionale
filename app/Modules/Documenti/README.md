# Modulo `Documenti`

## Scopo

Generazione documenti del MES e sync Excel bidirezionale Prinect.
Pattern Generator: ogni `TipoDocumento` ha (o avrà) un suo
`DocumentoGeneratorInterface`.

Oggi solo `Etichetta` (Data Matrix, plain non GS1, A nel GTIN, qty 8 cifre
zero-padded — vedi memoria `project_codici_operatore.md`) ha implementazione
modulare. Schede produzione PDF e altri formati restano nei service legacy.

`ExcelSyncService` gestisce il file `dashboard_mes.xlsx` (lettura/scrittura
ogni 2 min via queue worker) per integrazione con Prinect.

## API Pubblica

```
DocumentoService::genera(TipoDocumento $tipo, array $context): string   // path file

interface DocumentoGeneratorInterface
    tipo(): TipoDocumento
    formato(): FormatoDocumento
    genera(array $context): string

EtichettaGenerator implements DocumentoGeneratorInterface
ExcelSyncService::syncIn(): int    // Excel -> DB
ExcelSyncService::syncOut(): int   // DB -> Excel
```

**Enums:** `TipoDocumento::{Etichetta,SchedaProduzione,Bolla,DDT}`,
`FormatoDocumento::{Pdf,Png,Xlsx}`.
**Rules:** `CodiceArticoloRule`, `GtinRule`.
**ValueObjects:** `MetadatiDocumento`.

## Esempio

```php
use App\Modules\Documenti\Services\DocumentoService;
use App\Modules\Documenti\Enums\TipoDocumento;

$path = app(DocumentoService::class)->genera(
    TipoDocumento::Etichetta,
    ['cod_art' => '02W.ALASKA.GC1.300.003', 'qty' => 1200, 'lotto' => 'L240312']
);
```

## Dipendenze

- `App\Http\Services\PrinectService` (sync Excel).
- libreria DataMatrix (es. `dnsbty/php-datamatrix`).
- Consumato da: `EtichettaController`, queue `CachePrinectInkJob`.
