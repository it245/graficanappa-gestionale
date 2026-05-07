<?php

declare(strict_types=1);

namespace App\Modules\Audit\Adapters;

use App\Modules\Audit\Contracts\AuditSinkInterface;
use App\Modules\Audit\ValueObjects\AuditEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sink alternativo: append in formato JSON-Lines su file.
 *
 * Casi d'uso:
 *  - DR fallback se MySQL non risponde
 *  - Debug locale: tail -f storage/logs/audit.jsonl
 *  - Esportazione long-term archiving (file rotabili facilmente)
 *
 * Path default: storage/logs/audit.jsonl. Append-only con LOCK_EX per
 * sicurezza concorrenza (write rapidi: il flush avviene a fine scrittura).
 */
final class FileAuditSink implements AuditSinkInterface
{
    public function __construct(
        private readonly ?string $path = null,
    ) {}

    public function scrivi(AuditEvent $evento): void
    {
        try {
            $path = $this->path ?? storage_path('logs/audit.jsonl');
            $dir  = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $row = $evento->toRow();
            // Decodifica JSON dei campi JSON encoded per non avere stringhe escapate dentro stringhe
            foreach (['old_values', 'new_values'] as $k) {
                if (isset($row[$k]) && is_string($row[$k])) {
                    $decoded = json_decode($row[$k], true);
                    if ($decoded !== null) {
                        $row[$k] = $decoded;
                    }
                }
            }

            $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            Log::warning('FileAuditSink::scrivi failed', ['errore' => $e->getMessage()]);
        }
    }
}
