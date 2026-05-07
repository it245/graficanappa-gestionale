<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Maschera API key, token, password nei log.
 * Applicato come Monolog processor su tutti i channel via tap LogChannelTap.
 */
class SecretsMaskerProcessor implements ProcessorInterface
{
    private const PATTERNS = [
        // Anthropic API key (sk-ant-api03-...)
        '/sk-ant-[a-zA-Z0-9_\-]{20,}/' => 'sk-ant-***',
        // Telegram bot token (8330569441:AAEnyu...)
        '/\b\d{8,12}:[A-Za-z0-9_\-]{30,}/' => '***telegram-token***',
        // Generic Bearer token
        '/Bearer\s+[A-Za-z0-9_\-\.]{20,}/' => 'Bearer ***',
        // Google API key (AIzaSy...)
        '/AIzaSy[A-Za-z0-9_\-]{30,}/' => 'AIza***',
        // Generic password fields
        '/("password"\s*:\s*")[^"]+(")/' => '$1***$2',
        '/(password=)[^&\s]+/' => '$1***',
        // Fiery API key (long base64-like)
        '/(FIERY_API_KEY["\s:=]+)[A-Za-z0-9+\/=]{50,}/' => '$1***',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $message = $this->maskString($record->message);
        $context = $this->maskArray($record->context);
        $extra = $this->maskArray($record->extra);

        return $record->with(
            message: $message,
            context: $context,
            extra: $extra,
        );
    }

    private function maskString(string $value): string
    {
        foreach (self::PATTERNS as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value);
        }
        return $value;
    }

    private function maskArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->maskString($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->maskArray($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
