# Architettura MES Grafica Nappa

> Branch di riferimento: `def2.0` — monolite Laravel + 11 moduli DDD in `app/Modules/`.
> Pattern di migrazione: **Strangler Fig** (codice legacy ancora in produzione, moduli che ne assorbono pezzi senza rotture).

## Sommario

1. [Visione d'insieme](#visione-dinsieme)
2. [Diagramma del sistema](#diagramma-del-sistema)
3. [Convenzioni moduli](#convenzioni-moduli)
4. [I moduli](#i-moduli)
5. [Strangler Fig: come migrare codice legacy](#strangler-fig-come-migrare-codice-legacy)
6. [Test strategy](#test-strategy)

---

## Visione d'insieme

L'app è un monolite Laravel 11 (PHP 8.3, MySQL su `.60`). Storicamente la logica
di dominio era distribuita fra `App\Models`, `App\Http\Controllers` e
`App\Http\Services`. Negli ultimi mesi (def2.0) abbiamo introdotto una
*foundation modulare* in `app/Modules/<Nome>` con quattro obiettivi:

- **Isolare il dominio** dai concern HTTP/persistenza Eloquent.
- **Testabilità**: classi finali, readonly, strict types, dipendenze iniettate.
- **Migrazione incrementale**: nessun big-bang. I moduli avvolgono i service
  legacy tramite `Adapter` e li espongono dietro `Contracts`.
- **Riuso futuro come SaaS multi-tenant** (vedi `project_mes_commerciale.md`).

I moduli **non sostituiscono** ancora i Controller / Eloquent: sono il
*core* su cui i Controller delegano la logica di business. Il codice legacy
in `App\Http\Services` (es. `PrinectService`, `BrtService`, `OndaService`)
resta vivo e viene riusato dai moduli tramite Adapter.

---

## Diagramma del sistema

```
                         HTTP (Browser, Bot Telegram, Scheduler)
                                       |
                                       v
+----------------------------------------------------------------------+
|  App\Http\Controllers (thin)                                         |
|  - parse request, auth/CSRF, redirect                                |
|  - delega ai moduli per la business logic                            |
+--------------------------+-------------------------------------------+
                           |
                           v   (DI via container Laravel)
+----------------------------------------------------------------------+
|  app/Modules/<Nome>/ ........... CORE DOMINIO (DDD-lite)             |
|                                                                      |
|   Services/    -> Use case / orchestrazione (transazioni, eventi)    |
|   Rules/       -> Logica pura, senza side-effect (testabili in iso.) |
|   Contracts/   -> Interfacce (porte verso il legacy / esterno)       |
|   Adapters/    -> Implementazioni dei contracts (anti-corruption)    |
|   Enums/       -> Stati, tipi, ruoli                                 |
|   Events/      -> DTO immutabili dispatcheati (Laravel Events)       |
|   Listeners/   -> Reazioni cross-modulo                              |
|   ValueObjects -> Tipi primitivi tipizzati (codici, formati, qty)    |
+----+--------------------------------+--------------------------------+
     |                                |
     v                                v
+----------------+         +----------------------------------+
| App\Models     |         | App\Http\Services (LEGACY)       |
| Eloquent ORM   |         | PrinectService, BrtService,      |
| (tab. MySQL)   |         | OndaService, FieryService, ...   |
+--------+-------+         +----------------+-----------------+
         |                                  |
         v                                  v
   MySQL .60                        SOAP/REST esterni
   (core MES)                       (Onda SQL Server, BRT, Heidelberg, Fiery)
```

Direzione delle dipendenze: **Controller -> Modules -> {Eloquent, Adapter -> Legacy Service}**.
I moduli non chiamano direttamente HTTP/SOAP esterni: passano da `Contracts` +
`Adapters`. Eloquent è ammesso (pragmatismo): non vogliamo un repository pattern
forzato finché lo schema MySQL resta lo stesso.

---

## Convenzioni moduli

### Namespace e struttura

```
app/Modules/<Nome>/
├── Contracts/      # interfacce (porte)
├── Adapters/       # adapter sui service legacy
├── Services/       # use case (un metodo pubblico = un caso d'uso)
├── Rules/          # logica pura statica/iniettabile
├── Enums/          # PHP 8.1 backed enum (string|int)
├── Events/         # readonly + Dispatchable
├── Listeners/      # reazioni event-driven
├── ValueObjects/   # readonly, immutabili, factory `from(...)`
├── Exceptions/     # eccezioni di dominio
└── StateMachine/   # opzionale (es. Fasi)
```

### Stile codice

- `declare(strict_types=1)` in **tutti** i file PHP del modulo.
- Classi `final` salvo specifica necessità di estensione.
- `readonly` per ValueObjects e DTO degli Events.
- Costruttore con DI: `private readonly Foo $foo` (constructor property promotion).
- Metodi pubblici tipizzati ovunque (input + return).
- Niente facade Eloquent dentro le `Rules/` (devono essere pure / testabili
  senza DB). Se hai bisogno di persistenza, è un `Service`.

### Eventi

Gli eventi sono nel namespace `App\Modules\<Nome>\Events\<Nome>Event` e usano
`Illuminate\Foundation\Events\Dispatchable`. Sono **fatti accaduti** (passato:
`FaseTerminata`, `CommessaCompletata`), mai imperativi. I listener registrati
in `App\Providers\EventServiceProvider` reagiscono in modo decoupled.

Esempio (vero, da `Fasi/Services/FaseTransitionService.php`):

```php
FaseAvviata::dispatch($fase, $operatore, $isRipresa);
FaseTerminata::dispatch($fase, $operatore);
```

### Value Objects

Costruttori privati + factory `from(...)` con validazione:

```php
final readonly class CodiceTracking
{
    private function __construct(public string $value) {}

    public static function from(string $raw): self
    {
        $v = strtoupper(trim($raw));
        if (!preg_match('/^\d{12}$/', $v)) {
            throw new InvalidArgumentException("Tracking BRT non valido: {$raw}");
        }
        return new self($v);
    }
}
```

---

## I moduli

11 moduli totali. Ogni modulo ha un proprio `README.md` con dettagli e
dipendenze. Qui solo lo scopo + entry point.

### Macchine

**Scopo:** registry delle macchine fisiche (XL106, JOH, BOBST, Piegaincolla,
ZUND, Indigo, ...) con regole di calendario/turni e velocità nominale.

- **Entry point:** `MacchinaRegistry::find('XL106')` ritorna `MacchinaInterface`.
- **Service:** `CalcoloOreService` — stima ore lavorazione date copie e tiratura.
- **Note:** fonte di verità in-memory; in futuro tabella `macchine`.

```php
$xl106 = MacchinaRegistry::find('XL106');
$ore   = app(CalcoloOreService::class)->stima($xl106, $copie = 5000);
```

### Fasi

**Scopo:** macchina a stati di `OrdineFase` (0=non iniziata, 1=pronto, 2=avviato,
3=terminato, 4=consegnato, + pause stringhe + 'EXT'). Centro nevralgico del MES.

- **Service:** `FaseTransitionService::transizione($fase, $nuovoStato, $op)`
  — valida via `Transizioni::validate()`, persiste in transazione, audita,
  dispatcha `FaseAvviata` / `FaseTerminata`.
- **Eventi:** `FaseAvviata`, `FaseTerminata` (consumati da Scheduling +
  Notifiche).
- **Rules:** `PausaRule::canPausa()`, `Transizioni` (registry transizioni
  legali).

### Operatori

**Scopo:** matrice permessi per `RuoloOperatore` (admin, owner, capo reparto,
operatore, prestampa, spedizione).

- **Service:** `PermessiService::check($op, Permesso::ModificaFase, $faseCtx)`.
- **Combina:** `PermessiMatrix` (livello ruolo) + `AssegnazioneFaseRule`
  (controllo reparto sul context `OrdineFase`).
- **Sostituisce gradualmente:** check sparsi tipo `Auth::user()->ruolo === 'admin'`.

### Stampa

**Scopo:** astrazione sopra Prinect (Heidelberg XL106) e Fiery (digitale Indigo)
per ottenere job in stampa, fogli prodotti, tempi avviamento/esecuzione.

- **Contract:** `StampaIntegrationInterface` (4 metodi: `getId`,
  `getJobInStampa`, `getCopieFatte`, `getTempiAvviamentoEsecuzione`).
- **Adapters:** `PrinectAdapter`, `FieryAdapter` — wrappano i service legacy
  (`App\Http\Services\PrinectService`, `FieryService`) **senza modificarli**.
- **Service:** `StampaQuotaService` — calcoli tiratura/quota.

### Magazzino

**Scopo:** carico/scarico/rettifica articoli carta + audit trail.

- **Service:** `MovimentoService::registraMovimento(codArt, tipo, qty, causale, meta)`
  — valida, aggiorna giacenza, persiste con `giacenza_dopo` (transazione DB).
  `annullaMovimento(id, motivo)` crea movimento opposto, **non cancella** la
  riga (audit).
- **Service:** `GiacenzaService` (read + delta).
- **Eventi:** `SottoSogliaEvento` -> Listener `NotificaSottoSogliaListener`.

### Spedizione

**Scopo:** tracking BRT, ritardi, multi-spedizione, note consegne owner↔spedizione.

- **Service:** `TrackingService::aggiornaStato($ddt)` — chiama BRT SOAP,
  aggiorna campi cache su `ddt_spedizioni`, dispatcha
  `SpedizioneInRitardo` se `RitardoRule` scatta.
- **Service:** `NoteConsegneService` — note bidirezionali con polling.
- **VO:** `CodiceTracking`, `IndirizzoDestinazione`.

### Scheduling (Mossa 37)

**Scopo:** prioritizzazione 4-livelli per la dashboard operatore + propagazione
cascata.

- **Service:** `PrioritaService::calcolaPriorita($fase): float` — combina
  Disponibilità, Urgenza, BatchAffinity, Sequenza fasi in punteggio scalare.
- **Service:** `PropagazioneService` — cascata stato dopo `FaseTerminata`.
- **Rules pure:** `SequenzaFasiRule`, `UrgenzaRule`, `BatchAffinityRule`,
  `SetupChangeRule`.

### Documenti

**Scopo:** generazione etichette (Data Matrix), PDF schede produzione, sync
Excel bidirezionale Prinect.

- **Service:** `DocumentoService::genera(TipoDocumento::Etichetta, $context): string`
  ritorna path del file generato.
- **Generators:** `EtichettaGenerator` (modulare). Altri formati delegati al
  legacy.
- **Service:** `ExcelSyncService` (bidirezionale ogni 2 min via queue).

### Notifiche

**Scopo:** fan-out su canali (Telegram, Email, BrowserPush) con routing
automatico per priorità.

- **Service:** `NotificaService::notificaOperatore($op, $msg, PrioritaNotifica::Alta)`.
- **Senders:** `TelegramSender`, `EmailSender`, `BrowserPushSender` —
  registrati nel ServiceProvider.
- **Templates:** `RitardoSpedizione`, `SottoSogliaCarta`.

### Carta

**Scopo:** anagrafica articoli carta (tabella `articoli`, prefisso `02W.`),
parsing codice Onda, conversioni fogli↔kg, compatibilità macchina.

- **Service:** `AnagraficaCartaService::cerca($codArt)` -> `?Articolo`.
- **VO:** `CodiceArticoloOnda` (parsing 5 segmenti), `Formato`.
- **Rules:** `CompatibilitaMacchinaRule`, `ConversioneFogliKgRule`.

### Commessa

**Scopo:** vista aggregata della commessa (stato derivato dalle fasi, qty
totale/consegnata, urgenza).

- **Service:** `CommessaService::getStatoAggregato('0067164-26')` -> `StatoCommessa`.
- **Eventi:** `CommessaCompletata`, `CommessaConsegnata`.
- **Rule:** `StatoDerivatoRule` (pura, dato un `Collection<OrdineFase>`).

---

## Strangler Fig: come migrare codice legacy

Il pattern Strangler Fig ci permette di sostituire pezzi di legacy senza mai
rompere produzione. Procedura standard quando si "modularizza" una funzione:

1. **Identifica il caso d'uso** nel codice legacy (es. metodo grasso in
   `App\Http\Controllers\OrdineController` o in un service `App\Http\Services`).
2. **Crea il modulo** (o aggiungi al modulo esistente) con:
   - un'**interfaccia** in `Contracts/` se il legacy parla a un sistema esterno
     (es. SOAP);
   - un **Adapter** in `Adapters/` che implementa l'interfaccia *delegando* al
     service legacy esistente — non riscrivere subito;
   - un **Service** in `Services/` che orchestra usando il contract.
3. **Cambia il chiamante (Controller / Job / Command)** per usare il nuovo
   service modulare via DI.
4. Il legacy resta vivo ma **non più chiamato direttamente** dai Controller.
5. Quando puoi, sostituisci l'Adapter con un'implementazione "pulita" e
   rimuovi il service legacy.

Esempio reale (`Stampa`):

```
Prima:
  PrinectController -> App\Http\Services\PrinectService::getDevices()

Dopo (passo 2):
  PrinectController -> StampaIntegrationInterface (PrinectAdapter)
                                                  -> PrinectService (legacy)

Domani (passo 5):
  PrinectController -> StampaIntegrationInterface (PrinectClientNuovo)
  // PrinectService legacy eliminato
```

Regole d'oro:

- **Mai** modificare il service legacy quando lo wrappi (anti-corruption layer).
- **Mai** rompere chi già lo usa: lascia entrambi i percorsi finché non hai
  migrato tutto.
- I moduli **non importano** `App\Http\Controllers`: dipendenza unidirezionale.
- Le `Rules/` non parlano col DB: se devi leggere, fallo nel `Service` e passa
  i dati alla rule.

---

## Test strategy

Tre livelli, dal più veloce al più lento:

### 1. Unit test — Rules (millisecondi, zero DB)

`Rules/` sono pure: input -> output. Test triviali.

```php
public function test_pausa_consentita_solo_se_avviato(): void
{
    $fase = OrdineFase::factory()->make(['stato' => 2]);
    $this->assertTrue(PausaRule::canPausa($fase));

    $fase->stato = 3;
    $this->assertFalse(PausaRule::canPausa($fase));
}
```

### 2. Integration test — Services (con DB SQLite in-memory)

Per i Service che orchestrano transazioni / persistenza. `RefreshDatabase` +
`Event::fake()` per asserire dispatch.

```php
public function test_transizione_avvia_fase_e_dispatcha_evento(): void
{
    Event::fake();
    $fase = OrdineFase::factory()->create(['stato' => 1]);

    app(FaseTransitionService::class)->transizione($fase, 2);

    Event::assertDispatched(FaseAvviata::class);
    $this->assertSame(2, $fase->fresh()->stato);
}
```

### 3. Feature test — Controller (HTTP end-to-end)

Smoke test per i flussi critici (login, transizione fase, registrazione
movimento magazzino). Usano route reali + middleware reali.

### Quando NON testare

- Adapters che sono "pass-through" verso un service legacy già coperto.
- Eloquent semplice (scope, relazioni): test del framework, non nostro.

### Comandi

```bash
php artisan test --filter=Modules        # solo moduli
php artisan test --filter=FaseTransition # singolo
```

I test dei moduli vivono in `tests/Unit/Modules/<Nome>/` e
`tests/Feature/Modules/<Nome>/`.
