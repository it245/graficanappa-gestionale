<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Rules;

use App\Models\DdtSpedizione;
use App\Modules\Spedizione\Enums\EsitoBrt;

/**
 * Regola: identifica i DDT multi-spedizione (1 DDT → N spedizioni BRT).
 *
 * Sono casi non tracciabili singolarmente e vanno esclusi dalle email
 * automatiche di ritardo (rischio falsi positivi).
 */
final class MultiSpedizioneRule
{
    /**
     * Considera multi-spedizione se:
     *  - lo stato cache è "MULTI-SPEDIZIONE" (vedi BrtService::getTrackingByDDT)
     *  - oppure l'esito BRT memorizzato è -22.
     */
    public function eMultiSpedizione(DdtSpedizione $ddt): bool
    {
        $stato = (string) ($ddt->brt_stato ?? '');

        if (stripos($stato, 'MULTI') !== false) {
            return true;
        }

        // Se in futuro venisse persistito un campo esito numerico, supportiamolo.
        $esitoRaw = $ddt->getAttribute('brt_esito');
        if ($esitoRaw !== null) {
            return EsitoBrt::fromValoreOSconosciuto($esitoRaw)->eMultiSpedizione();
        }

        return false;
    }

    /**
     * I DDT multi-spedizione vanno esclusi dalle notifiche di ritardo.
     */
    public function escludiDaNotifica(DdtSpedizione $ddt): bool
    {
        return $this->eMultiSpedizione($ddt);
    }
}
