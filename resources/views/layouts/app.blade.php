<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="op-token" content="{{ $opToken ?? '' }}">
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

    // ===== Op Token: autenticazione per-tab =====
    (function() {
        // Prendi token dalla meta tag (iniettato dal server) o dalla URL
        var metaToken = document.querySelector('meta[name="op-token"]');
        var urlToken = new URLSearchParams(window.location.search).get('op_token');
        var token = urlToken || (metaToken ? metaToken.getAttribute('content') : '') || sessionStorage.getItem('op_token') || '';

        // Salva in sessionStorage (isolato per tab)
        if (token) {
            sessionStorage.setItem('op_token', token);
        }

        // Funzione globale per ottenere il token
        window.opToken = function() {
            return sessionStorage.getItem('op_token') || '';
        };

        // Aggiungi op_token a tutti i link <a> interni
        function appendTokenToLinks() {
            var tk = window.opToken();
            if (!tk) return;

            document.querySelectorAll('a[href]').forEach(function(a) {
                var href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('data:')) return;

                // Solo link interni (stesso host o relativi)
                try {
                    var url = new URL(href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    if (url.searchParams.has('op_token')) return;
                    url.searchParams.set('op_token', tk);
                    a.setAttribute('href', url.pathname + url.search + url.hash);
                } catch(e) {}
            });
        }

        // Aggiungi op_token ai form
        function appendTokenToForms() {
            var tk = window.opToken();
            if (!tk) return;

            document.querySelectorAll('form').forEach(function(form) {
                if (form.querySelector('input[name="op_token"]')) return;
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'op_token';
                input.value = tk;
                form.appendChild(input);
            });
        }

        // Esegui al caricamento e dopo cambiamenti DOM
        document.addEventListener('DOMContentLoaded', function() {
            appendTokenToLinks();
            appendTokenToForms();
        });

        // Observer per link/form aggiunti dinamicamente
        var observer = new MutationObserver(function() {
            appendTokenToLinks();
            appendTokenToForms();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });

        // Intercetta tutte le fetch: aggiunge CSRF + Op-Token + gestisce 419
        var originalFetch = window.fetch;
        window.fetch = function(url, options) {
            options = options || {};
            options.headers = options.headers || {};

            // Aggiorna CSRF token
            if (options.headers['X-CSRF-TOKEN']) {
                options.headers['X-CSRF-TOKEN'] = csrfToken();
            }

            // Aggiungi X-Op-Token a tutte le richieste
            var tk = window.opToken();
            if (tk) {
                options.headers['X-Op-Token'] = tk;
            }

            return originalFetch.call(this, url, options).then(function(response) {
                if (response.status === 419) {
                    alert('Sessione scaduta. La pagina verra ricaricata.');
                    window.location.reload();
                    return Promise.reject('Token scaduto');
                }
                return response;
            });
        };
    })();
    </script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    </body>
</html>
