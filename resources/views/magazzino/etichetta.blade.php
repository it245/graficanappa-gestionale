<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; }

        .etichetta {
            width: 100mm;
            height: 70mm;
            padding: 3mm;
        }

        .header {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2mm;
            text-align: center;
        }

        .content {
            margin-top: 2mm;
        }

        .qr {
            text-align: center;
            margin-bottom: 2mm;
        }

        .field {
            font-size: 8pt;
            margin-bottom: 1mm;
            line-height: 1.3;
        }

        .field-label {
            color: #666;
            font-size: 7pt;
        }

        .field-value {
            font-weight: bold;
        }

        .qta-big {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            margin: 2mm 0;
        }
    </style>
</head>
<body>
    <div class="etichetta">
        <div class="header">
            GRAFICA NAPPA — MAGAZZINO CARTA
        </div>

        <div class="qr">
            <img src="data:image/svg+xml;base64,{{ $qrPng }}" style="width:22mm; height:22mm;" alt="QR">
        </div>

        <div class="content">
            <div class="field">
                <span class="field-label">Cod:</span>
                <span class="field-value">{{ Str::limit($articolo->codice, 30) }}</span>
            </div>

            <div class="field">
                {{ Str::limit($articolo->descrizione, 40) }}
            </div>

            <div class="field">
                {{ $articolo->formato ?? '' }}
                @if($articolo->grammatura)
                    &nbsp; {{ $articolo->grammatura }}g
                @endif
            </div>

            @if($etichetta->lotto)
            <div class="field">
                <span class="field-label">Lotto:</span>
                <span class="field-value">{{ $etichetta->lotto }}</span>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
