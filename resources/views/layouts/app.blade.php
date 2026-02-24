<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MES GRAFICA NAPPA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        tr.scaduta td{
               background-color: #e8747a !important;
            color: #000000 !important;
            font-weight: 700;
        }

        tr.warning-strong td {
            background-color: #f96f2a !important;
            color: #000000 !important;
            font-weight: 700;
        }

        tr.warning-light td {
            background-color: #ffd07a !important;
            color: #000000 !important;
            font-weight: 700;
        }
    
          </style>
</head>
<body>
  <div class="container-fluid px-0 mt-1">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-2 mt-1 mb-0" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-2 mt-1 mb-0" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // CSRF token globale: tutte le fetch lo leggono dalla meta tag
    window.csrfToken = function() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    };

    // Intercetta tutte le fetch: aggiunge CSRF header e gestisce 419 (Page Expired)
    (function() {
        var originalFetch = window.fetch;
        window.fetch = function(url, options) {
            options = options || {};
            if (options.headers && options.headers['X-CSRF-TOKEN']) {
                options.headers['X-CSRF-TOKEN'] = csrfToken();
            }
            return originalFetch.call(this, url, options).then(function(response) {
                if (response.status === 419) {
                    // Token scaduto: ricarica la pagina per ottenerne uno nuovo
                    alert('Sessione scaduta. La pagina verra ricaricata.');
                    window.location.reload();
                    return Promise.reject('Token scaduto');
                }
                return response;
            });
        };
    })();
    </script>
    </body>
</html>
