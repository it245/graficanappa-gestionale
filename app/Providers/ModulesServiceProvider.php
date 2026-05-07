<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

// Stampa
use App\Modules\Stampa\Contracts\StampaIntegrationInterface;
use App\Modules\Stampa\Adapters\PrinectAdapter;
use App\Modules\Stampa\Adapters\FieryAdapter;

// Notifiche
use App\Modules\Notifiche\Contracts\NotificaSenderInterface;
use App\Modules\Notifiche\Senders\TelegramSender;
use App\Modules\Notifiche\Senders\EmailSender;
use App\Modules\Notifiche\Senders\BrowserPushSender;
use App\Modules\Notifiche\Services\NotificaService;
use App\Modules\Notifiche\Rules\CanaleSceltaRule;

// Documenti
use App\Modules\Documenti\Contracts\DocumentoGeneratorInterface;
use App\Modules\Documenti\Generators\EtichettaGenerator;

// Scheduling
use App\Modules\Scheduling\Contracts\SchedulerEngineInterface;

// Onda
use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Onda\Adapters\OndaErpAdapter;

// Prinect (modulo dedicato XL106)
use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\Adapters\PrinectHttpAdapter;

// Presenze (NetTime)
use App\Modules\Presenze\Contracts\TimbratureSourceInterface;
use App\Modules\Presenze\Adapters\NetTimeShareAdapter;

/**
 * ModulesServiceProvider
 *
 * Aggrega il wiring del container Laravel per i moduli DDD in app/Modules/*.
 * Tenuto SEPARATO da AppServiceProvider per ridurre il blast-radius di
 * modifiche durante la migrazione architetturale (def2.0).
 *
 * Convenzioni:
 *  - register(): solo bind/singleton, niente side-effects, niente DB.
 *  - boot():     event listeners e wiring runtime.
 *  - Tutti i listeners registrati qui sono stub: la logica vera arriva
 *    nelle iterazioni successive (vedi TODO nei singoli Listener).
 */
