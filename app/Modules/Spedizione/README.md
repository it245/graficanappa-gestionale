# Modulo `Spedizione`

## Scopo

Tracking BRT + gestione DDT MES + note consegne bidirezionali (owner ↔
spedizione). Il modulo non modifica `BrtService` legacy: lo usa.

- aggiorna campi cache (`brt_stato`, `brt_data_consegna`, ...) su `ddt_spedizioni`;
- dispatcha `SpedizioneInRitardo` quando `RitardoRule` scatta;
- esclude multi-spedizioni con esito BRT `-22` da email ritardi;
- gestisce note consegne con polling 15s lato dashboard.

## API Pubblica

```
TrackingService::aggiornaStato(DdtSpedizione $ddt): void
TrackingService::aggiornaTutti(): int                        // ritorna n. aggiornati

NoteConsegneService::aggiungi(string $contenuto, ?Operatore $autore): NoteConsegna
NoteConsegneService::nonLette(?Operatore $destinatario): Collection
NoteConsegneService::segnaLetta(int $noteId): void
```

**Enums:** `StatoSpedizione`, `EsitoBrt` (codici BRT 1..-22).
**ValueObjects:** `CodiceTracking`, `IndirizzoDestinazione`.
**Rules:** `RitardoRule::isInRitardo($ddt)`, `MultiSpedizioneRule`.
**Eventi:** `SpedizioneInRitardo` -> `NotificaSpedizioneInRitardoListener`.

## Esempio

```php
use App\Modules\Spedizione\Services\TrackingService;

// Singolo DDT
app(TrackingService::class)->aggiornaStato($ddt);

// Cron / queue: aggiorna tutti i DDT aperti
$n = app(TrackingService::class)->aggiornaTutti();
```

## Dipendenze

- `App\Http\Services\BrtService` (legacy, SOAP BRT).
- `App\Models\DdtSpedizione`, `App\Models\NoteConsegna`.
- Consumato da: `SpedizioneController`, `OwnerController`, queue jobs.
- Produce eventi consumati da: `Notifiche`.
