<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    /**
     * Invia notifica push a tutti gli iscritti con un certo ruolo.
     */
    public static function notificaPerRuolo(string $ruolo, array $payload): void
    {
        $subscriptions = PushSubscription::where('ruolo', $ruolo)->get();
        foreach ($subscriptions as $sub) {
            static::invia($sub, $payload);
        }
    }

    /**
     * Invia notifica push a un singolo operatore.
     */
    public static function notificaOperatore(int $operatoreId, array $payload): void
    {
        $subscriptions = PushSubscription::where('operatore_id', $operatoreId)->get();
        foreach ($subscriptions as $sub) {
            static::invia($sub, $payload);
        }
    }

    /**
     * Invia notifica push a una singola subscription.
     * Usa l'API Web Push con VAPID (senza librerie esterne).
     */
    public static function invia(PushSubscription $sub, array $payload): bool
    {
        $vapidPublic = config('webpush.public_key');
        $vapidPrivate = config('webpush.private_key');
        $vapidSubject = config('webpush.subject', 'mailto:it@graficanappa.com');

        if (!$vapidPublic || !$vapidPrivate) {
            Log::warning('WebPush: chiavi VAPID non configurate');
            return false;
        }

        try {
            $payloadJson = json_encode($payload);

            // Usa la libreria minishlink/web-push se disponibile
            if (class_exists(\Minishlink\WebPush\WebPush::class)) {
                $webPush = new \Minishlink\WebPush\WebPush([
                    'VAPID' => [
                        'subject' => $vapidSubject,
                        'publicKey' => $vapidPublic,
                        'privateKey' => $vapidPrivate,
                    ],
                ]);

                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh_key,
                    'authToken' => $sub->auth_token,
                ]);

                $webPush->sendOneNotification($subscription, $payloadJson);

                foreach ($webPush->flush() as $report) {
                    if (!$report->isSuccess()) {
                        Log::warning('WebPush fallita: ' . $report->getReason(), [
                            'endpoint' => $sub->endpoint,
                        ]);
                        // Rimuovi subscription scaduta (410 Gone)
                        if ($report->isSubscriptionExpired()) {
                            $sub->delete();
                        }
                        return false;
                    }
                }
                return true;
            }

            // Fallback: log per debug (libreria non installata)
            Log::info('WebPush (no lib): ' . $payloadJson, ['endpoint' => substr($sub->endpoint, 0, 50)]);
            return false;

        } catch (\Throwable $e) {
            Log::error('WebPush errore: ' . $e->getMessage());
            return false;
        }
    }
}
