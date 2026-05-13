# 07. Flussi

Sequence diagrams + spiegazione step-by-step dei flussi business critici di Mossa 37.

---

## Flusso 1: Sync Onda ERP → MES

Frequenza: **ogni ora** (cron `php artisan onda:sync`).

```mermaid
sequenceDiagram
    participant Cron
    participant Wrapper as OndaSyncService (legacy)
    participant Sync as OrdineSyncService
    participant Adapter as OndaErpAdapter
    participant OndaDB as SQL Server Onda
    participant MySQL
    participant Events

    Cron->>Wrapper: php artisan onda:sync
    Wrapper->>Sync: sync()
    Sync->>Adapter: getOrdiniDal(now-7gg)
    Adapter->>OndaDB: SELECT ATTDocTeste t<br/>JOIN PRDDocTeste p<br/>JOIN PRDDocFasi f<br/>OUTER APPLY ATTDocRighe (carta)<br/>OUTER APPLY ATTDocRighe TipoRiga=1 (desc live)<br/>OUTER APPLY PRDDocRighe (costo materiali)<br/>OUTER APPLY OC_ATTDocRigheExt (supporto)
    OndaDB-->>Adapter: righe
    Adapter-->>Sync: array
    Sync->>Sync: raggruppa per commessa+cod_art+desc

    loop per ogni gruppo
        Sync->>MySQL: lookup Ordine (commessa+cod_art+desc)
        alt non trovato
            Sync->>MySQL: fallback lookup commessa+cod_art
        end
        alt esistente
            Sync->>MySQL: update campi (cliente, data, carta, ecc.)
            Sync->>MySQL: update descrizione se diversa
        else nuovo
            Sync->>MySQL: create Ordine + fase BRT1 auto
        end
        Sync->>MySQL: upsert OrdineFase (mapping reparto via RepartoService)
        Sync->>Events: dispatch OrdineSincronizzato
    end

    Sync-->>Wrapper: result {ordini_creati, aggiornati, fasi_create}
    Wrapper-->>Cron: log + excel:sync trigger
```

**Note operative:**
- MES preferisce `ATTDocRighe.Descrizione` (commerciale live) su `OC_Descrizione` (PRD stale, può essere obsoleta dopo revisioni OC cliente)
- Non sovrascrive `data_prevista_consegna` se modificata manualmente nel MES
- Non sovrascrive `cliente_nome` se modificato manualmente
- Fase BRT1 (spedizione) viene auto-aggiunta per ogni nuova commessa con priorità 96
- Comando singola commessa: `php artisan onda:sync 0067339-26` (no filtro data)

**File:** `app/Modules/Onda/Services/OrdineSyncService.php`, `CommessaSyncService.php`, `Adapters/OndaErpAdapter.php`.

---

## Flusso 2: Ciclo fase operatore (Avvia → Pausa → Termina)

Attori: operatore (tablet), `ProduzioneController`, `FaseTransitionService`.

```mermaid
sequenceDiagram
    participant Op as Operatore (tablet)
    participant UI as /operatore/dashboard
    participant Ctrl as ProduzioneController
    participant Svc as FaseTransitionService
    participant DB as MySQL
    participant Events
    participant Listener as PropagaFasiSuccessive

    Op->>UI: card commessa stato=1 Pronto
    Op->>UI: click Avvia
    UI->>Ctrl: POST /produzione/avvia {faseId, operatoreId}
    Ctrl->>Svc: transizione(fase, Avviato)
    Svc->>Svc: PausaRule.canTransitare()
    Svc->>DB: UPDATE ordine_fasi SET stato=2, data_inizio=NOW()
    Svc->>Events: FaseAvviata(fase, op)
    Events-->>Ctrl: ok
    Ctrl-->>UI: json success

    Note over Op: ...lavoro in corso...
    Op->>UI: input qta_prod + scarti
    UI->>Ctrl: POST /produzione/aggiorna-campo
    Ctrl->>DB: UPDATE ordine_fasi.qta_prod = X

    Op->>UI: click Pausa (motivo: cambio materiale)
    UI->>Ctrl: POST /produzione/pausa {motivo}
    Ctrl->>Svc: pausa(fase, "cambio materiale")
    Svc->>DB: stato='cambio materiale'
    Svc->>DB: incrementa pivot secondi_pausa

    Op->>UI: click Riprendi
    UI->>Ctrl: POST /produzione/riprendi
    Ctrl->>Svc: riprendi(fase)
    Svc->>DB: stato=2 Avviato

    Op->>UI: click Termina
    UI->>Ctrl: POST /produzione/termina
    Ctrl->>Svc: transizione(fase, Terminato)
    Svc->>DB: stato=3, data_fine=NOW()
    Svc->>Events: FaseTerminata(fase, op)

    Events->>Listener: handle FaseTerminata
    Listener->>DB: SELECT fasi_successive (config sequenza_fasi)
    loop fasi successive
        Listener->>DB: UPDATE stato=1 (Pronto) se prec terminate
        Listener->>DB: ricalcola priorita_m37
    end
    Listener->>Events: CommessaCompletata se tutte fasi >= 3
```

