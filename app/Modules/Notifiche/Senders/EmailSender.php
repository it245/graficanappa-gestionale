<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Senders;

use App\Models\Operatore;
use App\Modules\Notifiche\Contracts\NotificaSenderInterface;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\ValueObjects\Notifica;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sender Email: usa Mail::raw() di Laravel (SMTP Gmail gia' configurato in .env).
 *
 * Destinatario accettato:
 *  - string    -> indirizzo email
 *  - Operatore -> $operatore->email (se presente)
 *
 * Per template HTML strutturati, in futuro: usare Mailable dedicati.
 *
 * Esempio:
 *   $sender->send(new Notifica('Report contatori', 'Vedi allegato',
 *       CanaleNotifica::Email, PrioritaNotifica::Normale,
 *       destinatario: 'capo@graficanappa.com'));
 */
final class EmailSender implements NotificaSenderInterface
{
    public function canale(): CanaleNotifica
    {
        return CanaleNotifica::Email;
    }

    public function isAvailable(): bool
    {
        // Mailer e' considerato disponibile se la config e' presente.
        return (string) config('mail.default', '') !== '';
    }

    public function send(Notifica $n): bool
    {
        $to = $this->resolveEmail($n->destinatario);

        if ($to === null) {
            Log::warning('EmailSender: email destinatario non risolvibile', [
                'destinatario_type' => get_debug_type($n->destinatario),
                'titolo' => $n->titolo,
            ]);

            return false;
        }

        try {
            Mail::raw($n->messaggio, function ($mail) use ($to, $n): void {
                $mail->to($to)->subject($this->subject($n));
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('EmailSender: invio fallito', [
                'to' => $to,
                'titolo' => $n->titolo,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function resolveEmail(mixed $destinatario): ?string
    {
        if (is_string($destinatario) && filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return $destinatario;
        }

        if ($destinatario instanceof Operatore) {
            $email = $destinatario->email ?? null;

            return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }

        return null;
    }

    private function subject(Notifica $n): string
    {
        $prefix = match ($n->priorita->value) {
            'critica' => '[MES CRITICA] ',
            'alta' => '[MES ALTA] ',
            default => '[MES] ',
        };

        return $prefix.$n->titolo;
    }
}
