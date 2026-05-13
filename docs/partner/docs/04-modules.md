# 04. Moduli Funzionali

I 19 moduli DDD in `app/Modules/` orchestrano la business logic di Mossa 37. Ogni modulo è autonomo, ha contracts espliciti verso l'esterno e dipende da altri moduli solo via Eventi o Contracts.

## Audit
**Path:** `app/Modules/Audit/`
**Scopo:** Logging centralizzato di eventi business + compliance GDPR / Statuto Lavoratori art. 4.

- **Contracts:** `AuditSinkInterface`
- **Services:**
  - `AuditLogService::log()` — Registra evento auditato (azione, entità, prima/dopo) con sanitizzazione automatica dati sensibili
  - `AuditQueryService` — Read-side: ricerca log per utente/azione/modello
  - `ComplianceExportService::esportaPerOperatore()` / `::aggregatoSindacale()` — Export GDPR art. 15 + aggregato RSU pseudonimizzato
- **Adapters:** `DatabaseAuditSink`, `FileAuditSink`, `NullAuditSink`
- **Rules:** `DatiSensibiliRule` (mask password/token/Bearer/sk-*), `RitenzioneRule` (GDPR retention 3yr/2yr/7yr), `AccessoLogRule` (chi può leggere)
- **ValueObjects:** `AuditEvent`, `ContestoUtente`, `DiffPayload`
- **Enums:** `TipoAzione` (Create/Read/Update/Delete/Login/Logout/Export/Sync/Failed), `EntitaAudit`, `LivelloSicurezza` (Normale/Sensibile/Critico)
- **Listeners:** `LogFaseAvviata`, `LogFaseTerminata`, `LogCommessaCompletata`, `LogMovimentoMagazzino`, `LogLoginRiuscito`, `LogLoginFallito`

## Carta
**Path:** `app/Modules/Carta/`
**Scopo:** Anagrafica tipizzata carta (prefisso `02W.` Onda). Parser codice + conversione fogli↔kg + compatibilità macchina.

- **Services:** `AnagraficaCartaService` (`cerca`, `perFamiglia`, `ricerca`)
- **Rules:** `CompatibilitaMacchinaRule::isCompatibile()`, `ConversioneFogliKgRule` (`fogliToKg`, `kgToFogli`)
- **ValueObjects:** `CodiceArticoloOnda::from()` (parser regex 5 segmenti TIPO.NOME.QUALITA.GRAMMATURA.FORMATO), `Formato`
- **Enums:** `FamigliaCarta` (Patinata/Naturale/Cartone/Adesivo), `TipoSuperficie` (Lucida/Opaca/Satinata)

## Commessa
**Path:** `app/Modules/Commessa/`
**Scopo:** Vista aggregata (insieme Ordini stesso codice base): stato derivato, qta totale/consegnata, urgenza, dispatch eventi completamento.

- **Services:**
  - `CommessaService` (`getStatoAggregato`, `getQtaTotale`, `getQtaConsegnata`, `getOrdiniDellaCommessa`, `aggiornaCampo`)
  - `ProgressoService` (`percentuale`, `fasiResidue`)
- **Rules:** `StatoDerivatoRule::deriva()` (mapping stati fasi → StatoCommessa)
- **ValueObjects:** `CodiceCommessa::from()` (parser `NNNNNNN-AA`)
- **Enums:** `StatoCommessa` (Bozza/InCorso/Completata/Consegnata/Sospesa)
- **Events:** `CommessaCompletata`, `CommessaConsegnata`

## Documenti
**Path:** `app/Modules/Documenti/`
**Scopo:** Generazione documenti MES (Etichette DataMatrix con GTIN, schede produzione, DDT). Sync Excel bidirezionale Prinect.

- **Contracts:** `DocumentoGeneratorInterface`
- **Services:**
  - `DocumentoService::genera()` — Factory generazione con context
  - `EtichettaGenerator implements DocumentoGeneratorInterface` — DataMatrix A8 cifre zero-padded
  - `ExcelSyncService` (`syncIn`, `syncOut`) — `sync_data.xlsx` bidirezionale via queue
