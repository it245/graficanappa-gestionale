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
Migliorare esclusivamente l'interfaccia utente (UI) e l'esperienza utente (UX) del codice fornito, rendendola di livello enterprise globale — come se fosse un prodotto Fortune 500.

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
- I layout esistenti (`layouts/mes.blade.php`, `layouts/app.blade.php`)
- I CSS inline e le classi Bootstrap utilizzate
- I componenti riutilizzabili in `components/mes/`
- Le variabili passate dai controller alle view
- Le interazioni JS (AJAX, modali, filtri)

Mappa: inconsistenze di stile, pattern ripetuti, punti di intervento.

### Agente 4 — Ricerca Specifici Tipografie
Lancia un agente `Explore` per cercare:
- **Heidelberg Prinect** — dashboard e widget configurabili
- **PressWise** (SmartSoft) — Print MIS dashboard
- **PrintPLANR** — dashboard personalizzabili per ruolo
- **SOLitrack** — scheduling drag-and-drop
- **Hexicom Software** — dashboard personalizzata per ruolo

Estrai: come gestiscono ordini, fasi, macchine, operatori nella UI.

### Agente 5 — Ricerca Design Inspiration & Best Practices (NUOVO)
Lancia un agente `Explore` per cercare su:
- **Dribbble.com**: "SaaS dashboard", "admin panel", "enterprise UI", "analytics dashboard", "dark mode dashboard"
- **Behance.net**: "manufacturing UI", "industrial dashboard", "production management"
- **Mobbin.com**: design pattern per app enterprise
- **SaaS Pages** (saaspages.xyz): landing page e dashboard di SaaS reali
- **Refero.design**: UI reali di prodotti SaaS
- **Screenlane.com**: UI pattern di app reali
- **Collectui.com**: collezione UI components
- **UI8.net / Envato Elements**: template dashboard premium enterprise
- **Linear.app** — UI pulitissima, keyboard shortcuts, animazioni fluide
- **Vercel Dashboard** — minimale ma potente, dark mode perfetto
- **Stripe Dashboard** — data density, micro-interazioni, tipografia
- **Figma** — come gestiscono dashboard collaborative
- **Notion** — sidebar, navigazione, UX per power user
- **GitHub** — tabelle dati dense, filtri, status badges
- **Material Design 3** (m3.material.io) — design system Google aggiornato
- **Carbon Design System** (IBM) — specifico per enterprise/industrial
- **Ant Design** — design system cinese molto usato in ERP/MES
- **Chakra UI / Radix UI** — component library moderne
- Articoli specifici:
  - "How to design data-dense UIs" (NN/g)
  - "Enterprise UX patterns 2026"
  - "Dashboard typography best practices"
  - "Color systems for data visualization"
  - "Micro-interactions that improve UX"
  - "Designing for 10,000 rows" (table design)

Estrai: palette colori professionali, tipografia, spacing, ombre, transizioni, micro-animazioni, pattern per tabelle dense, sidebar navigation, KPI cards, status indicators.

### Agente 6 — Audit Accessibilità & Performance Percepita (NUOVO)
Lancia un agente `Explore` per cercare:
- WCAG 2.2 AA requirements per dashboard enterprise
- "Perceived performance CSS tricks" — skeleton loading, shimmer, transitions
- "Touch target size guidelines" — WCAG, Apple HIG, Material Design
- Contrasto colori minimo per testo/sfondo
- Focus indicators per navigazione da tastiera
- "Loading states best practices" — spinner vs skeleton vs progressive

## FASE 2: SINTESI E DESIGN SYSTEM

Dopo la ricerca, sintetizza i risultati in:
1. **Pattern comuni** tra i migliori MES/ERP e i migliori SaaS del mondo
2. **Gap** tra la nostra UI attuale e lo standard Fortune 500
3. **Priorità** di intervento (cosa migliora di più con meno sforzo)
4. **Design system aggiornato** basato sui trend trovati
5. **Moodboard testuale**: 5-10 riferimenti visivi specifici con URL

## APPROCCIO OBBLIGATORIO
- Ragiona come se stessi progettando per aziende Fortune 500
- Il MES deve sembrare un prodotto da $50.000/anno di licenza
- Considera le esigenze di:
  - **operatori di produzione** (touch-friendly, pochi click, font grandi, colori di stato evidenti)
  - **manager/owner** (dashboard analitiche, KPI, drill-down, panoramiche)
  - **analisti** (report, filtri, export, confronti periodi)
- Applica best practice dei migliori SaaS al mondo (Linear, Vercel, Stripe)
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

## DESIGN SYSTEM ATTUALE (v2.0 base)

### Font
- **Inter** (Google Fonts) come font primario
- `font-feature-settings: 'tnum'` per numeri tabulari
- Fallback: -apple-system, BlinkMacSystemFont, sans-serif

