# Modulo `Magazzino`

## Scopo

Orchestratore unico per la gestione magazzino carta:

- registra movimenti `carico` / `scarico` / `reso` / `rettifica`;
- aggiorna giacenza in **stessa transazione** del movimento;
- mantiene `giacenza_dopo` per audit trail;
- annullamento via movimento opposto (mai DELETE);
- evento `SottoSogliaEvento` -> notifica responsabile.

> **Vincolo importante:** lo scarico di carta **NON** passa da Telegram
> (vedi memoria `feedback_magazzino_scarico.md`). Solo prelievo da MES web.

## API Pubblica

```
MovimentoService::registraMovimento(
    string $codArt, TipoMovimento $tipo, float $quantita,
    string $causale, array $meta = []
): MagazzinoMovimento

MovimentoService::annullaMovimento(int $id, string $motivo): bool
MovimentoService::storicoMovimenti(string $codArt, int $giorni = 90): Collection

GiacenzaService::aggiornaGiacenza(string $codArt, float $delta, TipoMovimento $t, string $causale): void
GiacenzaService::giacenzaCorrente(string $codArt): int
```

**Enums:** `TipoMovimento::{Carico,Scarico,Reso,Rettifica}`, `StatoGiacenza`.
**ValueObjects:** `QuantitaCarta`.
**Eventi:** `SottoSogliaEvento` -> `NotificaSottoSogliaListener`.

## Esempio

```php
use App\Modules\Magazzino\Services\MovimentoService;
use App\Modules\Magazzino\Enums\TipoMovimento;

app(MovimentoService::class)->registraMovimento(
    codArt: '02W.ALASKA.GC1.300.003',
    tipo: TipoMovimento::Scarico,
    quantita: 1200,
    causale: 'PRODUZIONE',
    meta: ['commessa' => '66575', 'fase' => 'STAMPA XL', 'operatore_id' => 17],
);
```

## Dipendenze

- `App\Models\MagazzinoArticolo`, `App\Models\MagazzinoMovimento`.
- DB transaction Laravel + `lockForUpdate` per concorrenza annulli.
- Consumato da: `MagazzinoController`, telegram bot (carico OCR bolla).
- Produce eventi consumati da: `Notifiche`.
