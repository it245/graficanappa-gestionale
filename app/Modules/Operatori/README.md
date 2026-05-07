# Modulo `Operatori`

## Scopo

Punto unico per i controlli di autorizzazione del MES. Combina:

- **Matrice ruolo -> permessi** (`PermessiMatrix`) — livello statico.
- **Regole contestuali** (`AssegnazioneFaseRule`) — es. un operatore può
  modificare solo fasi del proprio reparto.

Sostituisce gradualmente i check sparsi tipo
`Auth::user()->ruolo === 'admin'` nei Controller.

## API Pubblica

```
PermessiService::check(Operatore $op, Permesso $perm, ?Model $context = null): bool
PermessiService::authorize(Operatore $op, Permesso $perm, ?Model $context = null): void
                                                                  // throws AuthorizationException

RuoloOperatore::fromStringOrDefault(?string $raw): self           // enum
Permesso::ModificaFase, ::AvviaFase, ::TerminaFase, ::GestisciMagazzino, ...
```

**Ruoli:** `admin`, `owner`, `capo_reparto`, `operatore`, `prestampa`,
`spedizione`.

## Esempio

```php
use App\Modules\Operatori\Services\PermessiService;
use App\Modules\Operatori\Permessi\Permesso;

$svc = app(PermessiService::class);

// Check semplice (solo livello ruolo)
if ($svc->check($op, Permesso::ModificaImpostazioni)) { ... }

// Check contestuale: operatore + fase -> verifica reparto
if ($svc->check($op, Permesso::ModificaFase, $ordineFase)) { ... }
```

## Dipendenze

- `App\Models\Operatore`, `App\Models\OrdineFase`.
- Consumato da: tutti i Controller (al posto di `gate`/`policy` Laravel).
- Nessuna dipendenza esterna.
