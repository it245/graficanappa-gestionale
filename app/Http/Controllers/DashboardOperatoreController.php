<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

class DashboardOperatoreController extends Controller
{
   public function index()
{
    // La pagina deve caricarsi sempre, il JS penserà a proteggerla
    return view('operatore.dashboard');
}

// Nuovo metodo per i dati API
public function getDati(Request $request)
{
    $operatore = $request->attributes->get('operatore');
    if (!$operatore) return response()->json(['error' => 'Non autorizzato'], 401);

    $reparti = array_map('trim', explode(',', $operatore->reparto));
    
    // ... (tutta la tua logica di $fasiInfo e $fasiVisibili che avevi prima) ...

    return response()->json([
        'success' => true,
        'fasi' => $fasiVisibili->values(), // values() per resettare gli indici dopo sortBy
        'operatore' => $operatore
    ]);
}
}