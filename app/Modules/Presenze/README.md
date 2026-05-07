# Modulo Presenze

Gestione presenze operatori basata sul software esterno **NetTime**
(server `.34`, share di rete `.253`) con calcolo ore lavorate,
assenze e regole di compatibilità reparto.

## Struttura

```
Contracts/TimbratureSourceInterface  -> astrazione sorgente (NetTime/Manuale/API)
Adapters/NetTimeShareAdapter         -> implementazione su nettime_timbrature
Adapters/ManualeAdapter              -> implementazione in-memory (fallback)
Services/PresenzeService             -> stato del giorno + lookup oggi
Services/TimbratureSyncService       -> sync da file BKP ogni 5 min
Services/CalcoloOreService           -> ore giorno/settimana/mese + pause
Services/AssenzeService              -> CRUD assenze (presenze_assenze)
Rules/ValidazioneOrarioRule          -> 6-22 lun-ven, 24h XL106
Rules/CalcoloStraordinariRule        -> >40h = straordinario 25%, >48h = 35%
Rules/CompatibilitaTurnoReparto      -> abilitazioni reparto
ValueObjects/PeriodoPresenza         -> ingresso + uscita (eventualmente aperto)
ValueObjects/BadgeOperatore          -> matricola normalizzata 6 cifre
ValueObjects/OreLavorate             -> ore + minuti immutabile
Enums/TipoTimbratura                 -> IN/OUT/PAUSA/RIENTRO
Enums/TipoAssenza                    -> FERIE/MALATTIA/PERMESSO/SCIOPERO/...
Enums/StatoPresenza                  -> PRESENTE/IN_PAUSA/ASSENTE/...
Events/TimbraturaRegistrata          -> dopo sync NetTime
Events/OperatoreNonTimbrato          -> alert > 30 min
Events/StraordinariSuperati          -> oltre 40h settimanali
```

## Vincoli architetturali

- Schema DB **invariato**: `nettime_timbrature` e `nettime_anagrafica`
  restano popolate dal command legacy `presenze:sync`. La nuova tabella
  `presenze_assenze` è creata on-demand dal `AssenzeService`.
- Path share `\\192.168.1.34\NetTime\TIMBRA\TIMBRACP.BKP` (primario) +
  `\\192.168.1.253\timbrature\timbrature.txt` (fallback) **non
  modificati**.
- Task scheduled `presenze:sync` ogni 5 min **non rotto**: il command
  resta operativo, il `TimbratureSyncService` nasce come servizio
  riutilizzabile (delegato in futuro).
- Log `Log::info('Sync NetTime', …)` preservato.

## Procedura sindacale

Il MES traccia produttività individuale → art.4 Statuto Lavoratori.
Prima del rollout completo serve:
- accordo RSU/RSA o autorizzazione ITL
- Informativa GDPR lavoratori art.13 firmata da ogni dipendente

Il modulo è progettato per supportare `TipoAssenza::Sciopero` e
distinguerlo dalle altre assenze, requisito per le comunicazioni RSU.

## Wiring

Bind in `App\Providers\ModulesServiceProvider`:

```php
$this->app->bind(TimbratureSourceInterface::class, NetTimeShareAdapter::class);
```

Fallback testabile:

```php
$this->app->bind(TimbratureSourceInterface::class, fn () =>
    ManualeAdapter::da([['badge' => '000123', 'ingresso' => '2026-05-07 08:00', 'uscita' => '2026-05-07 17:00']])
);
```
