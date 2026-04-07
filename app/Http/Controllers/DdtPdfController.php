<?php

namespace App\Http\Controllers;

use App\Services\DdtPdfService;

class DdtPdfController extends Controller
{
    /**
     * Visualizza PDF del DDT nel browser.
     * GET /ddt/pdf/{numeroDdt}
     */
    public function genera($numeroDdt)
    {
        return DdtPdfService::stream($numeroDdt);
    }
}
