<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\MagazzinoEtichetta;
use App\Services\QrEtichettaService;
use App\Modules\Documenti\Enums\FormatoDocumento;
use App\Modules\Documenti\Enums\TipoDocumento;

class MagazzinoEtichettaController extends Controller
{
    /**
     * Stampa etichetta QR per un bancale.
     *
     * Strangler Fig: la generazione PDF resta nel service legacy `QrEtichettaService`
     * (dompdf wrapper), ma logghiamo l'evento centralmente con i tipi del modulo
     * Documenti per uniformare audit e telemetry.
     */
    public function stampa(Request $request, int $id)
    {
        $etichetta = MagazzinoEtichetta::with(['articolo', 'ubicazione'])->findOrFail($id);

        Log::info('Etichetta generata', [
            'tipo' => TipoDocumento::Etichetta->value,
            'formato' => FormatoDocumento::Pdf->value,
            'mime' => FormatoDocumento::Pdf->mimeType(),
            'qr_code' => $etichetta->qr_code,
            'articolo_id' => $etichetta->articolo?->id,
            'ubicazione_id' => $etichetta->ubicazione?->id,
            'sorgente' => 'magazzino_qr',
        ]);

        return QrEtichettaService::stream($etichetta);
    }
}