**Stati `OrdineFase.stato`:**
- 0 = NonIniziata (caricato)
- 1 = Pronto
- 2 = Avviato
- 3 = Terminato
- 4 = Consegnato
- 5 = Esterna
- stringa = Pausa con motivo

**File:** `app/Http/Controllers/ProduzioneController.php`, `app/Modules/Fasi/Services/FaseTransitionService.php`, `app/Modules/Scheduling/Services/PropagazioneService.php`.

---

## Flusso 3: Generazione DDT spedizione

```mermaid
sequenceDiagram
    participant Sped as Spedizione
    participant UI as /spedizione/dashboard
    participant Ctrl as DashboardSpedizioneController
    participant Svc as DdtSyncService
    participant PdfSvc as DdtPdfService
    participant BRT as BrtService
    participant DB as MySQL
    participant Notif as NotificaService

    Sped->>UI: vede commessa "Da consegnare" (tutte fasi prod terminate)
    Sped->>UI: click "Consegna Totale"
    UI->>Ctrl: POST /spedizione/invio {faseId, tipo: totale}
    Ctrl->>DB: INSERT ddt_spedizioni (numero auto)
    alt vettore = BRT
        Ctrl->>BRT: prepareSoap XML
        BRT-->>Ctrl: shipment_id, segnacollo
        Ctrl->>DB: UPDATE ddt_spedizioni SET segnacollo_brt=X
    end
    Ctrl->>PdfSvc: genera DDT PDF
    PdfSvc-->>Ctrl: PDF storage path
    Ctrl->>DB: UPDATE ordine_fasi BRT1 SET stato=3
    Ctrl->>Ctrl: genera etichetta DataMatrix
    Note over Ctrl: GTIN "A" + 13 cifre, qta zero-pad 8 cifre
    Ctrl->>Notif: notifyOwner "Commessa X spedita"
    Notif->>UI: chat note consegne (polling 15s)
    Ctrl-->>UI: redirect dashboard + PDF link
```

**File:** `app/Http/Controllers/DashboardSpedizioneController.php`, `app/Modules/Spedizione/Services/DdtSyncService.php`, `app/Modules/Documenti/Services/EtichettaGenerator.php`.

---

## Flusso 4: Sync Prinect (stampa offset XL106)

Frequenza: **ogni 5 minuti** (cron `prinect:sync-attivita`, storico 7gg).

```mermaid
sequenceDiagram
    participant Cron
    participant Svc as PrinectSyncService
    participant Adapter as PrinectHttpAdapter
    participant API as Prinect REST API
    participant AutoTerm as PrinectAutoTerminaService
    participant DB as MySQL
    participant Events

    Cron->>Svc: php artisan prinect:sync-attivita
    Svc->>Adapter: getDeviceActivity(XL106, oggi, ora)
    Adapter->>API: GET /rest/device/{id}/activity
    API-->>Adapter: activities
    Adapter-->>Svc: collection

    loop per ogni attività
        Svc->>Svc: estrai jobId
        Svc->>Svc: mapping jobId → commessa (ltrim 7 char)
        Svc->>Adapter: getJobWorksteps(jobId)
        Adapter->>API: GET /rest/job/{jobId}/workstep
        Adapter-->>Svc: worksteps

        loop per ogni workstep COMPLETED
            alt workstep COMPLETED senza actualStartDate
                Note over Svc: Fix bug 66811: auto-termina fase MES
                Svc->>DB: UPDATE ordine_fasi SET stato=3, data_fine=NOW()
            else
                Svc->>AutoTerm: deveTerminare(fase)
                alt true (>4h inattiva, fasi succ terminate)
                    Svc->>DB: UPDATE ordine_fasi SET stato=3
                end
                Svc->>AutoTerm: deveRipristinare(fase)
                alt true (stato=3 con attività recenti)
                    Svc->>DB: UPDATE ordine_fasi SET stato=2
                end
            end
            Svc->>Adapter: getAccounting(jobId, wsId)
            Svc->>DB: UPDATE qta_prod_prinect, scarti_prinect, tempo_avviamento_sec, tempo_esecuzione_sec
            Svc->>Events: WorkstepCompleted
        end
    end

    Svc-->>Cron: log + count fasi aggiornate
```