final class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Mappa Event => [Listener, ...]
     *
     * Tenuta inline (non delegata a EventServiceProvider) perché
     * Laravel 12 spinge verso provider espliciti. Se in futuro la
     * lista cresce molto, estrarre in ModulesEventServiceProvider.
     *
     * @var array<class-string, list<class-string>>
     */
    private const LISTEN = [
        \App\Modules\Fasi\Events\FaseAvviata::class => [
            \App\Modules\Fasi\Listeners\TracciaInizioFaseListener::class,
        ],
        \App\Modules\Fasi\Events\FaseTerminata::class => [
            \App\Modules\Fasi\Listeners\PropagaFasiSuccessiveListener::class,
            \App\Modules\Fasi\Listeners\NotificaCommessaCompletataListener::class,
        ],
        \App\Modules\Magazzino\Events\SottoSogliaEvento::class => [
            \App\Modules\Magazzino\Listeners\NotificaSottoSogliaListener::class,
        ],
        \App\Modules\Spedizione\Events\SpedizioneInRitardo::class => [
            \App\Modules\Spedizione\Listeners\NotificaSpedizioneInRitardoListener::class,
        ],
    ];

    /**
     * Bind di interfacce e implementazioni dei moduli.
     */
    public function register(): void
    {
        /*
         | Stampa — StampaIntegrationInterface
         |
         | Risolve l'integrazione di stampa di default (Prinect Pressroom
         | Manager o EFI Fiery). Cambiabile tramite env MES_STAMPA_DEFAULT
         | o config('mes.stampa_default'). Le classi *Adapter concrete
         | restano risolvibili direttamente per chi sa quale gli serve
         | (es. job di sync Prinect-only o Fiery-only).
         */
        $this->app->bind(StampaIntegrationInterface::class, function (Application $app) {
            $driver = (string) config('mes.stampa_default', 'prinect');

            return match ($driver) {
                'fiery'   => $app->make(FieryAdapter::class),
                'prinect' => $app->make(PrinectAdapter::class),
                default   => $app->make(PrinectAdapter::class),
            };
        });

        /*
         | Prinect — PrinectApiInterface
         |
         | Modulo dedicato Heidelberg XL106. Espone una shape REST pulita
         | (Contracts/PrinectApiInterface) che i Service del modulo consumano.
         | L'adapter di default usa la Http facade di Laravel; in test si può
         | sostituire con un fake PrinectApi senza toccare i Service.
         |
         | NB: PrinectAdapter (Modules/Stampa) e PrinectService legacy
         | restano in produzione come wrapper di compatibilità.
         */
        $this->app->bind(PrinectApiInterface::class, PrinectHttpAdapter::class);

        /*
         | Notifiche — NotificaSenderInterface
         |
         | Risolto contestualmente per "canale": l'interfaccia di default
         | mappa sul primo canale di config('mes.notifiche.canali_default'),
         | mentre i tre Sender concreti restano risolvibili by-class per
         | uso esplicito (es. NotificaService che cicla sui canali_critici).
         |
         | Esempio uso:
         |   $sender = app(NotificaSenderInterface::class);          // default
         |   $sender = app(TelegramSender::class);                   // esplicito
         */
        $this->app->bind(NotificaSenderInterface::class, function (Application $app) {
            $canali  = (array) config('mes.notifiche.canali_default', ['telegram']);
            $canale  = $canali[0] ?? 'telegram';

            return match ($canale) {
                'email'         => $app->make(EmailSender::class),
                'browser_push'  => $app->make(BrowserPushSender::class),
                'telegram'      => $app->make(TelegramSender::class),
                default         => $app->make(TelegramSender::class),
            };
        });

        /*
         | Notifiche — NotificaService (orchestratore con registry sender)
         |
         | Singleton: il registry è popolato una sola volta col fan-out
         | Telegram + Email + BrowserPush. Listener (es. NotificaSottoSogliaListener)
         | risolvono questo servizio via constructor injection.
         */
        $this->app->singleton(NotificaService::class, function (Application $app) {
            return new NotificaService(
                rule: $app->make(CanaleSceltaRule::class),
                senders: [
                    $app->make(TelegramSender::class),
                    $app->make(EmailSender::class),
                    $app->make(BrowserPushSender::class),
                ],
            );
        });

        /*
         | Documenti — DocumentoGeneratorInterface
         |
         | Per ora esiste solo EtichettaGenerator (Data Matrix per le
         | etichette commessa). Quando arriveranno SchedaProduzionePdf,
         | DdtPdf, PreventivoPdf, sostituire con un Manager-style resolver.
         */
        $this->app->bind(DocumentoGeneratorInterface::class, EtichettaGenerator::class);

        /*
         | Scheduling — SchedulerEngineInterface
         |
         | Wrap del SchedulerService legacy non ancora pronto: per non
         | rompere chi inietta l'interfaccia, bind condizionale: se
         | esiste già un'implementazione concreta nel container la usa,
         | altrimenti restituisce null e logga warning.
         |
         | TODO: implementare App\Modules\Scheduling\Services\SchedulerEngineService
         |       come wrapper su SchedulerService + Mossa37 PriorityService.
         */
        $this->app->bind(SchedulerEngineInterface::class, function () {
            // TODO: tornare a $app->make(SchedulerEngineService::class)
            //       una volta implementato. Per ora null per evitare crash
            //       su classi non ancora pronte.
            return null;
        });

        /*
         | Onda — OndaErpInterface
         |
         | Adapter sopra la connessione SQL Server `config('database.connections.onda')`.
         | I servizi del modulo Onda (OrdineSyncService, CommessaSyncService,
         | ClienteSyncService) ricevono via DI questa interfaccia, NON la
         | connessione DB raw — questo permette test unit con fake/mock
         | senza dover collegare SQL Server in CI.
         |
         | Wrapper legacy {@see \App\Services\OndaSyncService} risolve l'interfaccia
         | tramite app(OndaErpInterface::class) per backward compat con i caller
         | statici esistenti (cron `onda:sync`, ImportExcelTutto).
         */
        $this->app->bind(OndaErpInterface::class, OndaErpAdapter::class);

        /*
         | Presenze — TimbratureSourceInterface
         |
         | Sorgente delle timbrature operatori. Default = NetTimeShareAdapter
         | che legge da `nettime_timbrature` (popolata dal cron
         | `presenze:sync` ogni 5 min, share \\.34\NetTime + fallback \\.253).
         |
         | In test si può sostituire con ManualeAdapter::da([...]) senza
         | toccare i Service del modulo (PresenzeService, CalcoloOreService).
         */
        $this->app->bind(TimbratureSourceInterface::class, NetTimeShareAdapter::class);
    }

    /**
     * Wiring runtime: event listeners.
     *
     * NOTA: tutti sync per ora. Quando i listener notifica diventano
     * pesanti (Telegram + Email + Push in serie su SottoSoglia), passare
     * a ShouldQueue sul singolo Listener — l'API Event::listen rimane
     * invariata.
     */
    public function boot(): void
    {
        foreach (self::LISTEN as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