- **Rules:** `CodiceArticoloRule`, `GtinRule` (checksum)
- **ValueObjects:** `MetadatiDocumento` (codArt, articolo, lotto, qta, dataOrdinazione)
- **Enums:** `TipoDocumento` (Etichetta/SchedaProduzione/Bolla/DDT), `FormatoDocumento` (Pdf/Png/Xlsx)

## Fasi
**Path:** `app/Modules/Fasi/`
**Scopo:** State machine `OrdineFase`. Transizioni legali, pause con motivo, audit log strutturato, dispatch eventi dominio.

- **Services:** `FaseTransitionService` (`transizione`, `pausa`, `riprendi`)
- **Rules:** `PausaRule` (`canPausa`, `canRiprendi`), `TransizioneRule` (StateMachine registry)
- **Enums:** `StatoFase` — 0=NonIniziata, 1=Pronto, 2=Avviato, 3=Terminato, 4=Consegnato, 5=Esterna; pause = string motivo
- **Events:** `FaseAvviata(ordineFase, operatore, isRipresa)`, `FaseTerminata(ordineFase, operatore)`
- **Listeners:** `PropagaFasiSuccessiveListener` (cascata attivazione), `TracciaInizioFaseListener`, `NotificaCommessaCompletataListener`

## Fustelle
**Path:** `app/Modules/Fustelle/`
**Scopo:** Gestione tipizzata fustelle/cliché (anagrafica, stato magazzino, prelievi, note operative). Tabella nuova `fustelle` (distinta da legacy `cliche_anagrafica`).

- **Services:**
  - `FustellaService` (`cercaPerCodice` cache 1h, `crea`, `cambiaStato`)
  - `NoteFustellaService` (`aggiungi` append-only, `elenco`)
  - `PrelievoFustellaService` (`preleva`, `restituisci`)
- **Rules:** `FustellaCompatibileMacchina::eCompatibile()`, `ValidazioneCodiceFustella`
- **ValueObjects:**
  - `CodiceFustella::daStringa()` (`F-NNNNN-X`) + `::daLegacy()`
  - `DimensioneFustella::daStringa()` (`720x510x0.71`)
  - `NotaFustella::ora()` (timestamp `[Autore - dd/mm HH:MM]`)
- **Enums:** `TipoFustella` (PIANA/ROTATIVA/TRANCIATURA/RILIEVO, `::dalCodiceFase()`, `::macchineCompatibili()`), `StatoFustella` (PREPARAZIONE/PRONTA/IN_USO/ARCHIVIATA, `::puoTransitareA()`)
- **Events:** `FustellaPrelevata`, `FustellaRestituita`, `NotaFustellaAggiunta`

## Macchine
**Path:** `app/Modules/Macchine/`
**Scopo:** Registry in-memory 12 macchine fisiche + regole specifiche (turni, capacità, setup).

- **Contracts:** `MacchinaInterface` (`getId`, `getTurni`, `capacitaNominale`)
- **Services:** `CalcoloOreService::stima()`
- **Implementations:**
  - `RegoleXL106` — Heidelberg offset, 24h lun-ven
  - `RegoleJOH` — Caldo, 6-22 lun-ven
  - `RegoleBOBST` — 1 macchina, 2 config (rilievi/fustelle), cambio 1h
  - `RegolePiegaincolla` — 1 macchina, 3 config (PI01/PI02/PI03), cambio 1h
  - `RegoleStandard` — 6-22: STEL, PLAST, FIN, INDIGO, TAGLIO, LEGAT, ZUND, MGI
- **Registry:** `MacchinaRegistry::all()` / `::find()` / `::exists()` (cache 1h)

## Magazzino
**Path:** `app/Modules/Magazzino/`
**Scopo:** Orchestratore magazzino carta: registra movimenti, aggiorna giacenza stessa transazione, annullamento via movimento opposto, evento sottosoglia.

