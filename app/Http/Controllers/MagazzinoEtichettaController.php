<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MagazzinoEtichetta;
use App\Services\QrEtichettaService;

class MagazzinoEtichettaController extends Controller
{
    /**
     * Stampa etichetta QR per un bancale.
     */
    public function stampa(Request $request, int $id)
    {
        $etichetta = MagazzinoEtichetta::with(['articolo', 'ubicazione'])->findOrFail($id);

        return QrEtichettaService::stream($etichetta);
    }
}
