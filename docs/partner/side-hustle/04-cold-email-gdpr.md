# Cold Email B2B Italia 2026 — GDPR + Best Practice

> **TL;DR**: prassi italiana Garante Privacy NON riconosce "legittimo interesse" per cold email B2B come UK/USA. Cold email in Italia al limite della legalità. Per PMI locali (pizzerie/ristoranti) combo più efficace: **visita fisica + Instagram DM + cold email solo su `info@`**.

## 1. Quadro normativo

- **GDPR + Codice Privacy + ePrivacy**
- **Art. 130 Codice Privacy**: email promozionali richiedono **consenso preventivo, libero, specifico, informato, documentabile** (opt-in). Vale anche per B2B.
- Garante **NON ammette** legittimo interesse (art. 6.1.f) per email marketing non sollecitato.

## 2. B2B vs B2C in Italia

**In Italia B2B = B2C** per finalità marketing. La differenza UK/USA "B2B legitimate interest" non esiste.

**Unica eccezione tollerata**: invio a **indirizzi corporate generici non nominativi** (`info@`, `commerciale@`, `prenotazioni@`). Non sono dati personali GDPR. MA: serve informativa + opt-out.

## 3. PEC — VIETATO

Garante ha **vietato esplicitamente** invio email promozionali a PEC da INI-PEC/Registro Imprese. Sanzionati diversi soggetti che hanno scrapato INI-PEC.

## 4. Sanzioni recenti

| Soggetto | Multa | Anno |
|---|---|---|
| Verisure Italia | €400.000 | dic 2025 |
| Aimag spa | €300.000 | 2024 |
| noicompriamoauto.it | €45.000 | 2023 |
| PMI generica | €10.000 | - |

Tetto GDPR: 20M€ o 4% fatturato globale.

> **Solo link unsubscribe NON basta** a rendere lecito invio senza consenso preventivo.

## 5. Tabella legalità rapida

| Azione | Legale in Italia? |
|---|---|
| Email a `info@pizzeria.it` (corporate generica) | Zona grigia tollerata |
| Email a `mario.rossi@pizzeria.it` (nominativa) | **NO** senza consenso |
| Email a PEC da INI-PEC | **NO** |
| Scraping Google Maps (nome/tel/indirizzo) | Sì (dati commerciali) |
| Scraping email personali LinkedIn/GM | NO |
| Database email comprato | Rischio alto |
| LinkedIn DM connessione 1° grado | Sì |
| Visita fisica + biglietto | Sì |
| Telefonata P.IVA pubblica | Verifica RPO |

## 6. Provider email transactional

| Provider | Free tier | Cold permesso? |
|---|---|---|
| **Brevo** | 300/giorno | NO ToS |
| Mailjet | 200/giorno | Solo opt-in |
| SendGrid | Eliminato 27/05/2025 | NO ToS |
| Self-host SMTP | Gratis | Blacklist immediato |

**Per cold email vero**: Instantly/Smartlead/Lemlist (€30-100/mese, warm-up auto). Free tier solo per email clienti già acquisiti.

## 7. Lead generation legale

- **Google Maps scraping** dati commerciali: legale. Email personali nominative: NO.
- **Registro Imprese/Visure Italia**: legale dati legali. PEC per marketing: NO.
- **Apollo.io/Hunter.io**: dichiarano compliance ma Garante NON riconosce base legale per Italia.
- **LinkedIn Sales Navigator** (€80/mese): legale, segmentazione Napoli + ristorazione.

## 8. Deliverability tecnica

- **Dominio dedicato** outreach (es. `giovannipietropaolo-studio.it`)
- **SPF + DKIM + DMARC** obbligatori 2026 (Gmail/Outlook rifiutano senza)
- **DMARC start**: `p=none` per monitorare, mai `p=reject` day 1
- **Warm-up**: 4-6 settimane minimo (5-10 email/gg → 50-75/gg week 5-6)
- **Volume max sostenibile**: 50-100 email/giorno per mailbox
- **Test**: mail-tester.com >9/10