- **Services:**
  - `MovimentoService` (`registraMovimento` con transaction, `annullaMovimento` opposto logico, `storicoMovimenti` 90gg)
  - `GiacenzaService` (`aggiornaGiacenza` delta + causale, `giacenzaCorrente`)
- **Rules:** `MovimentoValiditaRule`, `SogliaSottoMinimoRule` (emette evento)
- **ValueObjects:** `QuantitaCarta` (fogli o kg)
- **Enums:** `TipoMovimento` (Carico/Scarico/Reso/Rettifica), `StatoGiacenza` (InStock/SottoSoglia/Critica)
- **Events:** `SottoSogliaEvento(cod_art, giacenza_attuale, soglia)` → `NotificaSottoSogliaListener`

## Notifiche
**Path:** `app/Modules/Notifiche/`
**Scopo:** Orchestratore notifiche con fan-out multi-canale routing per priorità (Bassa→BrowserPush, Media→Email, Alta→Telegram, Critica→Tutti).

- **Contracts:** `NotificaSenderInterface` (`canale`, `invia`)
- **Services:** `NotificaService` (`notificaOperatore`, `notifica`, `inviaSuCanale`)
- **Implementations:**
  - `TelegramSender` (via `irazasyed/telegram-bot-sdk`)
  - `EmailSender` (via Laravel Mail)
  - `BrowserPushSender` (via `minishlink/web-push`)
- **Rules:** `CanaleSceltaRule` (seleziona canali per priorità)
- **Templates:** `RitardoSpedizione`, `SottoSogliaCarta`
- **Enums:** `CanaleNotifica` (Telegram/Email/BrowserPush), `PrioritaNotifica` (Bassa/Media/Alta/Critica)

## Onda
**Path:** `app/Modules/Onda/`
**Scopo:** Integrazione ERP Onda (SQL Server) → MES (MySQL). Pattern architettura DDD modulare: isola coupling, prepara sostituzione futura.

- **Contracts:** `OndaErpInterface`
- **Adapters:** `OndaErpAdapter` (I/O SQL Server, 5 query estratte da `OndaSyncService` legacy)
- **Services:**
  - `OrdineSyncService::sync()` — Sync ordini produzione (`TipoDocumento=2`)
  - `ClienteSyncService` — Ricognizione clienti su `cliente_nome`
  - `CommessaSyncService::sync()` — Sync singola commessa (no filtro data)
- **ValueObjects:** `CodiceCommessaOnda::from()` (parser NNNNNNN-AA), `DocumentoOnda`
- **Enums:** `TipoDocumentoOnda` (OrdineProduzione=2, DdtVendita=3, DdtFornitore=7)
- **Events:** `OrdineSincronizzato(ordine_id, tipo, data)`, `ClienteSincronizzato(cliente_nome)`

## Operatori
**Path:** `app/Modules/Operatori/`
**Scopo:** Punto unico controlli autorizzazione MES. Matrice ruolo→permessi (statico) + regole contestuali (es. operatore modifica solo fasi reparto proprio).

- **Services:** `PermessiService` (`check` con context, `authorize` throws `AuthorizationException`)
- **Rules:** `AssegnazioneFaseRule`, `PermessiMatrix` (lookup statico ruolo→[Permesso])
- **Enums:**
  - `RuoloOperatore` (admin, owner, capo_reparto, operatore, prestampa, spedizione, owner_readonly, fiery_contatori)
  - `Permesso` (ModificaFase, AvviaFase, TerminaFase, GestisciMagazzino, ...)

## Presenze
**Path:** `app/Modules/Presenze/`
**Scopo:** Gestione presenze operatori basata NetTime (server presenze, share file system). Calcolo ore lavorate, assenze, compatibilità reparto. Art. 4 Statuto Lavoratori.

- **Contracts:** `TimbratureSourceInterface`
- **Adapters:** `NetTimeShareAdapter`, `ManualeAdapter` (in-memory fallback testing)
- **Services:**
  - `PresenzeService` — Stato giorno + lookup oggi
  - `TimbratureSyncService` — Sync file BKP ogni 5 min
  - `CalcoloOreService` — Ore giorno/settimana/mese + pause
  - `AssenzeService` — CRUD assenze (`presenze_assenze`)
