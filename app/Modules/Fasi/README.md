# Modulo `Fasi`

## Scopo

Macchina a stati per `OrdineFase`. Centralizza:

- transizioni legali (`StateMachine/Transizioni`),
- pause con motivo testuale (`Rules/PausaRule`),
- audit log strutturato,
- dispatch di eventi di dominio (`FaseAvviata`, `FaseTerminata`).

Stati: `0`=non iniziata, `1`=pronto, `2`=avviato, `3`=terminato, `4`=consegnato.
Pause come stringa motivo. `EXT` = fase esterna (visualizzata in viola owner).

## API Pubblica

```
FaseTransitionService::transizione(OrdineFase $fase, int|string $nuovo, ?Operatore $op): bool
FaseTransitionService::pausa(OrdineFase $fase, string $motivo, ?Operatore $op): bool
FaseTransitionService::riprendi(OrdineFase $fase, ?Operatore $op): bool

Transizioni::validate(int|string $da, int|string $a): void   // throws FaseTransitionException
PausaRule::canPausa(OrdineFase $f): bool
PausaRule::canRiprendi(OrdineFase $f): bool
```

**Eventi:** `FaseAvviata(OrdineFase, ?Operatore, bool $isRipresa)`,
`FaseTerminata(OrdineFase, ?Operatore)`.

**Listeners attivi:** `PropagaFasiSuccessiveListener` (cascata),
`TracciaInizioFaseListener` (tempi), `NotificaCommessaCompletataListener`.

## Esempio

```php
use App\Modules\Fasi\Services\FaseTransitionService;

app(FaseTransitionService::class)->transizione($fase, nuovoStato: 2, operatore: $op);

// Pausa con motivo
app(FaseTransitionService::class)->pausa($fase, 'Cambio bobina', $op);

// Riprende: torna a 2
app(FaseTransitionService::class)->riprendi($fase, $op);
```

## Dipendenze

- `App\Models\OrdineFase`, `App\Models\Operatore` (Eloquent).
- DB transaction Laravel.
- Consumato da: tutti i Controller dashboard (operatore, owner, prestampa).
- Produce eventi consumati da: `Scheduling`, `Notifiche`, `Commessa`.
