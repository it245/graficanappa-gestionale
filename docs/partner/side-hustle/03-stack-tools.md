# Stack tecnologico freelance web designer 2026

## 1. Stack siti vetrina

| Tool | Costo | LC | Pro/Contro |
|---|---|---|---|
| **Astro** | Gratis | Bassa-Media | Zero JS default, Core Web Vitals top |
| **Next.js** | Gratis | Alta | Ecosistema enorme, JS pesante per vetrine |
| **WordPress + Elementor Pro** | 59-199€/anno | Bassa | Cliente edita da solo |
| **Webflow** | 14-23$/mese/sito + 19$ workspace | Bassa | Lock-in pesante |
| **Decap CMS** | Gratis | Bassa | Git-based |
| **Tina** | Free/29$/mese cloud | Media | Visual editing |
| **Sanity** | Free 100K req/mese | Media | API potente |

**Default**: Astro + Tailwind + Decap CMS (siti custom).
**Backup**: WordPress + Elementor Pro per clienti che vogliono editare.

## 2. Hosting + Deploy

| Servizio | Free | Commercial | Verdict |
|---|---|---|---|
| **Cloudflare Pages** | Bandwidth illimitato, 500 build/mese | Gratis | **Vincitore** |
| Vercel Hobby | 100GB/mese | Pro $20/seat | Solo portfolio |
| Netlify Starter | 100GB | Pro $19/mese | Sorprese credit |
| Aruba Linux | - | ~30-50€/anno | WP base |
| SiteGround StartUp | - | ~50-90€ primo anno | WP performance |

## 3. Domain Registrar

| Registrar | .com | .it | Privacy |
|---|---|---|---|
| **Cloudflare Registrar** | $10.46 a costo | NO | Gratis |
| Namecheap | $9-15 | $10-15 | Gratis |
| **OVH Italia** | €8 | €7 | Gratis |
| Aruba | €10 | €0.99 primo / €12 rinnovo | Incl |

**Consigliato**: Cloudflare (.com) + OVH (.it).

## 4. Email Professionale

| Servizio | Free | Pro | Note |
|---|---|---|---|
| **Cloudflare Email Routing** | Forward illimitato | Gratis | Solo inbound |
| Zoho Mail Free | 5 user, 5GB | - | Web-only |
| Zoho Mail Lite | - | €12/user/anno | IMAP+mobile |
| Google Workspace | Trial | $7/user/mese | Standard |

**Consigliato**: Cloudflare Routing + Gmail (gratis) + Zoho Lite per clienti.

## 5. Design / Mockup

| Tool | Free | Pro |
|---|---|---|
| **Figma Free** | 3 file design + 3 FigJam | $15/seat/mese |
| **Penpot** | Tutto illimitato | Self-host |
| Canva Pro | Free utile | ~110€/anno |
| ThemeForest | - | $20-60/template |

## 6. Asset Gratuiti
- **Unsplash, Pexels, Pixabay** — photo/vector
- **Lucide Icons** (ISC) — Astro/React
- **Heroicons** (MIT) — Tailwind
- **Google Fonts** — **self-host obbligatorio** post-Schrems II

## 7. Form

| Tool | Free | Note |
|---|---|---|
| **Web3Forms** | 250/mese, no signup | Top |
| Formspree | 50/mese | $10/mese 200 sub |
| EmailJS | 200/mese | $7/mese |

## 8. SEO + Analytics

| Tool | Costo |
|---|---|
| Google Search Console | Gratis |
| GA4 | Gratis, 45KB script |
| **Umami self-hosted** | ~€5/mese VPS | MIT, 2KB script |
| Plausible Cloud | $9/mese | Privacy-first |
| Screaming Frog Free | Gratis (500 URL) |

**Strategia**: Umami self-hosted (Hetzner VPS €4-5/mese) per TUTTI i clienti → vendi analytics come servizio.

## 9. Project Mgmt / Fatturazione

| Tool | Costo |
|---|---|
| **Fatture in Cloud Forfettari** | €48/anno+IVA |
| Notion Free | Gratis (CRM clienti) |
| Linear Free | Gratis 250 issues |
| Trello Free | Gratis |

## 10. Marketing Locale (per cliente)

- Google Business Profile setup
- Schema.org LocalBusiness JSON-LD
- NAPW consistency
- Recensioni: QR code GBP + email post-servizio

## Costo annuo totale realistico

| Voce | €/anno |
|---|---|
| Dominio .com tuo | 10 |
| Dominio .it studio | 12 |
| Hosting (Cloudflare Pages) | 0 |
| Email (Cloudflare + Gmail) | 0 |
| Figma + Penpot | 0 |
| Canva Pro | 110 |
| Form (Web3Forms) | 0 |
| VPS Hetzner (Umami) | 60 |
| Fatture in Cloud Forfettari | 58 |
| Notion + Linear | 0 |
| Elementor Pro Advanced | 120 |
| Template Themeforest occasionali | 100 |
| **TOTALE anno 1** | **~470€** |

Setup lean: **~120€/anno** se skippi Canva/Elementor/VPS/template.

## Raccomandazione finale

1. **Build**: Astro + Tailwind + Decap CMS (custom) / WP + Elementor Pro (clienti che editano)
2. **Hosting**: Cloudflare Pages / Aruba Linux (WP)
3. **Domini**: Cloudflare (.com) / OVH (.it)
4. **Email**: Cloudflare Routing + Zoho Lite clienti
5. **Design**: Figma Free + Canva Pro
6. **Asset**: Unsplash/Pexels + Lucide + Google Fonts self-host
7. **Form**: Web3Forms
8. **Analytics**: Search Console + Umami self-host (vendilo)
9. **Gestione**: Fatture in Cloud Forfettari + Notion + Linear
10. **Local SEO**: GBP + LocalBusiness JSON-LD obbligatori

Investimento iniziale **<€200**, scalabile a **~€470/anno**. Tempo produzione vetrina: **3-5gg**. Margine target: **70-80% su pacchetti €800-2500**.