**File:** `app/Modules/Prinect/Services/PrinectJobsService.php`, `PrinectAutoTerminaService.php`, `Adapters/PrinectHttpAdapter.php`.

---

## Flusso 5: Scheduler Mossa 37

Trigger: `FaseTerminata` event (real-time) OPPURE cron `scheduler:run` (oggi disabled).

```mermaid
sequenceDiagram
    participant Trigger as FaseTerminata event<br/>or cron scheduler:run
    participant Propag as PropagazioneService
    participant Prio as PrioritaService
    participant Rules as Rules (4 livelli)
    participant DB as MySQL

    Trigger->>Propag: propagaDopoTermine(faseTerminata)
    Propag->>DB: SELECT fasi successive stessa commessa<br/>(config/sequenza_fasi)
    loop fasi successive
        Propag->>Rules: SequenzaFasiRule.puoIniziare(fase)
        alt prec tutte terminate
            Propag->>DB: UPDATE disponibile=true
        end
    end

    Propag->>Prio: calcolaPriorita per tutte le fasi disponibili
    loop ogni fase
        Prio->>Rules: livello 1 Disponibilità (peso 1000)
        Rules-->>Prio: 1000 o 0
        Prio->>Rules: livello 2 UrgenzaRule.urgenza()<br/>(giorni residui, negativo=ritardo)
        Rules-->>Prio: float urgenza
        Prio->>Rules: livello 3 BatchAffinityRule<br/>(setup compatibili ±5gg)
        Rules-->>Prio: batch_key + bonus
        Prio->>Rules: livello 4 SequenzaFasi (config)
        Rules-->>Prio: peso sequenza
        Prio->>Prio: somma lessicografica → priorita_m37
        Prio->>DB: UPDATE priorita_m37, sequenza_m37
    end

    Note over Prio: Dashboard operatore ora vede fasi ordinate DESC per priorita_m37
```

**Macchine specifiche:**
- XL106: 24h lun-ven (3 turni)
- JOH (caldo): 6-22 lun-ven (2 turni)
- BOBST: 1 macchina, 2 config (rilievi/fustelle), cambio config = 1h setup
- Piegaincolla: 1 macchina, 3 config (PI01/PI02/PI03), cambio config = 1h setup
- Standard 6-22: STEL, PLAST, FIN, INDIGO, TAGLIO, LEGAT, ZUND, MGI

**Colli bottiglia:** JOH > BOBST > Piegaincolla.

**File:** `app/Modules/Scheduling/Services/PrioritaService.php`, `PropagazioneService.php`, `Rules/UrgenzaRule.php`, `BatchAffinityRule.php`.

---

## Flusso 6: Sync Excel bidirezionale

Frequenza: **ogni 2 minuti** (cron `excel:sync`).

```mermaid
sequenceDiagram
    participant Cron
    participant Svc as ExcelSyncService
    participant FS as Filesystem
    participant DB as MySQL

    Cron->>Svc: php artisan excel:sync

    Note over Svc: 1. syncIn (Excel → DB)
    Svc->>FS: lettura sync_data.xlsx
    Svc->>Svc: calcola MD5 hash
    Svc->>FS: legge .last_sync_hash
    alt hash diverso
        Svc->>Svc: parsing colonne A-AJ (PhpSpreadsheet)
        Svc->>Svc: raggruppa per commessa
        loop righe modificate
            Svc->>DB: UPDATE OrdineFase corrispondente
            alt data_prevista_consegna cambiata
                Svc->>DB: propaga a tutti ordini stessa commessa
            end
        end
        loop righe eliminate (presenti in .last_exported_ids ma non in xlsx)
            Svc->>DB: soft-delete OrdineFase
        end
        Svc->>FS: aggiorna .last_sync_hash, .last_sync_timestamp
    end

    Note over Svc: 2. syncOut (DB → Excel)
    Svc->>DB: SELECT current state ordini + fasi
    Svc->>FS: scrivi sync_data.xlsx (PhpSpreadsheet)
    Svc->>FS: aggiorna .last_exported_ids
```

