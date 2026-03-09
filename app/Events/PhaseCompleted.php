<?php

namespace App\Events;

use App\Models\OrdineFase;
use Illuminate\Foundation\Events\Dispatchable;

class PhaseCompleted
{
    use Dispatchable;

    public function __construct(public OrdineFase $fase)
    {
    }
}