- **Rules:**
  - `ValidazioneOrarioRule` (6-22 lun-ven, XL106 24h)
  - `CalcoloStraordinariRule` (>40h=25%, >48h=35%)
  - `CompatibilitaTurnoReparto`
- **ValueObjects:** `PeriodoPresenza`, `BadgeOperatore` (matricola 6 cifre), `OreLavorate`
- **Enums:** `TipoTimbratura` (IN/OUT/PAUSA/RIENTRO), `TipoAssenza` (FERIE/MALATTIA/PERMESSO/SCIOPERO...), `StatoPresenza` (PRESENTE/IN_PAUSA/ASSENTE)
- **Events:** `TimbraturaRegistrata`, `OperatoreNonTimbrato` (alert >30min), `StraordinariSuperati`

## Prinect
**Path:** `app/Modules/Prinect/`
**Scopo:** Integrazione Heidelberg Prinect Pressroom Manager (XL106). Pattern architettura DDD modulare verso `PrinectService` legacy + adapter `Stampa\PrinectAdapter`.

- **Contracts:** `PrinectApiInterface`
- **Adapters:** `PrinectHttpAdapter` (HTTP + Basic Auth)
- **Services:**
  - `PrinectJobsService` (`getWorkstepsStampa`, `stampaConfermata`, `jobIdToCommessa`, `commessaToJobId`)
  - `PrinectInkService::getConsumo()` — `inkConsumption` + cache 1h
  - `PrinectAccountingService::getTempi()` — Tempi avviamento/esecuzione da activities
  - `PrinectAutoTerminaService` (`deveTerminare` >4h inattiva, `deveRipristinare` se attività recenti)
- **ValueObjects:** `WorkstepId` (jobId, workstepId), `TempiStampa` (avviamento, esecuzione sec), `ConsumoInchiostro` (CMYK + spot grammi)
- **Enums:** `StatoWorkstep` (WAITING/RUNNING/COMPLETED/ABORTED)
- **Events:** `WorkstepAvviato`, `WorkstepCompleted(jobId, workstepId, tempiStampa)`

## Reparti
**Path:** `app/Modules/Reparti/`
**Scopo:** Tipizzazione 12 reparti (tabella `reparti`) + calcolo capacità/turni per scheduler Mossa 37 + dashboard owner. Logica pura (no DB write).

- **Services:**
  - `RepartoService` (`tutti`, `bySlug`, `byId`, `byCodice`, cache 1h)
  - `TurniService::turniPerReparto()` — Orari per CodiceReparto + giorno
  - `CapacitaService::oreDisponibiliGiorno()` — Emette `RepartoCapacitaSuperata`
- **Rules:** `AssegnazioneFaseReparto::reparto()` (pure: fase → CodiceReparto), `CompatibilitaMacchinaReparto::reparto()`
- **ValueObjects:** `CapacitaReparto` (reparto, macchineAttive, oreMacchina), `OrarioTurno` (oraInizio, oraFine, turno) + `::mattina()`/`::pomeriggio()`/`::notte()`
- **Enums:**
  - `CodiceReparto` (PRESTAMPA, STAMPA_OFFSET, FINITURA, LOGISTICA, SPEDIZIONE, ESTERNO, ...) con `::id()`, `::nome()`, `::tipo()`, `::oreSettimana()`
  - `TipoReparto` (PRESTAMPA, STAMPA, FINITURA, LOGISTICA, SPEDIZIONE, ESTERNO)
- **Events:** `RepartoCapacitaSuperata`

## Reportistica
**Path:** `app/Modules/Reportistica/`
**Scopo:** Aggregazioni cross-cutting (KPI, report ore, panoramiche reparti, fustelle, esterne, produttività operatori). Sostituisce ~600 righe di SQL inline da `DashboardAdminController` (1579 LOC) + `DashboardOwnerController` (1911 LOC). Read-only + cache deterministico.