**File:** `app/Http/Services/ExcelSyncService.php` (legacy), `app/Modules/Documenti/Services/ExcelSyncService.php` (nuovo).

**State files:** `.last_sync_timestamp`, `.last_sync_hash` (MD5), `.last_exported_ids`.

---

## Flusso 7: Login operatore + Login admin con 2FA

### 7a. Login operatore (codice + password opzionale)

```mermaid
sequenceDiagram
    participant Op as Operatore
    participant UI as /operatore/login
    participant Ctrl as OperatoreLoginController
    participant Auth as Auth guard 'operatore'
    participant DB as MySQL

    Op->>UI: codice_operatore + password
    UI->>Ctrl: POST /operatore/login
    Ctrl->>Auth: attempt({codice_operatore, password})
    Auth->>DB: SELECT operatori WHERE codice_operatore=X AND attivo=1
    DB-->>Auth: operatore
    Auth->>Auth: Hash::check(password)
    Auth-->>Ctrl: ok
    Ctrl->>DB: INSERT operatore_tokens (token random, expires +12h)
    Ctrl->>UI: redirect /operatore/dashboard?op_token=XXX
    Note over UI: Per-tab session via op_token query param
```

### 7b. Login admin con 2FA TOTP

```mermaid
sequenceDiagram
    participant Adm as Admin
    participant UI as /admin/login
    participant Login as AdminLoginController
    participant Auth as Auth guard 'web'
    participant TFC as TwoFactorController
    participant TFS as TwoFactorService (Google2FA)
    participant DB

    Adm->>UI: email + password
    UI->>Login: POST /admin/login
    Login->>Auth: attempt({email, password})
    Auth->>DB: SELECT users WHERE email=X
    DB-->>Auth: user
    Auth-->>Login: ok
    Login->>DB: SELECT user.2fa_enabled_at
    alt 2FA abilitato
        Login->>UI: redirect /admin/2fa/challenge
        Adm->>UI: codice TOTP (Google Authenticator)
        UI->>TFC: POST /admin/2fa/verify {code}
        TFC->>TFS: verify(user.2fa_secret, code)
        TFS-->>TFC: ok
        alt trust device requested
            TFC->>DB: INSERT trusted_devices (cookie hash SHA256, expires +10yr)
        end
        TFC->>UI: redirect /admin/dashboard
    else
        Login->>UI: redirect /admin/dashboard
    end
```

**File:** `app/Http/Controllers/OperatoreLoginController.php`, `AdminLoginController.php`, `TwoFactorController.php`, `app/Services/TwoFactorService.php`.

**Recovery codes:** 8 codici 4-4 one-time use generati al setup 2FA.

---

## Eventi cross-modulo (catalogo completo)

| Evento | Modulo origine | Listener |
|---|---|---|
| `FaseAvviata` | Fasi | TracciaInizioFase (Audit) |
| `FaseTerminata` | Fasi | PropagaFasiSuccessive (Scheduling), NotificaCommessaCompletata (Commessa) |
| `CommessaCompletata` | Commessa | NotificaSpedizione (Notifiche), AuditLog |
| `CommessaConsegnata` | Commessa | AuditLog |
| `OrdineSincronizzato` | Onda | ExcelSync trigger (Documenti) |
| `ClienteSincronizzato` | Onda | AuditLog |
| `WorkstepAvviato` | Prinect | AuditLog |
| `WorkstepCompleted` | Prinect | UpdateOrdineFase tempi, AuditLog |
| `SottoSogliaEvento` | Magazzino | NotificaSottoSoglia (Notifiche) |
| `SpedizioneInRitardo` | Spedizione | NotificaSpedizioneInRitardo (Notifiche), EmailCliente |
| `FustellaPrelevata` | Fustelle | AuditLog |
| `FustellaRestituita` | Fustelle | AuditLog |
| `NotaFustellaAggiunta` | Fustelle | NotificaOperatore (Notifiche) |
| `TimbraturaRegistrata` | Presenze | AuditLog, CalcoloOreService |
| `OperatoreNonTimbrato` | Presenze | NotificaAlert (Notifiche) |
| `StraordinariSuperati` | Presenze | NotificaAlert (Notifiche), AuditLog |
| `RepartoCapacitaSuperata` | Reparti | NotificaOwner (Notifiche) |

Tutti dispatchati via Laravel Events (`event(new EventClass(...))`) e handler in `app/Modules/<Modulo>/Listeners/`.

Registrazione listener: `app/Providers/EventServiceProvider.php` o auto-discovery con `__invoke()` listener.
