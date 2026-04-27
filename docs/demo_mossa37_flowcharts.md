# Demo Mossa 37 — Flowchart

Render: VSCode estensione "Markdown Preview Mermaid" oppure https://mermaid.live/

---

## 1. Decisione Priorità (5 livelli)

```mermaid
flowchart TD
    A[Commessa Onda] --> B[Mossa 37 PriorityService]
    B --> L1{1. Urgenza<br/>giorni alla consegna}
    L1 -->|critica ≤3gg| L1A[+1000 punti]
    L1 -->|in tempo| L1B[base]
    L1A --> L2
    L1B --> L2

    L2{2. Ritardo reale<br/>fine stimata > consegna}
    L2 -->|sì| L2A[+500 punti]
    L2 -->|no| L2B[0]
    L2A --> L3
    L2B --> L3

    L3{3. Batch affinity<br/>setup compatibile}
    L3 -->|setup uguale| L3A[+200 punti]
    L3 -->|setup diverso| L3B[0]
    L3A --> L4
    L3B --> L4

    L4{4. Sequenza ciclo<br/>fase precedente OK}
    L4 -->|fase pronta| L4A[+100 punti]
    L4 -->|in attesa| L4B[posticipo]
    L4A --> L5
    L4B --> END

    L5{5. Formato carta XL106<br/>raggruppa stessa misura}
    L5 -->|stesso formato| L5A[+50 punti]
    L5 -->|formato diverso| L5B[0]
    L5A --> OUT
    L5B --> OUT

    OUT[Score finale] --> SCHED[Posizione in coda<br/>per macchina]
    SCHED --> END[Gantt aggiornato]
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
    subgraph OLD["PRIMA — Pianificazione Manuale"]
        O1[Capo reparto<br/>guarda elenco] --> O2[Decide priorità<br/>a memoria]
        O2 --> O3[Comunica a voce<br/>operatori]
        O3 --> O4{Cambio urgenza?}
        O4 -->|sì| O5[Re-pianifica<br/>tutto da zero<br/>30-60 min]
        O5 --> O3
        O4 -->|no| O6[Stop]
        O6 --> O7[Setup ridondanti<br/>fermi macchina<br/>code visibili solo a uomo]
    end

    subgraph NEW["DOPO — Mossa 37"]
        N1[Onda invia commessa] --> N2[Mossa 37 calcola<br/>5 livelli priorità<br/>< 1 secondo]
        N2 --> N3[Gantt aggiornato<br/>real-time]
        N3 --> N4{Cambio urgenza?}
        N4 -->|sì| N5[Ricalcolo automatico<br/>< 1 secondo]
        N5 --> N3
        N4 -->|no| N6[Continua]
        N6 --> N7[Batch ottimizzati<br/>setup minimi<br/>colli bottiglia evidenziati]
    end
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