## 9. Copy + Benchmark

- **Subject**: 3-7 parole, no maiuscolo, no spam words ("gratis", "promo")
- **Lunghezza**: 50-120 parole
- **CTA singola**: "ha 10 minuti martedì o mercoledì?" meglio Calendly freddo
- **Follow-up**: 3-4 max, distanziati 3-4-7 giorni
- **Open rate** medio 2026: 27,7% (buono >45%)
- **Reply rate**: 5-15% (ottimo >15%)

## 10. Template GDPR-compliant pizzerie/ristoranti

```
Da: Giovanni Pietropaolo <giovanni@[dominio].it>
A: info@pizzeriaesempio.it
Oggetto: Sito Pizzeria Esempio — 3 prenotazioni in più

Buongiorno,

ho visto che Pizzeria Esempio è su Google Maps con 4,7 stelle ma
senza sito proprio. Mi presento: sono Giovanni, faccio siti vetrina
per ristoranti a Napoli con prenotazione WhatsApp integrata.

Esempio: ho fatto il sito di [Trattoria X al Vomero] — da agosto
+18 prenotazioni/mese senza commissioni TheFork.

Posso passare 10 minuti in pizzeria giovedì mattina per mostrarle
3 idee concrete? Nessun obbligo.

Grazie,
Giovanni Pietropaolo
P.IVA 0000000000 — Napoli
+39 ___ ___ ____ | giovanni@[dominio].it

---
Informativa breve (art. 13 GDPR): il suo indirizzo info@ è stato
reperito dal sito pubblico della sua attività. Base giuridica:
legittimo interesse (art. 6.1.f GDPR) per comunicazione commerciale
B2B verso indirizzo non nominativo. Titolare: Giovanni Pietropaolo,
giovanni@[dominio].it. Per non ricevere più email risponda "STOP"
o clicchi qui: [link unsubscribe]. Dati cancellati entro 24h.
```

**Punti critici**:
- Indirizzo `info@` (non nominativo)
- Informativa art. 13 in calce obbligatoria
- Opt-out one-click + risposta "STOP"
- P.IVA mittente visibile
- Riferimento concreto attività (no template puro)

## 11. Alternative più efficaci

ROI/legalità ranking per PMI locali Napoli:

1. **Visita fisica** Vomero/Chiaia/Posillipo — conversion 5-10x cold email
2. **Instagram DM business** — fuori scope GDPR email
3. **LinkedIn outreach** titolari — reply rate 10-20% vs 5% email
4. **Passaparola + caso studio locale** — fai primo sito gratis/scontato
5. **Cold email** solo se warm-up fatto, volumi bassi (20-30/giorno), su `info@`
6. **Telefonata** — verifica RPO numeri business

## Raccomandazione Giovanni

1. **NON cold email su scala**. Multa Garante €2k-50k > ROI
2. **Strategia ibrida**:
   - 5 visite/settimana zona target
   - 20 Instagram DM/giorno pizzerie business
   - 10 cold email/giorno SOLO `info@` con template, dominio warm-up 4 settimane
3. **Provider**: Brevo free 300/giorno
4. **Setup tecnico**: dominio dedicato + SPF/DKIM/DMARC + mail-tester >9
5. **Documentazione**: Excel con base legale URL, data invio, opt-out ricevuti

## Fonti

- Garante Privacy provvedimenti (Marketing email, Verisure 400k)
- Studio Previti, Floreani, FiscoeTasse (legittimo interesse vs consenso)
- Federprivacy (PEC + opt-out)
- Cybersecurity360 (email senza consenso)
- Brevo (best transactional services 2026)
- Instantly (cold email benchmark + SPF/DKIM/DMARC guide)
- Evaboot (cold email vs LinkedIn)
