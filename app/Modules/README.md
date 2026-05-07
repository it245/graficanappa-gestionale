# app/Modules — Foundation modulare DDD

Pacchetti di dominio del MES Grafica Nappa. Branch: `def2.0`.

> Per dettagli architetturali (diagrammi, convenzioni, Strangler Fig, test
> strategy) vedi **[../docs/ARCHITECTURE.md](../../docs/ARCHITECTURE.md)**.

## Indice moduli

| Modulo | Scopo (1 riga) | README |
|---|---|---|
| `Macchine` | Registry macchine fisiche (XL106, JOH, BOBST, ...) + regole turni | [Macchine/README.md](Macchine/README.md) |
| `Fasi` | State machine `OrdineFase` (0..4 + pause + EXT) | [Fasi/README.md](Fasi/README.md) |
| `Operatori` | Matrice permessi per ruolo + check contestuali | [Operatori/README.md](Operatori/README.md) |
| `Stampa` | Adapter Prinect (XL106) + Fiery (Indigo) dietro contract comune | [Stampa/README.md](Stampa/README.md) |
| `Magazzino` | Movimenti carico/scarico/rettifica + giacenza + audit | [Magazzino/README.md](Magazzino/README.md) |
| `Spedizione` | Tracking BRT, ritardi, multi-spedizione, note consegne | [Spedizione/README.md](Spedizione/README.md) |
| `Scheduling` | Mossa 37: priorità 4-livelli + propagazione cascata fasi | [Scheduling/README.md](Scheduling/README.md) |
| `Documenti` | Generazione etichette Data Matrix + sync Excel Prinect | [Documenti/README.md](Documenti/README.md) |
| `Notifiche` | Fan-out Telegram / Email / BrowserPush per priorità | [Notifiche/README.md](Notifiche/README.md) |
| `Carta` | Anagrafica articoli `02W.*` + parsing cod_art Onda + conversioni | [Carta/README.md](Carta/README.md) |
| `Commessa` | Vista aggregata commessa: stato derivato, qty, urgenza | [Commessa/README.md](Commessa/README.md) |

## Quick start — usare un modulo da Controller

I moduli si risolvono via container Laravel (DI nel costruttore o `app(...)`).

```php
namespace App\Http\Controllers;

use App\Modules\Fasi\Services\FaseTransitionService;
use App\Modules\Operatori\Permessi\Permesso;
use App\Modules\Operatori\Services\PermessiService;
use App\Models\OrdineFase;

final class FaseController extends Controller
{
    public function __construct(
        private readonly FaseTransitionService $fasi,
        private readonly PermessiService $permessi,
    ) {}

    public function avvia(int $faseId)
    {
        $fase = OrdineFase::findOrFail($faseId);
        $op   = auth()->user();

        if (! $this->permessi->check($op, Permesso::ModificaFase, $fase)) {
            abort(403);
        }

        $this->fasi->transizione($fase, nuovoStato: 2, operatore: $op);

        return back()->with('ok', 'Fase avviata');
    }
}
```

Il controller resta **thin**: parsing request + auth + delega. La business
logic (validazione transizione, audit, eventi) è nel modulo.

## Aggiungere un nuovo modulo

Template struttura. Crea sotto `app/Modules/<Nome>/`:

```
<Nome>/
├── README.md           # Scopo / API / Esempio / Dipendenze (max 80 righe)
├── Contracts/          # opz. interfacce verso esterno/legacy
├── Adapters/           # opz. implementazioni dei contracts
├── Services/           # 1 file = 1 use case orchestrato
├── Rules/              # logica pura, niente DB
├── Enums/              # PHP 8.1 backed enum
├── Events/             # readonly + Dispatchable
├── Listeners/          # registrati in EventServiceProvider
├── ValueObjects/       # readonly + factory from(...)
└── Exceptions/         # eccezioni di dominio
```

Checklist nuovo modulo:

1. `declare(strict_types=1)` + `final class` + namespace `App\Modules\<Nome>\...`.
2. Niente Eloquent dentro `Rules/` (devono essere unit-testabili).
3. Eventi al passato (`FaseTerminata`, non `TerminaFase`).
4. Registra Listener in `app/Providers/EventServiceProvider.php`.
5. Se il modulo wrappa un service legacy: usa `Contracts/` + `Adapters/`,
   **non modificare** il service legacy.
6. Test in `tests/Unit/Modules/<Nome>/` e `tests/Feature/Modules/<Nome>/`.
7. Aggiorna questo `README.md` + crea `<Nome>/README.md`.

## Cosa **NON** fare

- Non importare `App\Http\Controllers` da un modulo (dipendenza
  unidirezionale).
- Non usare `Auth::user()` / `request()` dentro un Service: passali come
  parametri (`?Operatore $op`).
- Non spostare codice legacy dentro un modulo "in massa": usa Strangler Fig
  (vedi `docs/ARCHITECTURE.md`).
