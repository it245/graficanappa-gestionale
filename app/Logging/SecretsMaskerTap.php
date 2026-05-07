<?php

namespace App\Logging;

use Illuminate\Log\Logger;

/**
 * Tap che applica SecretsMaskerProcessor a un channel di logging.
 * Uso in config/logging.php: 'tap' => [App\Logging\SecretsMaskerTap::class]
 */
class SecretsMaskerTap
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(new SecretsMaskerProcessor());
        }
    }
}
