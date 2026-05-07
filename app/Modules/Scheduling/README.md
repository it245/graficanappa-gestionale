# Modulo `Scheduling`

## Scopo

Cuore del **progetto Mossa 37**: ordinamento prioritario delle fasi nella
dashboard operatore, con propagazione cascata quando una fase termina.

Combina 4 livelli (lessicograficamente dominanti) in un unico punteggio
scalare ordinabile DESC:

1. **Disponibilità** (sequenza fasi precedenti completate)
2. **Urgenza reale** (giorni residui vs `data_consegna`)
3. **Batch affinity** (raggruppa setup compatibili entro ±5gg)
4. **Sequenza ciclo** (10 stampa offset → 11 dig → 20 plast → ... → 999 spedizione)

I pesi vivono in `LivelloPriorita::peso()` per tarature isolate.

## API Pubblica

```
PrioritaService::calcolaPriorita(OrdineFase $fase): float
PrioritaService::ordina(Collection $fasi): Collection

PropagazioneService::propagaDopoTermine(OrdineFase $faseTerminata): void

interface SchedulerEngineInterface
    schedule(Collection $fasi): Collection                  // sorted DESC
```

**Rules pure** (no DB):
- `SequenzaFasiRule::puoIniziare(OrdineFase): bool`
- `UrgenzaRule::urgenza(OrdineFase): float`  (giorni residui, neg=ritardo)
- `BatchAffinityRule::calcolaBatchGroup(OrdineFase): string`
- `SetupChangeRule` (cambio config BOBST/Piegaincolla = 1h).

**Enums:** `LivelloPriorita::{Disponibilita,Urgenza,Batch,Sequenza}`.

## Esempio

```php
use App\Modules\Scheduling\Services\PrioritaService;

$fasi = OrdineFase::where('reparto', 'stampa')->whereIn('stato',[0,1,2])->get();
$ordinate = app(PrioritaService::class)->ordina($fasi);
// dashboard operatore -> $ordinate
```

## Dipendenze

- `App\Models\OrdineFase`, `App\Models\Ordine`.
- `App\Modules\Macchine` (capacità nominale per stima ore).
- Consumato da: `OperatoreController` (dashboard), `OptimizeScheduleJob`.
- Listener di `Fasi::FaseTerminata` -> `PropagazioneService`.
