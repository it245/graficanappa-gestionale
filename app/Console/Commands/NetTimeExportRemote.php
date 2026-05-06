<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NetTimeExportRemote extends Command
{
    protected $signature = 'nettime:export-remote {--sync : esegui anche presenze:sync dopo export}';
    protected $description = 'Forza export NetTime su PC .34 via WinRM, poi (opzionalmente) sincronizza in MES';

    public function handle()
    {
        $host = (string) env('NETTIME_PC_HOST', '192.168.1.34');
        $user = (string) env('NETTIME_PC_USER', '');
        $pass = (string) env('NETTIME_PC_PASS', '');
        $cmd  = (string) env('NETTIME_EXPORT_CMD', '');

        if ($user === '' || $pass === '' || $cmd === '') {
            $this->error('Mancano NETTIME_PC_USER / NETTIME_PC_PASS / NETTIME_EXPORT_CMD nel .env');
            return 1;
        }

        // Costruisci comando PowerShell che invoca remote
        // Uso: New-PSSession con credenziali, lancia comando, restituisce output
        $script = <<<PS
\$pwd = ConvertTo-SecureString '$pass' -AsPlainText -Force;
\$cred = New-Object System.Management.Automation.PSCredential('$user', \$pwd);
try {
    \$result = Invoke-Command -ComputerName $host -Credential \$cred -ScriptBlock { & cmd.exe /c '$cmd' } -ErrorAction Stop;
    Write-Output "OK: \$result";
    exit 0;
} catch {
    Write-Error "FAIL: \$_";
    exit 1;
}
PS;

        // Salva script temp + esegui
        $tmp = storage_path('app/nettime_export_' . uniqid() . '.ps1');
        file_put_contents($tmp, $script);

        $this->info("Lancio export remoto su $host...");
        $output = [];
        $rc = -1;
        exec("powershell -ExecutionPolicy Bypass -File \"$tmp\" 2>&1", $output, $rc);
        @unlink($tmp);

        $this->line(implode("\n", $output));

        if ($rc !== 0) {
            Log::error('NetTime export remoto fallito', ['rc' => $rc, 'output' => $output]);
            $this->error("Export remoto fallito (rc=$rc)");
            return 1;
        }

        Log::info('NetTime export remoto OK');

        if ($this->option('sync')) {
            $this->info('Eseguo presenze:sync...');
            $this->call('presenze:sync');
        }

        return 0;
    }
}
