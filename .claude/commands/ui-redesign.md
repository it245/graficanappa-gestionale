# UI/UX Redesign Enterprise — MES Grafica Nappa

Agisci come un Senior SaaS Architect + UI/UX Designer specializzato in sistemi MES ed ERP enterprise utilizzati da grandi aziende multinazionali.

## CONTESTO
Il nostro MES è utilizzato (o sarà utilizzato) da alcune delle più grandi aziende al mondo.
Questo implica standard estremamente elevati in termini di:
- usabilità
- coerenza
- scalabilità
- performance percepita
- chiarezza operativa

Il progetto è un'applicazione Laravel con Blade templates, Bootstrap 5, e jQuery/vanilla JS.
Il database è MySQL, le integrazioni includono Prinect (stampa offset), Fiery (stampa digitale), Onda (ERP), BRT (spedizioni).

## OBIETTIVO
Migliorare esclusivamente l'interfaccia utente (UI) e l'esperienza utente (UX) del codice fornito, rendendola di livello enterprise globale.

## FASE 1: RICERCA E ANALISI COMPETITIVA (Multi-Agente)

Prima di iniziare qualsiasi redesign, lancia in parallelo questi agenti di ricerca:

### Agente 1 — Ricerca UI MES/ERP Attuali
Lancia un agente `Explore` per cercare nel web le UI più recenti di:
- **Tulip** (tulip.co) — dashboard operatore e analytics
- **SAP Fiori** — pattern Monitoring Page e Planning Page
- **Odoo 19 MRP** — shop floor e manufacturing dashboard
- **MachineMetrics** — current shift dashboard e tile-based layout
- **Siemens Opcenter** — operator cockpit
- **Epicor Kinetic** — data visualization e KPI monitoring
- **Plex** (Rockwell) — production dashboard
- **Katana MRP** — scheduling drag-and-drop

Cerca screenshot, demo video, design system documentation. Estrai: layout, colori, componenti chiave, navigazione, differenziazione per ruolo.

### Agente 2 — Ricerca Trend UI Dashboard 2025-2026
Lancia un agente `Explore` per cercare:
- "manufacturing dashboard UI design 2026"
- "MES user interface modern examples"
- "production monitoring dark theme dashboard"
- "factory floor dashboard design best practices"
- Dribbble/Behance: "MES dashboard", "ERP dashboard", "manufacturing UI"
- Articoli su dashboard design principles (NN/g, UX Planet, Smashing Magazine)

Estrai: trend emergenti, pattern innovativi, micro-interazioni, data visualization.

### Agente 3 — Analisi Codebase Attuale
Lancia un agente `Explore` per analizzare:
- Tutte le view Blade nel progetto (`resources/views/`)
- I layout esistenti (`layouts/app.blade.php`)
- I CSS inline e le classi Bootstrap utilizzate
- I componenti riutilizzati tra le view
- Le variabili passate dai controller alle view
- Le interazioni JS (AJAX, modali, filtri)

Mappa: inconsistenze di stile, pattern ripetuti, punti di intervento.

### Agente 4 — Analisi Competitor Specifici Tipografie
Lancia un agente `Explore` per cercare:
- **Heidelberg Prinect** — dashboard e widget configurabili
- **PressWise** (SmartSoft) — Print MIS dashboard
- **PrintPLANR** — dashboard personalizzabili per ruolo
- **SOLitrack** — scheduling drag-and-drop
- **Hexicom Software** — dashboard personalizzata per ruolo

Estrai: come gestiscono ordini, fasi, macchine, operatori nella UI.

## FASE 2: SINTESI E DESIGN SYSTEM

Dopo la ricerca, sintetizza i risultati in:
1. **Pattern comuni** tra i migliori MES/ERP
2. **Gap** tra la nostra UI attuale e lo standard enterprise
3. **Priorità** di intervento (cosa migliora di più con meno sforzo)
4. **Design system aggiornato** basato sui trend trovati

## APPROCCIO OBBLIGATORIO
- Ragiona come se stessi progettando per aziende Fortune 500
- Considera le esigenze di:
  - **operatori di produzione** (touch-friendly, pochi click, font grandi, colori di stato evidenti)
  - **manager/owner** (dashboard analitiche, KPI, drill-down, panoramiche)
  - **analisti** (report, filtri, export, confronti periodi)
- Applica best practice di software enterprise (MES/ERP)
- Ottimizza per efficienza operativa e riduzione errori umani
- Mantieni coerenza tra tutte le schermate

## VINCOLI FONDAMENTALI
- **NON modificare la logica di business**
- **NON cambiare API, funzioni o flussi**
- **NON introdurre bug**
- **NON rompere compatibilità esistente**
- Mantieni invariato il comportamento del sistema
- Le colonne e i campi delle tabelle devono restare identici
- I dati passati dal controller alla view non cambiano

