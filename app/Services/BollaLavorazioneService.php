<?php

namespace App\Services;

use App\Models\Ordine;
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

    /**
     * Genera PDF scheda produzione completa per una commessa
     * (intestazione + tutte le fasi, replica scheda cartacea Grafica Nappa).
     */
    public static function streamCommessa(string $commessa)
    {
        $ordini = Ordine::with(['cliche', 'fasi.faseCatalogo.reparto', 'fasi.operatori'])
            ->where('commessa', $commessa)
            ->get();

        abort_if($ordini->isEmpty(), 404, 'Commessa non trovata');

        $fasi = collect();
        foreach ($ordini as $o) {
            $fasi = $fasi->concat($o->fasi);
        }
        $fasi = $fasi->sortBy('priorita')->values();

        $pdf = Pdf::loadView('pdf.scheda_produzione', [
            'commessa' => $commessa,
            'ordini' => $ordini,
            'ordinePrincipale' => $ordini->first(),
            'fasi' => $fasi,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("scheda_produzione_{$commessa}.pdf");
    }
}
