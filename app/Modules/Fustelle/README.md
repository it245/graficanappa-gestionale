# Modulo `Fustelle`

## Scopo

Gestione tipizzata di fustelle / cliché (anagrafica, stato magazzino, prelievi,
note operative). Centralizza logica oggi sparsa in `DashboardOwnerController`
e nelle Blade `owner/dettaglio_commessa` + `operatore/dashboard` (box viola
"Note Fustelle Mirko" introdotto nella sessione 20 marzo 2026).

## API Pubblica

```
TipoFustella::{PIANA, ROTATIVA, TRANCIATURA, RILIEVO}
TipoFustella::dalCodiceFase(string $cod): self
TipoFustella::macchineCompatibili(): list<string>

StatoFustella::{PREPARAZIONE, PRONTA, IN_USO, ARCHIVIATA}
StatoFustella::puoTransitareA(self): bool

CodiceFustella::daStringa('F-12345-A'): self        // throws su input invalido
CodiceFustella::provaDaStringa(string): ?self
CodiceFustella::daLegacy('FS0291'): ?string         // riconosce codici legacy

DimensioneFustella::daStringa('720x510x0.71'): self
NotaFustella::ora('Mirko', 'cambiata lama')->format()
    // → "[Mirko - 23/04 14:30] cambiata lama"

FustellaService::cercaPerCodice(string): ?Fustella    // cache 1h
FustellaService::crea(CodiceFustella, TipoFustella, array): Fustella
FustellaService::cambiaStato(Fustella, StatoFustella): Fustella   // state-machine

NoteFustellaService::aggiungi(OrdineFase, $autore, $testo): NotaFustella
NoteFustellaService::elenco(OrdineFase): list<array{autore,timestamp,testo}>

PrelievoFustellaService::preleva(Fustella, Operatore, Ordine): ?string
PrelievoFustellaService::restituisci(Fustella, Operatore, ?$nuovaPosizione): void

FustellaCompatibileMacchina::eCompatibile(TipoFustella, $macchinaId): bool
ValidazioneCodiceFustella  // ValidationRule Laravel
```

## Schema DB

Tabella **`fustelle`** (nuova, distinta da `cliche_anagrafica` legacy):

| Colonna               | Tipo            | Note                      |
|-----------------------|-----------------|---------------------------|
| id                    | bigint PK       |                           |
| codice                | varchar(20) UNQ | formato `F-NNNNN-X`       |
| tipo                  | enum            | PIANA/ROTATIVA/TRANC./RILIEVO |
| stato                 | enum            | default PREPARAZIONE      |
| dimensione_mm_x/y     | uint nullable   |                           |
| spessore_mm           | decimal(5,2)    |                           |
| posizione_magazzino   | varchar(50)     |                           |
| note                  | text            |                           |
| timestamps            | -               |                           |

**Schema `ordine_fasi.note`**: NON modificato — le note fustella vengono
appese al campo esistente con prefisso `[Autore - dd/mm HH:MM]` per preservare
storico (no sovrascrittura).

## Note di design

- **Codice canonico vs legacy**: il dominio MES usa storicamente
  `FS####`/`KS####` (vedi `App\Helpers\FustellaResolver`). Il VO
  `CodiceFustella` adotta il **nuovo formato** `F-NNNNN-X` (con revisione)
  per la nuova tabella `fustelle`. La conversione/riconoscimento dai codici
  legacy è disponibile via `CodiceFustella::daLegacy()`. Il cablaggio del
  vecchio formato è fuori scope di questo step.
- **Cache lookup**: 1h, invalidata su `aggiorna()` / `cambiaStato()` / `crea()`.
- **Persistenza prelievi**: gli eventi `FustellaPrelevata`/`FustellaRestituita`
  sono i punti di estensione per audit trail (listener applicativo, tabella
  dedicata) — non gestita in questo step.
- **State-machine**: `StatoFustella::puoTransitareA()` blocca transizioni
  inconsistenti (es. ARCHIVIATA → IN_USO).

## Cablaggio attuale

Il cablaggio nel `DashboardOwnerController` e nelle view operatore/owner è
**fuori scope** (prossimo step). Il box "Note Fustelle Mirko" continua a
funzionare via `OrdineFase::note` come oggi.

## Test

- `tests/Unit/Fustelle/CodiceFustellaTest.php` — parsing regex `F-NNNNN-X`.
- `tests/Unit/Fustelle/NoteFustellaServiceTest.php` — append note + storico.