## DESIGN SYSTEM

### Font
- **Inter** (Google Fonts) come font primario
- Fallback: -apple-system, BlinkMacSystemFont, sans-serif

### Palette Colori
```
Background:        #f8fafc (grigio quasi bianco)
Card background:   #ffffff
Sidebar:           #1e293b (slate scuro)
Sidebar hover:     #334155
Sidebar active:    #2563eb (blu)
Topbar:            #ffffff con bordo #e2e8f0
Testo primario:    #1e293b
Testo secondario:  #64748b
Accento primario:  #2563eb (blu)
Successo:          #16a34a (verde)
Warning:           #d97706 (ambra)
Errore/Urgente:    #dc2626 (rosso)
Info:              #0891b2 (cyan)
Esterno:           #7c3aed (viola)
```

### Dark Mode (per monitor reparto)
```
Background:        #0f172a
Card background:   #1e293b
Testo:             #f1f5f9
Accenti:           stessi ma più saturi
```

### Componenti Standard

**Sidebar** (220px, fissa a sinistra):
- Logo + nome app in alto
- Sezioni raggruppate con label uppercase piccole
- Icone SVG 16px + testo 12px
- Active state: sfondo blu trasparente + bordo sinistro blu
- Footer con versione e data

**Topbar** (48px, fissa in alto):
- Titolo pagina a sinistra
- Dark mode toggle + ora + avatar utente a destra

**KPI Cards**:
- Bordo sinistro colorato (4px)
- Valore grande (24-28px bold)
- Label piccola uppercase (10px)
- Sottotesto grigio
- Click per drill-down

**Status Badge**:
- Pill arrotondate con colore semantico
- 0=grigio, 1=blu, 2=ambra, 3=verde, 4=grigio scuro
- Font 11px bold

**Tabelle**:
- Header grigio scuro (non nero pieno)
- Righe alternate sottili
- Hover effect leggero
- Bordi arrotondati sul contenitore
- Filtri inline sopra

**Progress Bar**:
- 6px height, bordi arrotondati
- Verde per completate, ambra per in corso, grigio per da fare

### Spacing & Layout
- Padding contenuto: 24px
- Gap tra card: 16px
- Border radius card: 12px
- Border radius tabella container: 12px
- Ombre: `0 1px 3px rgba(0,0,0,0.1)` (sottili)

## LINEE GUIDA UI/UX
- Design stile SaaS moderno enterprise
- Gerarchia visiva estremamente chiara
- Interfacce dense ma leggibili (tipico MES)
- Riduzione del carico cognitivo
- Uso intelligente di colori e stati (warning, error, success)
- Tabelle avanzate (filtri, sorting, leggibilità)
- Feedback chiari per ogni azione utente (bordo verde al salvataggio, toast per errori)
- Accessibilità e consistenza
- Transizioni CSS leggere (0.15s-0.2s)

## COERENZA E ANALISI
Prima di generare il risultato:
1. Analizza il contesto del modulo (quale view? quale ruolo utente?)
2. Identifica lo scopo operativo della schermata
3. Uniforma lo stile al design system sopra
4. Evita scelte arbitrarie o incoerenti

## STRUTTURA PAGINA TIPO

```
┌─────────────────────────────────────────────┐
│ SIDEBAR (220px)  │  TOPBAR (48px)           │
│                  ├──────────────────────────│
│ Logo             │  KPI Cards (4-6)          │
│ ─────────        │  ┌──┐ ┌──┐ ┌──┐ ┌──┐    │
│ Sezione 1        │  │  │ │  │ │  │ │  │    │
│  • Link          │  └──┘ └──┘ └──┘ └──┘    │
│  • Link          │                          │
│ Sezione 2        │  Tabella / Contenuto     │
│  • Link          │  ┌──────────────────┐    │
│  • Link          │  │ Filtri           │    │
│                  │  ├──────────────────┤    │
│ Footer           │  │ Header  │ Dati   │    │
│                  │  │─────────┼────────│    │
│                  │  │ Riga 1  │ ...    │    │
│                  │  └──────────────────┘    │
└─────────────────────────────────────────────┘
```

## MIGLIORAMENTI ATTESI
- UI più pulita, professionale e ordinata
- UX orientata alla produttività
- Miglior organizzazione delle informazioni
- Componenti riutilizzabili e standardizzati
- Micro-interazioni leggere ma utili

## CONTROLLO QUALITÀ
Prima di restituire:
- ✅ Verifica che NON sia stata modificata la logica
- ✅ Verifica assenza di errori PHP/JS
- ✅ Verifica coerenza visiva con il design system
- ✅ Verifica leggibilità e chiarezza
- ✅ Verifica che i dati del controller passino correttamente

## RICERCA UI/UX GIÀ COMPLETATA (Riferimenti)

