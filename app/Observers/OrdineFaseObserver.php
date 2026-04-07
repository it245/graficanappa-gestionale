<?php

namespace App\Observers;

use App\Models\OrdineFase;
use App\Services\AuditService;

class OrdineFaseObserver
{
    public function updating(OrdineFase $fase): void
    {
        // Log solo cambio stato (il campo più importante)
        if ($fase->isDirty('stato')) {
            AuditService::statoFase(
                $fase->id,
                $fase->getOriginal('stato'),
                $fase->stato,
                $fase->ordine_id ? "commessa: " . ($fase->ordine->commessa ?? '?') : null
            );
        }
    }

    public function deleted(OrdineFase $fase): void
    {
        AuditService::log('delete', 'OrdineFase', $fase->id, [
            'fase' => $fase->fase,
            'stato' => $fase->stato,
            'ordine_id' => $fase->ordine_id,
        ]);
    }
}
