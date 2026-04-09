<?php

namespace App\Services;

use App\Models\MagazzinoEtichetta;
use Barryvdh\DomPDF\Facade\Pdf;

class QrEtichettaService
{
    /**
     * Genera il PDF dell'etichetta QR per un bancale.
     */
    public static function generaPdf(MagazzinoEtichetta $etichetta)
    {
        $etichetta->load(['articolo', 'ubicazione']);

        $qrUrl = url("/magazzino/scan?qr={$etichetta->qr_code}");

        // Genera QR come SVG, converti in PNG via GD (no imagick)
        $svgString = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(150)->generate($qrUrl);

        // Workaround: usa SVG embedded come data URI per dompdf
        $qrPng = base64_encode($svgString);

        $data = [
            'etichetta' => $etichetta,
            'qrUrl' => $qrUrl,
            'qrPng' => $qrPng,
            'articolo' => $etichetta->articolo,
            'ubicazione' => $etichetta->ubicazione,
        ];

        $pdf = Pdf::loadView('magazzino.etichetta', $data);
        // Etichetta 100x70mm
        $pdf->setPaper([0, 0, 283.46, 198.43], 'landscape');

        return $pdf;
    }

    /**
     * Stream PDF etichetta nel browser.
     */
    public static function stream(MagazzinoEtichetta $etichetta)
    {
        $pdf = self::generaPdf($etichetta);
        return $pdf->stream("etichetta_{$etichetta->qr_code}.pdf");
    }
}
