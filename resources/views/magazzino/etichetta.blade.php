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
            padding: 4mm;
            border: 1px solid #333;
            display: table;
        }

        .qr-col {
            display: table-cell;
            vertical-align: top;
            width: 30mm;
            padding-right: 4mm;
        }

        .info-col {
            display: table-cell;
            vertical-align: top;
        }

        .header {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2mm;
            line-height: 1.3;
        }

        .field {
            font-size: 8pt;
            margin-bottom: 1.5mm;
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
            font-size: 14pt;
            font-weight: bold;
            margin: 2mm 0;
        }
    </style>
</head>
<body>
    <div class="etichetta">
        <div class="qr-col">
            {{-- QR Code generato come immagine SVG inline --}}
            <div style="width:26mm; height:26mm;">
                {!! QrCode::size(98)->generate($qrUrl) !!}
            </div>
        </div>
        <div class="info-col">
            <div class="header">
                GRAFICA NAPPA<br>
                MAGAZZINO CARTA
            </div>

            <div class="field">
                <span class="field-label">Cod:</span>
                <span class="field-value">{{ $articolo->codice }}</span>
            </div>

            <div class="field">
                {{ $articolo->descrizione }}
            </div>

            <div class="field">
                {{ $articolo->formato ?? '' }}
                @if($articolo->grammatura)
                    &nbsp; {{ $articolo->grammatura }}g
                @endif
            </div>

            <div class="qta-big">
                Qta: {{ number_format($etichetta->quantita_iniziale, 0, ',', '.') }} {{ $articolo->um }}
            </div>

            @if($etichetta->lotto)
            <div class="field">
                <span class="field-label">Lotto:</span>
                <span class="field-value">{{ $etichetta->lotto }}</span>
            </div>
            @endif

            @if($ubicazione)
            <div class="field">
                <span class="field-label">Ubicazione:</span>
                <span class="field-value">{{ $ubicazione->codice }}</span>
            </div>
            @endif

            <div class="field">
                <span class="field-label">Data:</span>
                {{ $etichetta->created_at->format('d/m/Y') }}
            </div>
        </div>
    </div>
</body>
</html>