- **Services:**
  - `KpiService` (`tutti`, `unico`) — 6 KPI cached 5 min
  - `ReportOreService` (`perCommessa`, `perTemplate`)
  - `PanoramicaRepartiService` (`overview`, `caricoEOre`) — cached 10 min
  - `EsterneReportService::commesseEsterne()` — cached 10 min
  - `FustelleReportService` (`overview`, `repartiFustellaIds`) — cached 10 min
  - `ProgressoCommesseService` (`avanzamento`, `isCompletata`, `avanzamentoBatch`)
  - `ProduttivitaOperatoriService` (`topPerFasi`, `performanceCompleta`)
- **Rules:** `EfficienzaRule` (`calcola`, `badge`), `SforatureRule::sforate()` (filtra >20% tolleranza), `PrioritaReportRule::score()`
- **ValueObjects:** `PeriodoReport`, `KpiCard`, `DatasetGrafico::toChartJs()`, `RigaReport`
- **Enums:** `TipoKpi` (6 standard), `PeriodoAggregazione` (giorno/sett/mese/trim/sem/anno), `TipoGrafico` (line/bar/pie/doughnut)
- **Cache:** `ReportCache::KEY_KPI` (TTL 5 min), `KEY_REPORT_ORE` (15 min), `KEY_PANORAMICA` (10 min)

## Scheduling
**Path:** `app/Modules/Scheduling/`
**Scopo:** Core progetto **Mossa 37**: ordinamento prioritario fasi dashboard operatore, con propagazione cascata. 4 livelli lessicografici (disponibilità, urgenza reale, batch affinity, sequenza ciclo).

- **Services:**
  - `PrioritaService` (`calcolaPriorita` → float ordinabile, `ordina` → Collection sorted DESC)
  - `PropagazioneService::propagaDopoTermine()` — Cascata attivazione su `FaseTerminata`
- **Contracts:** `SchedulerEngineInterface::schedule()`
- **Rules:**
  - `SequenzaFasiRule::puoIniziare()` — Fasi precedenti completate?
  - `UrgenzaRule::urgenza()` — Giorni residui (negativo = ritardo)
  - `BatchAffinityRule::calcolaBatchGroup()` — Setup compatibili ±5gg
  - `SetupChangeRule` — Cambio config BOBST/Piegaincolla = 1h
- **Enums:** `LivelloPriorita` (Disponibilita, Urgenza, Batch, Sequenza) con `::peso()`

## Spedizione
**Path:** `app/Modules/Spedizione/`
**Scopo:** Tracking BRT + gestione DDT MES + note consegne bidirezionali (owner↔spedizione). Non modifica `BrtService` legacy, lo usa. Cache `brt_stato`, `brt_data_consegna` su `ddt_spedizioni`. Esclude multi-spedizioni esito BRT `-22`. Note con polling 15s.

- **Services:**
  - `TrackingService` (`aggiornaStato` cache DDT, `aggiornaTutti` batch DDT aperti)
  - `NoteConsegneService` (`aggiungi`, `nonLette`, `segnaLetta`)
  - `DdtSyncService` (sync vendita), `DdtFornitoreSyncService`, `DdtLavorazioniSyncService`
- **Rules:** `RitardoRule::isInRitardo()` (scatta evento), `MultiSpedizioneRule` (filtra `-22`)
- **ValueObjects:** `CodiceTracking` (BRT), `IndirizzoDestinazione` (via/cap/comune/provincia)
- **Enums:** `StatoSpedizione` (Preparazione/Spedito/InTransito/Consegnato/Fallito), `EsitoBrt` (1, -22, ...)
- **Events:** `SpedizioneInRitardo(ddt_id, giorni_ritardo)` → `NotificaSpedizioneInRitardoListener`

