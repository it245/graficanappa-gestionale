# Modulo `Carta`

## Scopo

Anagrafica articoli **carta** (non tutti gli articoli, solo quelli con prefisso
`02W.` del codice Onda). Lavora sopra `App\Models\Articolo` senza modificarne
schema o comportamento.

Funzionalità:

- ricerca/lookup per `cod_art` Onda;
- parsing del codice in 5 segmenti (TIPO.NOME.QUALITA.GRAMMATURA.FORMATO);
- conversione fogli ↔ kg;
- compatibilità con macchina (formato/grammatura ammessi).

## API Pubblica

```
AnagraficaCartaService::cerca(string $codArt): ?Articolo
AnagraficaCartaService::perFamiglia(FamigliaCarta $f): Collection
AnagraficaCartaService::ricerca(string $query): Collection

CodiceArticoloOnda::from(string $raw): self
    ->tipo(): string  ->nome(): string  ->qualita(): string
    ->grammatura(): int  ->formato(): Formato

ConversioneFogliKgRule::fogliToKg(int $fogli, int $grammatura, Formato $f): float
ConversioneFogliKgRule::kgToFogli(float $kg, int $grammatura, Formato $f): int

CompatibilitaMacchinaRule::isCompatibile(MacchinaInterface $m, Articolo $a): bool
```

**Enums:** `FamigliaCarta::{Patinata,Naturale,Cartone,Adesivo,...}`,
`TipoSuperficie::{Lucida,Opaca,Satinata}`.

## Esempio

```php
use App\Modules\Carta\Services\AnagraficaCartaService;
use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;

$art = app(AnagraficaCartaService::class)->cerca('02W.ALASKA.GC1.300.003');
$cod = CodiceArticoloOnda::from($art->cod_art);
echo $cod->grammatura(); // 300
```

## Dipendenze

- `App\Models\Articolo`.
- Sync Onda SOAP (orario) popola la tabella; questo modulo è solo lettura.
- Consumato da: `MagazzinoController`, `EtichettaGenerator`, telegram bot OCR.
