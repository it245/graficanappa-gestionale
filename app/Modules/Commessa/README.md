# Modulo `Commessa`

## Scopo

Vista aggregata sulla commessa (insieme di `Ordine` con stesso codice base):

- stato derivato dalle fasi di tutti gli ordini (`StatoCommessa`);
- quantità totale richiesta vs consegnata;
- urgenza (giorni residui rispetto a `data_consegna`);
- dispatch eventi quando completata o consegnata.

Non modifica i model `Ordine` / `OrdineFase`: legge e aggrega.

## API Pubblica

```
CommessaService::getStatoAggregato(string $codiceCommessa): StatoCommessa
CommessaService::getQtaTotale(string $codiceCommessa): int
CommessaService::getQtaConsegnata(string $codiceCommessa): int
CommessaService::getOrdiniDellaCommessa(string $codice): Collection<Ordine>

ProgressoService::percentuale(string $codice): float            // 0..100
ProgressoService::fasiResidue(string $codice): Collection<OrdineFase>

StatoDerivatoRule::deriva(Collection $fasi): StatoCommessa      // pura

CodiceCommessa::from(string $raw): self                         // formato '0067164-26'
```

**Enums:** `StatoCommessa::{Bozza,InCorso,Completata,Consegnata,Sospesa}`.
**Eventi:** `CommessaCompletata`, `CommessaConsegnata`.

## Esempio

```php
use App\Modules\Commessa\Services\CommessaService;

$svc = app(CommessaService::class);
$stato = $svc->getStatoAggregato('0067164-26');     // StatoCommessa::InCorso
$tot   = $svc->getQtaTotale('0067164-26');          // 5000
$cons  = $svc->getQtaConsegnata('0067164-26');      // 2500
```

## Dipendenze

- `App\Models\Ordine`, `App\Models\OrdineFase`.
- Listener di `Fasi::FaseTerminata` per ricalcolare stato aggregato.
- Consumato da: `OwnerController`, `OperatoreController`, dashboard spedizione.
- Produce eventi consumati da: `Notifiche` (avviso completamento al cliente
  interno).