### Palette Colori
```
Background:        #f8fafc (grigio quasi bianco)
Card background:   #ffffff
Sidebar:           #1e293b (slate scuro)
Sidebar hover:     #334155
Sidebar active:    #2563eb (blu) con bordo sinistro
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

### Dark Mode
```
Background:        #0f172a
Card background:   #1e293b
Testo:             #f1f5f9
Accenti:           stessi ma più saturi
Toggle:            salvato in localStorage, applicato prima del paint
```

### Componenti Standard (già implementati)
- **Sidebar** (220px) — `layouts/mes.blade.php`
- **Topbar** (48px) con clock, dark mode, avatar
- **KPI Cards** — `components/mes/kpi-card.blade.php` (bordo sx 4px)
- **Status Badge** — `components/mes/status-badge.blade.php` (pill)
- **Progress Bar** — `components/mes/progress-bar.blade.php` (6px)
- **Data Table** — `components/mes/data-table.blade.php` (rounded container)

### Spacing & Layout
- Padding contenuto: 24px
- Gap tra card: 16px
- Border radius card: 12px
- Ombre: `0 1px 3px rgba(0,0,0,0.1)` base, `0 4px 12px rgba(0,0,0,0.08)` hover

## LINEE GUIDA UI/UX
- Design stile SaaS premium enterprise (pensa Linear + Stripe)
- Gerarchia visiva estremamente chiara
- Interfacce dense ma leggibili (tipico MES)
- Riduzione del carico cognitivo
- Uso intelligente di colori e stati
- Feedback chiari per ogni azione (bordo verde al salvataggio, toast per errori)
- Accessibilità WCAG 2.2 AA
- Transizioni CSS leggere (150ms hover, 200ms fade, 250ms slide)
- Skeleton loading invece di spinner dove possibile

## FASE 3: IMPLEMENTAZIONE (Multi-Agente)

Quando si passa alla fase di implementazione, lancia agenti in parallelo:

### Agente A — Miglioramento Layout e Componenti
Migliora `layouts/mes.blade.php` e i componenti in `components/mes/`:
- Applicare le novità trovate nella ricerca
- Migliorare transizioni, ombre, spacing
- Aggiungere skeleton loading states
- Migliorare dark mode (colori più raffinati)

### Agente B — Redesign View Specifica
Prende la view indicata dall'utente e la redesigna applicando:
- Il design system migliorato
- I pattern trovati nella ricerca
- Senza toccare la logica

### Agente C — Quality Assurance
Dopo il redesign, verifica:
- La view si renderizza senza errori
- I dati del controller passano correttamente
- La logica JS funziona ancora
- Il CSS è coerente con il design system
- Non ci sono regressioni
- Contrasto WCAG AA rispettato

## COME USARE QUESTA SKILL

Quando l'utente invoca `/ui-redesign`:

1. **Chiedi quale view** vuole redesignare (owner dashboard, operatore, prestampa, spedizione, report ore, scheduling, ecc.)
2. **Lancia Fase 1** (ricerca) — include i nuovi agenti 5 e 6
3. **Sintetizza** la ricerca in raccomandazioni concrete
4. **Proponi** il design prima di implementare (wireframe testuale + riferimenti)
5. **Implementa** con multi-agenti in parallelo
6. **Verifica** con l'agente QA
7. **Mostra** il risultato e chiedi feedback

## RICERCA UI/UX GIÀ COMPLETATA (Sessione 20 marzo 2026)

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

### Design System Reference (da ricerca agente 5)
| Fonte | Insight Chiave |
|-------|---------------|
| **Linear.app** | Keyboard-first, animazioni 150ms, palette scura raffinata, zero clutter |
| **Vercel Dashboard** | Minimale, dark mode perfetto, tipografia Inter, spacing generoso |
| **Stripe Dashboard** | Data density altissima ma leggibile, micro-interazioni su hover, colori muted |
| **Carbon Design (IBM)** | Status indicators con dot + testo, token-based theming, 4px grid |
| **Material Design 3** | Elevation system raffinato, color roles, dynamic color |
| **SAP Horizon** | Border-radius 12px (trend 2025-2026), shadow system leggero |

### Pattern Comuni Identificati
- **Sidebar fissa + topbar** è il layout dominante
- **KPI Cards** con valore + trend + sparkline in alto
- **Status badge colorati** con icona + testo
- **Dark mode** disponibile per monitor di reparto
- **Differenziazione per ruolo**: operatore (semplice, touch), manager (analitico)
- **Skeleton loading** al posto di spinner
- **Micro-interazioni**: hover states, transizioni fluide, tooltip
- **240px sidebar** collapsibile a 64px (pattern dominante 2025-2026)
- **Inter font** con `font-feature-settings: 'tnum'` per numeri tabulari
- **Ombre MD3**: `0 1px 3px rgba(0,0,0,0.04)` base → `0 4px 12px rgba(0,0,0,0.08)` hover