## Stampa
**Path:** `app/Modules/Stampa/`
**Scopo:** Astrazione comune sopra Prinect (Heidelberg XL106 offset) + Fiery (HP Indigo digitale). Espone `StampaIntegrationInterface` unico. Service legacy (`PrinectService`, `FieryService`) restano in Anti-Corruption Layer Adapters.

- **Contracts:** `StampaIntegrationInterface` (`getId`, `getJobInStampa`, `getCopieFatte`, `getTempiAvviamentoEsecuzione`)
- **Adapters:** `PrinectAdapter implements StampaIntegrationInterface`, `FieryAdapter implements StampaIntegrationInterface`
- **Services:** `StampaQuotaService::quotaResidua()` (tiratura − fatte)
- **Rules:** `AvviamentoRule` (tempi setup), `CalcoloOreStampaRule`
- **ValueObjects:** `Tiratura` (numero copie)
- **Enums:** `TipoStampa` (Offset/Digitale)

---

## Pattern comuni

### Eventi cross-modulo

Tutti i moduli comunicano via Laravel Events:

| Evento | Modulo dispatcher | Listener (modulo) |
|---|---|---|
| `FaseAvviata` | Fasi | Tracciamento (Audit) |
| `FaseTerminata` | Fasi | PropagazioneFasiSuccessive (Scheduling), NotificaCommessaCompletata (Commessa) |
| `CommessaCompletata` | Commessa | Notifica spedizione + audit |
| `OrdineSincronizzato` | Onda | Excel sync trigger |
| `WorkstepCompleted` | Prinect | Update fase MES |
| `SottoSogliaEvento` | Magazzino | NotificaSottoSoglia (Notifiche) |
| `SpedizioneInRitardo` | Spedizione | NotificaSpedizioneInRitardo (Notifiche) |
| `FustellaPrelevata` / `Restituita` | Fustelle | Audit |
| `TimbraturaRegistrata` | Presenze | Calcolo straordinari + audit |
| `OperatoreNonTimbrato` | Presenze | NotificaAlert (Notifiche) |
| `StraordinariSuperati` | Presenze | NotificaAlert + log |
| `RepartoCapacitaSuperata` | Reparti | Notifica owner |

### Adapter intercambiabili

Pattern adottato per tutte le integrazioni esterne:

```
Modulo/Contracts/XxxInterface
    ↑ implements
Modulo/Adapters/XxxHttpAdapter (prod)
Modulo/Adapters/XxxMockAdapter (test)
```

In `bootstrap/providers.php` / `AppServiceProvider`:

```php
$this->app->bind(OndaErpInterface::class, OndaErpAdapter::class);
```

In testing: swap con `MockOndaAdapter` o Mockery.

### ValueObjects con factory

VO immutabili con costruttore privato + factory `::from()`:

```php
final readonly class CodiceCommessa {
    private function __construct(public string $value) {}
    public static function from(string $raw): self { /* validation */ }
}
```

### Rules pure

Rules sono **funzioni pure** testabili in isolamento (no DB, no Http):

```php
final class UrgenzaRule {
    public static function urgenza(Carbon $dataConsegna, Carbon $oggi): float
    {
        return $oggi->diffInDays($dataConsegna, false);  // negativo = ritardo
    }
}
```

### Permessi centralizzati

Tutti i moduli usano `PermessiService` (modulo Operatori) per autorizzazione:

```php
app(PermessiService::class)->authorize(
    $operatore,
    Permesso::ModificaFase,
    ['fase' => $fase]
);
```

### Cache standardizzata

Moduli read-heavy (Reportistica, Fustelle, Reparti) usano cache key strutturate:

```php
ReportCache::KEY_KPI . ':' . hash('xxh64', json_encode($params))
```

TTL configurabili per tipo (KPI 5min, panoramica 10min, report ore 15min).

### architettura DDD modulare backward compat

Wrapper compatibilità mantenuti per:
- Comandi artisan cron esistenti
- Controller legacy
- Script standalone (`import_commessa.php`, ecc.)
- Reflection-based callers

Esempio: `OndaSyncService::sincronizza()` chiama internamente `app(OrdineSyncService::class)->sync()`.