### Software MES/ERP Analizzati
| Software | Punti Chiave UI |
|----------|----------------|
| **Tulip** | Dashboard modulare, widget drag-and-drop, Card KPI con trend, visualizzazione planimetria, tracker OEE real-time |
| **SAP Fiori** | Monitoring Page + Planning Page, colori semantici (Horizon palette), portlet modulari, filtri responsive |
| **Odoo 19** | Card visuali colorate per ordini, shop floor semplificato, interfaccia veloce e reattiva |
| **MachineMetrics** | Tile per macchina che cambia colore (verde/giallo/rosso), vista "Current Shift", touchscreen a bordo macchina |
| **Siemens Opcenter** | Tile-based layout, flat design responsive, "Operator Cockpit" con valori aggregati |
| **Epicor Kinetic** | Strumenti avanzati visualizzazione dati, grafici e tabelle, monitoraggio KPI facilitato |
| **Katana MRP** | Interfaccia minimalista, scheduling drag-and-drop, BOM integrata |
| **Microsoft Dynamics 365** | Copilot AI, trigger automatici, previsioni vendite basate su IA |
| **Oracle NetSuite** | Dashboard a portlet, Gantt chart interattivo, workbook a grafici |
| **Acumatica** | Design dinamico, automazione personalizzabile, integrazioni e-commerce |

### Software Specifici Tipografie
| Software | Punti Chiave UI |
|----------|----------------|
| **Prinect (Heidelberg)** | Widget configurabili, monitoraggio macchina remoto con drill-down, warning real-time |
| **PressWise** | Vista top-down ordini per stato, drill-down per giorno/ora |
| **PrintPLANR** | Dashboard personalizzabili per utente, snapshot rapido stato operativo |
| **SOLitrack** | Drag-and-drop scheduling e rilascio lavori |

### Pattern Comuni Identificati
- **Sidebar fissa + topbar** è il layout dominante in tutti i MES/ERP moderni
- **KPI Cards** con valore + trend + sparkline in alto su ogni dashboard
- **Status badge colorati** con icona + testo (non solo numero)
- **Dark mode** disponibile per monitor di reparto
- **Differenziazione per ruolo**: operatore (semplice, touch), manager (analitico, KPI), admin (configurazione)
- **Micro-interazioni**: hover states, transizioni fluide, tooltip informativi
- **Widget personalizzabili**: l'owner può riorganizzare le card
- **Confronto periodi**: "Oggi vs ieri", "Questa settimana vs scorsa" nelle KPI

### ERP Analizzati (da ClickUp research)
| ERP | Miglior Per | UI Insight |
|-----|-----------|------------|
| Oracle Cloud | Global Ops | Analitiche real-time, conformità multicountry |
| Sage Intacct | Finanza AI | Report multidimensionali real-time, anomaly detection |
| SAP Business One | PMI CRM | Integrazione Outlook, monitoraggio opportunità |
| Odoo | Open Source | Moduli componibili, prezzi bassi, Studio per customizzazione |
| Epicor | Manifatturiero | Visualizzazione dati avanzata, GRC integrata |
| SYSPRO | Produzione | Visibilità real-time inventario, BOM, tracciabilità supply chain |

## FASE 3: IMPLEMENTAZIONE (Multi-Agente)

Quando si passa alla fase di implementazione, lancia agenti in parallelo:

### Agente A — Layout Base
Crea il nuovo layout `layouts/mes.blade.php` con:
- Sidebar con navigazione
- Topbar
- Area contenuto
- Dark mode CSS
- Font Inter

### Agente B — Componenti Riutilizzabili
Crea partial Blade riutilizzabili in `resources/views/components/`:
- `components/kpi-card.blade.php`
- `components/status-badge.blade.php`
- `components/data-table.blade.php`
- `components/progress-bar.blade.php`
- `components/sidebar-item.blade.php`

### Agente C — Redesign View Specifica
Prende la view indicata dall'utente e la redesigna applicando:
- Il nuovo layout
- I componenti standard
- Il design system
- Senza toccare la logica

### Agente D — Quality Assurance
Dopo il redesign, verifica:
- La view si renderizza senza errori
- I dati del controller passano correttamente
- La logica JS funziona ancora
- Il CSS è coerente con il design system
- Non ci sono regressioni

## COME USARE QUESTA SKILL

Quando l'utente invoca `/ui-redesign`:

1. **Chiedi quale view** vuole redesignare (owner dashboard, operatore, prestampa, ecc.)
2. **Lancia Fase 1** (ricerca) se non è già stata fatta di recente
3. **Analizza** la view attuale + controller
4. **Proponi** il design prima di implementare (wireframe testuale)
5. **Implementa** con multi-agenti in parallelo
6. **Verifica** con l'agente QA
7. **Mostra** il risultato e chiedi feedback
