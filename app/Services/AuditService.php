<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditService
{
    /**
     * Registra un evento nell'audit log.
     *
     * @param string $action  login|logout|update|create|delete|sync|export
     * @param string|null $model  Nome del model (es. 'OrdineFase')
     * @param int|null $modelId  ID del record
     * @param array|null $oldValues  Valori prima della modifica
     * @param array|null $newValues  Valori dopo la modifica
     * @param string|null $extra  Info aggiuntive libere
     */
    public static function log(
        string $action,
        ?string $model = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $extra = null
    ): void {
        try {
            // Recupera utente dalla sessione (operatore o admin)
            $userId = session('operatore_id');
            $userName = session('operatore_nome');

            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'user_name' => $userName,
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
                'extra' => $extra,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Graceful degradation: se il log fallisce, non bloccare l'app
            \Log::warning('AuditService::log failed: ' . $e->getMessage());
        }
    }

    /**
     * Shortcut per log di login
     */
    public static function login(int $userId, string $userName): void
    {
        // Setta manualmente perché la sessione potrebbe non essere ancora pronta
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'user_name' => $userName,
                'action' => 'login',
                'ip' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('AuditService::login failed: ' . $e->getMessage());
        }
    }

    /**
     * Shortcut per log di logout
     */
    public static function logout(): void
    {
        self::log('logout');
    }

    /**
     * Log cambio stato fase
     */
    public static function statoFase(int $faseId, $oldStato, $newStato, ?string $extra = null): void
    {
        self::log('update', 'OrdineFase', $faseId, ['stato' => $oldStato], ['stato' => $newStato], $extra);
    }
}
