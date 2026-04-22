<?php

namespace App\Services;

use App\Models\OrdineFase;
use Barryvdh\DomPDF\Facade\Pdf;

class BollaLavorazioneService
{
    /**
     * Genera PDF bolla lavorazione per una fase specifica.
     * Restituisce il download via response.
     */
    public static function stream(int $faseId)
    {
        $fase = OrdineFase::with(['ordine.cliche', 'faseCatalogo.reparto', 'operatori'])->findOrFail($faseId);

        $pdf = Pdf::loadView('pdf.bolla_lavorazione', [
            'fase' => $fase,
            'ordine' => $fase->ordine,
            'cliche' => $fase->ordine?->cliche,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $nome = "bolla_{$fase->ordine?->commessa}_{$fase->fase}.pdf";

        return $pdf->download($nome);
    }
}
