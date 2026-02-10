<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>MES GRAFICA NAPPA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        tr.scaduta td{
               background-color: #ff0015 !important;
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
    <div class="container mt-4">
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
        
    </script>
    </body>
</html>
