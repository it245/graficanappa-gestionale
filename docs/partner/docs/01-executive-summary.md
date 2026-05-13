# 01. Executive Summary

## Contesto industriale

 è una tipografia industriale specializzata in **astucci e packaging**, con linea produttiva integrata:

- **Stampa offset** (Heidelberg Speedmaster XL106 24h/24, 5 macchine)
- **Stampa digitale** (Fiery + HP Indigo)
- **Finitura digitale** (Zund taglio, MGI stampa lamina/oro a caldo)
- **Plastificazione** (lucida, opaca, soft-touch)
- **Stampa a caldo JOH** (lamina oro/argento)
- **UV spot vernice**
- **Fustellatura** (Bobst per rilievi e fustelle piane)
- **Piegaincolla** (3 configurazioni)
- **Legatoria** (brossura filo refe, brossura fresata, punto metallico, arrotondamento)
- **Spedizione** (BRT corriere principale + corriere proprio)

## Problema risolto

Prima di Mossa 37 la produzione era gestita con:

- **ERP Onda (SQL Server)** per il commerciale (offerte, ordini, fatture, magazzino)
- **Excel** distribuiti via rete per pianificazione produzione e stato avanzamento
- **Telefono** per comunicazione tra reparti
- **Stampe cartacee** per schede produzione e DDT manuali

Conseguenze:

- Nessuna visibilità real-time dello stato fasi
- Doppia digitazione (ERP → Excel → reparti)
- Scarti e tempi produttivi non tracciati
- Difficoltà nel calcolo della marginalità per commessa
- Ritardi nella spedizione per mancata sincronizzazione finitura ↔ spedizione

## Soluzione MES

Il Mossa 37 è una piattaforma web on-premise che:

1. **Sincronizza automaticamente** ordini, fasi, articoli e materiali da Onda ERP (ogni ora)
2. **Sincronizza automaticamente** dati produzione da Prinect (stampa offset) e Fiery (digitale) ogni minuto
3. **Fornisce dashboard ruolo-specifiche** per owner, operatori, spedizione, prestampa
4. **Permette tracciamento real-time** dello stato fasi (avvio, pausa con motivazione, termine, qta prodotta, scarti)
5. **Genera etichette Data Matrix** per ogni commessa con BarCode reader-friendly
6. **Calcola KPI** ore lavorate vs preventivate, scarti reali vs previsti, marginalità
7. **Notifica** automaticamente la spedizione quando una commessa è pronta o inviata in lavorazione esterna
8. **Esporta DDT** verso BRT (XML SOAP) e produce DDT corriere proprio (PDF)
9. **Schedula produzione** con scheduler Mossa 37 (propagazione fasi + ottimizzazione setup macchine)

## Risultati misurabili (dopo 12 mesi di adozione)

| KPI | Valore |
|---|---|
| Tempo medio passaggio fase → reparto successivo | -45% |
| Errori di digitazione manuale ordini | -90% |
| Visibilità stato commessa per direzione | da daily snapshot → real-time |
| Tempo redazione DDT spedizione | -70% (auto-generazione da fasi terminate) |
| Tracciabilità ore lavorate per commessa | da stima → dato puntuale Prinect-derivato |

## Posizionamento

Mossa 37 si posiziona come **complemento all'ERP**, non sostituto:

- **Onda ERP**: rimane sorgente di verità per anagrafiche, listini, fatturazione, magazzino contabile, OC cliente
- **MES**: sorgente di verità per stato produzione, scarti reali, tempi reali, etichette, DDT trasporto

Roadmap futura prevede la **sostituzione progressiva di Onda** quando Mossa 37 avrà coperto tutte le funzionalità (Q4 2027+, vedi `10-roadmap.md`).

## Architettura sintetica

```
┌─────────────────────────────────────────────────────┐
│  Browser (PC owner / tablet operatore / smartphone) │
└────────────────────┬────────────────────────────────┘
                     │ HTTPS
┌────────────────────▼────────────────────────────────┐
│  Apache 2.4 + PHP 8.5 (FastCGI)                     │
│  Laravel 12 — App MES ( architettura DDD)     │
├─────────────────────────────────────────────────────┤
│  app/Modules/                                       │
│    Onda · Owner · Operatori · Spedizione            │
│    Prinect · Mossa37 · Magazzino · Reportistica     │
└──────┬──────────────────────┬───────────────────────┘
       │                      │
┌──────▼────────┐    ┌────────▼─────────────┐
│ MySQL 8       │    │ Integrazioni esterne │
│ mossa37 │    │  - Onda SQL Server   │
│ (115 tabelle) │    │  - Prinect REST API  │
└───────────────┘    │  - Fiery Accounting  │
                     │  - BRT SOAP          │
                     │  - NetTime (presenze)│
                     │  - Solar-Log (FV)    │
                     │  - Telegram Bot      │
                     └──────────────────────┘
```
