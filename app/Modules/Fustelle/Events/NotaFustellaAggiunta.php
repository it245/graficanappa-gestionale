<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Events;

use App\Models\OrdineFase;
use App\Modules\Fustelle\ValueObjects\NotaFustella;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emesso quando viene appesa una nota fustella a una fase di lavorazione.
 *
 * NB: la nota viene scritta sul campo esistente `ordine_fasi.note`
 * (non viene aggiunto/modificato lo schema). Lo storico è preservato.
 */
class NotaFustellaAggiunta
{
    use Dispatchable, SerializesModels;

    public OrdineFase $fase;

    /**
     * @var NotaFustella nota appena appesa (non serializzabile come model:
     *                   lasciata pubblica come VO immutabile).
     */
    public NotaFustella $nota;

    public function __construct(OrdineFase $fase, NotaFustella $nota)
    {
        $this->fase = $fase;
        $this->nota = $nota;
    }
}
