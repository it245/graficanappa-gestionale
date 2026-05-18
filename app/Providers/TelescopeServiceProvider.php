namespace App\Providers;

  use Illuminate\Support\Facades\Gate;
  use Laravel\Telescope\IncomingEntry;
  use Laravel\Telescope\Telescope;
  use Laravel\Telescope\TelescopeApplicationServiceProvider;

  class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
  {
      public function register(): void
      {
          $this->hideSensitiveRequestDetails();

          Telescope::filter(function (IncomingEntry $entry) {
              return true;
          });
      }

      protected function hideSensitiveRequestDetails(): void
      {
          if ($this->app->environment('local')) {
              return;
          }

          Telescope::hideRequestParameters(['_token', 'password', 'password_confirmation']);

          Telescope::hideRequestHeaders([
              'cookie',
              'x-csrf-token',
              'x-xsrf-token',
              'authorization',
          ]);
      }

      /**
       * Gate Telescope: MES usa auth custom session-based.
       * Accesso solo admin (session operatore_ruolo=admin).
       */
      protected function gate(): void
      {
          Gate::define('viewTelescope', function ($user = null) {
              return session('operatore_ruolo') === 'admin';
          });
      }
  }