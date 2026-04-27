# Demo Mossa 37 — Flowchart

Render: VSCode estensione "Markdown Preview Mermaid" oppure https://mermaid.live/

---

## 0. Come funziona Mossa 37 — Sintesi

```mermaid
flowchart LR
    subgraph IN["📥 INPUT real-time"]
        direction TB
        I1[Onda ERP<br/>commesse + priorità]
        I2[Prinect XL106<br/>tempi effettivi stampa]
        I3[Fiery V900<br/>coda digitale]
        I4[NetTime<br/>presenze operatori]
    end

    subgraph CRIT["🎯 4 CRITERI di priorità"]
        direction TB
        C1[1. Urgenza consegna<br/>giorni rimanenti]
        C2[2. Sequenza ciclo<br/>stampa→plast→fust→piega→sped]
        C3[3. Affinità batch<br/>stesso formato/colore/cliché]
        C4[4. Disponibilità macchina<br/>BOBST 2 config, PI01/02/03]
    end

    ENGINE{{⚙️ Mossa 37<br/>PriorityService<br/>< 1 secondo}}

    subgraph OUT["📤 OUTPUT"]
        direction TB
        O1[Gantt aggiornato<br/>real-time]
        O2[Batch ottimizzati<br/>setup minimi]
        O3[Alert capo reparto<br/>colli bottiglia]
    end

    EVENT[🔄 Trigger ricomposizione<br/>fase termina · cambio urgenza · nuova commessa]

    IN --> ENGINE
    CRIT --> ENGINE
    ENGINE --> OUT
    EVENT -.-> ENGINE

    RESULT[📊 50+ commesse pianificate &lt; 1 sec<br/>vs 2 ore Excel manuale]
    OUT --> RESULT

    style ENGINE fill:#0d6efd,color:#fff,stroke:#0a58ca,stroke-width:3px
    style RESULT fill:#16a34a,color:#fff,stroke:#15803d,stroke-width:2px
    style EVENT fill:#fbbf24,color:#000,stroke:#f59e0b
```

---

## 1. Decisione Priorità (5 livelli)

```mermaid
flowchart LR
    A[Commessa<br/>Onda] --> B[Mossa 37<br/>PriorityService]
    B --> L1{1. Urgenza}
    L1 -->|critica ≤3gg| L1A[+1000]
    L1 -->|in tempo| L1B[base]
    L1A --> L2
    L1B --> L2

    L2{2. Ritardo} -->|sì| L2A[+500]
    L2 -->|no| L2B[0]
    L2A --> L3
    L2B --> L3

    L3{3. Batch<br/>affinity} -->|setup<br/>uguale| L3A[+200]
    L3 -->|diverso| L3B[0]
    L3A --> L4
    L3B --> L4

    L4{4. Sequenza<br/>ciclo} -->|pronta| L4A[+100]
    L4 -->|attesa| L4B[posticipo]
    L4A --> L5
    L4B --> END

    L5{5. Formato<br/>XL106} -->|stesso| L5A[+50]
    L5 -->|diverso| L5B[0]
    L5A --> OUT
    L5B --> OUT

    OUT[Score<br/>finale] --> SCHED[Coda<br/>macchina]
    SCHED --> END[Gantt<br/>aggiornato]
```

---

## 2. Propagazione Fasi (event-driven)

```mermaid
flowchart LR
    A[Operatore termina<br/>fase X] --> B[Event PhaseCompleted]
    B --> C[Listener: SchedulerService]
    C --> D[Calcola fase X+1<br/>priorità + macchina]
    D --> E[Aggiorna ordine_fasi.priorita]
    E --> F[Ricalcola batch<br/>macchine collegate]
    F --> G[Push WebSocket<br/>Gantt UI]
    G --> H[Operatore vede<br/>nuova fase pronta]

    C --> I[Verifica colli di bottiglia<br/>JOH / BOBST / Piegaincolla]
    I --> J{Carico macchina<br/>> soglia?}
    J -->|sì| K[Alert capo reparto]
    J -->|no| L[OK]
```

---

## 3. Architettura Sistema

```mermaid
flowchart TB
    subgraph SRC["Sorgenti dati"]
        ONDA[Onda ERP<br/>SQL Server]
        FIERY[Fiery V900<br/>API Accounting]
        PRINECT[Prinect XL106<br/>Pressroom Manager]
    end

    subgraph SYNC["Layer Sync"]
        S1[Onda Sync<br/>SOAP ogni 1h]
        S2[Fiery Sync<br/>API ogni 1min]
        S3[Prinect Sync<br/>queue job]
    end

    subgraph MES["MES Grafica Nappa"]
        DB[(MySQL .60)]
        SCHED[SchedulerService<br/>Mossa 37]
        UI[Dashboard<br/>Owner / Operatore]
        TG[Bot Telegram<br/>Magazzino]
    end

    subgraph EXT["Esterno"]
        TGAPP[Telegram Mobile]
        BRT[BRT SOAP<br/>Spedizioni]
        AI[Claude API<br/>Vision bolle]
    end

    ONDA --> S1 --> DB
    FIERY --> S2 --> DB
    PRINECT --> S3 --> DB

    DB <--> SCHED
    SCHED <--> UI
    DB <--> TG
    TG <--> TGAPP
    TG --> AI

    UI --> BRT
```

---

## 4. Confronto: Manuale vs Mossa 37

```mermaid
flowchart LR
    subgraph OLD["⚠️ PRIMA — Manuale"]
        direction TB
        O1[Capo reparto<br/>guarda elenco] --> O2[Decide priorità<br/>a memoria]
        O2 --> O3[Comunica<br/>a voce]
        O3 --> O4{Cambio<br/>urgenza?}
        O4 -->|sì| O5[Re-pianifica<br/>30-60 min]
        O5 --> O3
        O4 -->|no| O7[Setup ridondanti<br/>fermi macchina]
    end

    OLD ~~~ ARROW[➡️ MOSSA 37 ➡️]
    ARROW ~~~ NEW

    subgraph NEW["✅ DOPO — Mossa 37"]
        direction TB
        N1[Onda invia<br/>commessa] --> N2[5 livelli priorità<br/>< 1 sec]
        N2 --> N3[Gantt<br/>real-time]
        N3 --> N4{Cambio<br/>urgenza?}
        N4 -->|sì| N5[Ricalcolo auto<br/>< 1 sec]
        N5 --> N3
        N4 -->|no| N7[Batch ottimizzati<br/>colli bottiglia<br/>evidenziati]
    end

    style ARROW fill:#fff,stroke:#fff,color:#16a34a,font-weight:bold,font-size:18px,stroke-width:2px
```

---

## Note demo

**Apertura demo (2 min)**
- Mostra dashboard Owner attuale con tabella commesse
- Spiega: oggi capo reparto pianifica a memoria

**Mossa 37 live (5 min)**
- Apri `/owner/scheduling`
- Mostra Gantt Per Macchina (5 giorni)
- Filtra "Solo critiche" → mostra urgenze
- Cambia priorità manuale 1 commessa → ricalcolo automatico
- Mostra KPI (commesse attive, in tempo, ritardo, ore stimate)

**Q&A (3 min)**
- Costo Claude API per AI bolle: ~3€/1000 bolle
- Roadmap: integrazione magazzino completa, dashboard cliente

**Materiali fisici**
- Non servono. Tutto live in browser.
- Eventuale: stampa flowchart 1+4 (priorità + confronto) come supporto.
