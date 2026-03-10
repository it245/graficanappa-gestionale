<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContatoreStampante;

class SnmpSnapshotContatori extends Command
{
    protected $signature = 'fiery:snapshot-contatori {--ip= : IP stampante/Fiery (default da config)}';
    protected $description = 'Legge i contatori Canon iPR V900 via SNMP e salva snapshot nel DB';

    /**
     * OID Canon per contatori (enterprise .1.3.6.1.4.1.1602.1.11.1.3.1.4.X)
     */
    private const OID_BASE = '.1.3.6.1.4.1.1602.1.11.1.3.1.4.';
    private const COUNTERS = [
        101 => 'totale_1',
        112 => 'nero_grande',
        113 => 'nero_piccolo',
        122 => 'colore_grande',
        123 => 'colore_piccolo',
        501 => 'scansioni',
        471 => 'foglio_lungo',
    ];

    public function handle()
    {
        if (!function_exists('snmpget')) {
            $this->error('Estensione PHP SNMP non abilitata. Decommentare extension=snmp in php.ini');
            return 1;
        }

        $ip = $this->option('ip') ?: config('fiery.host', '192.168.1.206');
        $community = 'public';

        $this->info("Lettura contatori da {$ip}...");

        $values = [];
        $errors = 0;

        foreach (self::COUNTERS as $oid => $field) {
            $result = @snmpget($ip, $community, self::OID_BASE . $oid);
            if ($result === false) {
                $this->warn("  Errore lettura OID {$oid} ({$field})");
                $values[$field] = 0;
                $errors++;
            } else {
                $num = (int) preg_replace('/^.*:\s*/', '', $result);
                $values[$field] = $num;
                $this->line("  {$field}: " . number_format($num, 0, ',', '.'));
            }
        }

        if ($errors === count(self::COUNTERS)) {
            $this->error('Nessun contatore letto. Stampante non raggiungibile via SNMP.');
            return 1;
        }

        // Salva snapshot
        $snapshot = ContatoreStampante::create(array_merge($values, [
            'stampante' => 'Canon iPR V900',
            'ip' => $ip,
            'rilevato_at' => now(),
        ]));

        $this->info("Snapshot salvato (ID: {$snapshot->id})");

        // Mostra delta rispetto all'ultimo snapshot
        $precedente = ContatoreStampante::where('id', '<', $snapshot->id)
            ->where('stampante', 'Canon iPR V900')
            ->orderByDesc('id')
            ->first();

        if ($precedente) {
            $giorni = $precedente->rilevato_at->diffInDays($snapshot->rilevato_at);
            $this->info("\nDelta rispetto a {$precedente->rilevato_at->format('d/m/Y H:i')} ({$giorni} giorni):");
            foreach (self::COUNTERS as $oid => $field) {
                $delta = $values[$field] - $precedente->{$field};
                $this->line("  {$field}: +" . number_format($delta, 0, ',', '.'));
            }
        }

        return 0;
    }
}
