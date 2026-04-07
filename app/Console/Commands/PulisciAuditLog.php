<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PulisciAuditLog extends Command
{
    protected $signature = 'audit:pulisci {--giorni=90 : Giorni di retention}';
    protected $description = 'Elimina audit log più vecchi di N giorni (default 90)';

    public function handle()
    {
        $giorni = (int) $this->option('giorni');
        $cutoff = Carbon::now()->subDays($giorni);

        $deleted = DB::table('audit_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Eliminati {$deleted} audit log precedenti al {$cutoff->format('d/m/Y')}");

        return 0;
    }
}
