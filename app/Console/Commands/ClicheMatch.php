<?php

namespace App\Console\Commands;

use App\Services\ClicheMatchService;
use Illuminate\Console\Command;

class ClicheMatch extends Command
{
    protected $signature = 'cliche:match';
    protected $description = 'Matcha ordini con cliché (solo fasi 0-1, rispetta override manuale)';

    public function handle(): int
    {
        $res = ClicheMatchService::matchAll();
        $this->info("Matched: {$res['matched']} | Updated: {$res['updated']}");
        return 0;
    }
}
