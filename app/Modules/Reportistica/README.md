# Modulo `Reportistica`

## Scopo

Aggregazioni dati cross-cutting per dashboard admin/owner: KPI, report ore,
panoramiche reparti, fustelle, esterne, produttività operatori.

Sostituisce ~600 righe di query SQL inline + collection-aggregation che
vivevano in `DashboardAdminController` (1579 LOC) e `DashboardOwnerController`
(1911 LOC).

Read-only: nessuna mutazione di stato. Il modulo legge `ordini`,
`ordine_fasi`, `fase_operatore`, `prinect_attivita`, `audit_logs` e
appoggia un layer di cache deterministico per ridurre il costo di
ri-aggregazione a ogni page-load.

## API Pubblica

```
KpiService
    ::tutti(): Collection<KpiCard>          // 6 KPI cached 5 min
    ::unico(TipoKpi $tipo): ?KpiCard

ReportOreService
    ::perCommessa($from, $to, ...): Collection<RigaReport>
    ::perTemplate($from, $to, ...): array{commesse, orePerReparto, fasi}

PanoramicaRepartiService
    ::overview(): Collection                // cached 10 min
    ::caricoEOre(): Collection

EsterneReportService
    ::commesseEsterne(): Collection         // cached 10 min

FustelleReportService
    ::overview(): array<codice, array<commessa, ...>>  // cached 10 min
    ::repartiFustellaIds(): list<int>

ProgressoCommesseService
    ::avanzamento(string $commessa): int
    ::isCompletata(string $commessa): bool
    ::avanzamentoBatch(iterable): Collection<string,int>

ProduttivitaOperatoriService
    ::topPerFasi(PeriodoReport, int): Collection
    ::performanceCompleta(PeriodoReport): Collection

ValueObjects:
    PeriodoReport(from, to, aggregazione, label)
    KpiCard(tipo, label, valore, delta, trend, unita)
    DatasetGrafico(labels, values, tipo, titolo)  // ::toChartJs()
    RigaReport(commessa, cliente, ore_prev, ore_eff, ...)

Enums:
    TipoKpi              // 6 KPI standard
    PeriodoAggregazione  // giorno/sett/mese/trim/sem/anno
    TipoGrafico          // line/bar/pie/doughnut

Rules (pure):
    EfficienzaRule::calcola(prev, eff)
    EfficienzaRule::badge(?eff)
    SforatureRule::sforate(Collection, tolleranza=0.20)
    PrioritaReportRule::score(consegna, sforata, manuale)

Cache:
    ReportCache::KEY_KPI / KEY_REPORT_ORE / KEY_PANORAMICA / ...
    ReportCache::TTL_KPI=300, TTL_REPORT_ORE=900, TTL_PANORAMICA=600
    ReportCache::invalidaTutto()
```

## Integrazione

I controller storici **iniettano** i Service via constructor:

```php
public function __construct(
    private readonly KpiService $kpi,
    private readonly PanoramicaRepartiService $panoramica,
    private readonly ReportOreService $reportOre,
    private readonly FustelleReportService $fustelle,
    private readonly EsterneReportService $esterne,
) {}
```

E il body dei metodi diventa una single-line:

```php
public function repartiOverview() {
    $data = $this->panoramica->overview();
    return view('owner.reparti_overview', compact('data', 'totReparti', 'opToken'));
}
```

## Cache Invalidation

`InvalidaReportistica` è registrato su `PhaseCompleted` in
`AppServiceProvider::boot()`. Quando un operatore termina una fase,
le 5 chiavi cache vengono pulite. Prossimo page-load le ricalcola.

## Vincoli

- Output JSON Chart.js INVARIATO (compatibilità frontend).
- URL routes INVARIATE.
- Schema DB INVARIATO (read-only).
- KPI 5 min, ReportOre 15 min, Panoramica 10 min.
