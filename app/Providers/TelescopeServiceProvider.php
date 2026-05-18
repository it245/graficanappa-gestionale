namespace App\Providers;

  use App\Models\User;
  use Illuminate\Support\Facades\Gate;
  use Laravel\Telescope\IncomingEntry;
  use Laravel\Telescope\Telescope;
  use Laravel\Telescope\TelescopeApplicationServiceProvider;

  class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
  {
      public function register(): void
      {
          // Telescope::night();

          $this->hideSensitiveRequestDetails();

          // Loggati tutti i request in production per debug MES (no filter)
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
       * Gate Telescope: solo admin Giovanni accede.
       * NB: tablet operatori NON loggati come User → nessun accesso.
       */
      protected function gate(): void
      {
          Gate::define('viewTelescope', function ($user = null) {
              if (! $user) return false;
              return in_array($user->email ?? '', [
                  'it@graficanappa.com',
              ]);
          });
      }
  }