# Audit God Class — 08/05/2026

**Branch**: `def2.0` · **Modalità**: read-only · **Scope**: `app/`

## Top 25 file per dimensione

| # | File | Righe | Classification |
|---|---|---|---|
| 1 | `Http/Controllers/DashboardOwnerController.php` | 1677 | God Class urgente |
| 2 | `Http/Controllers/DashboardAdminController.php` | 1550 | God Class urgente |
| 3 | `Http/Services/PrinectSyncService.php` | 977 | God Class urgente |
| 4 | `Services/SchedulerService.php` | 696 | God Class (16 metodi multi-resp) |
| 5 | `Http/Controllers/TelegramWebhookController.php` | 653 | God Class (16 handler in 1 file) |
| 6 | `Http/Controllers/FieryController.php` | 649 | God Class |
| 7 | `Http/Controllers/DashboardSpedizioneController.php` | 614 | God Class |
| 8 | `Http/Controllers/PrinectController.php` | 614 | God Class |
| 9 | `Http/Services/FieryService.php` | 603 | Service medio |
| 10 | `Http/Services/ExcelSyncService.php` | 600 | God Class |
| 11 | `Http/Controllers/ProduzioneController.php` | 528 | Service medio |
| 12 | `Http/Services/FierySyncService.php` | 495 | Service medio |
| 13 | `Imports/OrdiniImport.php` | 464 | Service medio |
| 14 | `Modules/Fasi/Services/FaseTransitionService.php` | 455 | Service medio |
| 15 | `Console/Commands/ExportPresenzeExcel.php` | 443 | Service medio |

## God Class urgente (>800 righe)

### DashboardOwnerController.php (1677 righe, 28 metodi pubblici, 84 branch)
- Responsabilità mescolate: panoramica reparti, dettaglio commessa, fasi terminate, sync Onda, scheduling, audit log, cliche, prototipo, esterne, fustelle, report ore, ricalcola stati
- Modulo target: `Modules/Dashboard/Owner/{Panoramica,Commesse,Scheduling,Cliche,Audit}Controller.php` + `OwnerQueryService`
- Estraibile: ~900 righe (panoramica reparti 153-309, scheduling 1255-1424, cliche 1505-1670, fasi terminate 723-897, sync Onda 1093-1181)

### DashboardAdminController.php (1550 righe, 23 metodi pubblici, 55 branch)
- Responsabilità: CRUD reparti, KPI calcolo, report direzione + Excel, report Prinect, EAN, turni, audit log, cruscotto
- Modulo target: spostare KPI+report in `Modules/Reportistica/Services/{KpiCalculator,DirezioneReport,PrinectReport}Service`
- Estraibile: ~700 righe (`calcolaKpiPeriodo` 732-1021, `calcolaKpiPrinect` 1140-1344, `report*` 1022-1407)

### PrinectSyncService.php (977 righe, 14 metodi, 79 branch)
- Responsabilità: sincronizza attività + aggiorna fogli + ripristina fasi attive + termina abbandonate + match operatori + completamento check + distribuzione attività
- Modulo target: `Modules/Prinect/Services/` (esistono già autotermina/ink/accounting)
- Estraibile: ~500 righe (auto-terminazione 797-910, matchOperatori 911-fine, aggiornaFogliCommessa 235-304)

## Duplicazioni rilevate (DRY violations)

| Pattern | Occorrenze | File principali | Suggerimento |
|---|---|---|---|
| `DB::table('ordine_fasi')` raw | 30 in 9 file | DashOwner(4), Scheduler(4), SchedulerExport(6), OrdineSync(7), Kiosk(4) | Repository `OrdineFaseRepository` o Eloquent `OrdineFase::query()`. Le raw query bypassano `OrdineFaseObserver` + audit |
| `Log::*` errori HTTP/sync | 37+ in 15 file | Services/Api/*, Http/Services/* | Wrapper `LoggedHttpClient` per API esterne |
| `AuditLog::create / audit_log` raw | 22 in 14 file | Coesistono `Services/AuditService` + `Modules/Audit/Services/AuditLogService` | Migrare tutti su `Modules/Audit/Services/AuditLogService` e deprecare `Services/AuditService` |
| `->update/insert/delete` raw | 33+ in 5 file | Scheduler(4), DashAdmin(3), ExcelSync(3) | Repository pattern con audit hook automatico |

## Complessità ciclomatica alta

| File | Branch | Note |
|---|---|---|
| DashboardOwnerController | 84 in 1677 righe | Concentrate in `repartiOverview`, `dettaglioCommessa`, `scheduling` |
| PrinectSyncService | 79 in 977 righe | `aggiornaFasi`, `distribuisciAttivitaTraFasi`, `controllaCompletamentoPrinect` |
| DashboardAdminController | 55 in 1550 righe | `calcolaKpiPeriodo` (290 righe), `calcolaKpiPrinect` (205 righe) |
| ExcelSyncService | 600 righe statiche | `importFromExcel` (340 righe in 1 metodo) |

## Raccomandazioni prossima sessione

1. **Spezzare DashboardOwnerController**: estrarre prima `cliche*` (140 righe) + `scheduling*` (170 righe), già isolati. Quick win basso rischio.
2. **Migrare calcolo KPI da DashboardAdminController** in `Modules/Reportistica/Services/KpiCalculatorService` (scaffolding esistente). Riduce 700 righe, abilita cache.
3. **Centralizzare scritture `ordine_fasi`** in `OrdineFaseRepository`. Le 30 raw query bypassano observer + audit: rischio integrità dati.
4. **Deprecare `Services/AuditService`** in favore di `Modules/Audit/Services/AuditLogService`. Doppio entry point causa audit gap silenzioso.
5. **ExcelSyncService**: spezzare in `ExcelExporter` + `ExcelImporter` + `ExcelDiffDetector` + `ExcelDateParser`. `importFromExcel` (340 righe) è il blocco più complesso del codebase.

## Quick wins immediati (basso rischio)

- Eliminare `$fasiInfo` privato in DashboardOwnerController (143 righe duplicate di `config/fasi_ore.php`)
- Eliminare `repartiFustellaIds()` privato (10 righe duplicate di `FustelleReportService`)
- Stima: −150 righe immediate, zero rischio
