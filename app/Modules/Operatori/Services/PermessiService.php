<?php

declare(strict_types=1);

namespace App\Modules\Operatori\Services;

use App\Models\Operatore;
use App\Models\OrdineFase;
use App\Modules\Operatori\Enums\RuoloOperatore;
use App\Modules\Operatori\Permessi\Permesso;
use App\Modules\Operatori\Permessi\PermessiMatrix;
use App\Modules\Operatori\Rules\AssegnazioneFaseRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * Punto di ingresso unico per i controlli di autorizzazione del modulo Operatori.
 *
 * Non tocca la sessione, non parla con il DB direttamente:
 * combina PermessiMatrix (livello ruolo) con regole contestuali (es. reparto).
 */
final class PermessiService
{
    /**
     * Verifica permesso. Se passato un $context (tipicamente OrdineFase),
     * applica anche le regole contestuali.
     */
    public function check(Operatore $op, Permesso $permesso, ?Model $context = null): bool
    {
        $ruolo = RuoloOperatore::fromStringOrDefault($op->ruolo ?? null);

        if (! PermessiMatrix::has($ruolo, $permesso)) {
            return false;
        }

        // Controlli contestuali: per ora coprono solo le fasi.
        if ($context instanceof OrdineFase) {
            return $this->checkSuFase($op, $ruolo, $permesso, $context);
        }

        return true;
    }

    /**
     * Variante che lancia AuthorizationException se il check fallisce.
     *
     * @throws AuthorizationException
     */
    public function throwUnlessCan(Operatore $op, Permesso $permesso, ?Model $context = null): void
    {
        if (! $this->check($op, $permesso, $context)) {
            throw new AuthorizationException(
                sprintf('Operatore %s non autorizzato: %s', (string) $op->getKey(), $permesso->value)
            );
        }
    }

    private function checkSuFase(
        Operatore $op,
        RuoloOperatore $ruolo,
        Permesso $permesso,
        OrdineFase $fase,
    ): bool {
        // Admin e Owner: ok ovunque (Owner ha MODIFICA_FASE_REPARTO_ALTRO).
        if ($ruolo === RuoloOperatore::Admin || $ruolo === RuoloOperatore::Owner) {
            return true;
        }

        // Per AVVIA/TERMINA fase l'operatore deve poter essere assegnato a quella fase.
        if ($permesso === Permesso::AVVIA_FASE || $permesso === Permesso::TERMINA_FASE) {
            return AssegnazioneFaseRule::canAssegnareFase($op, $fase);
        }

        // Modifica priorita / fase di altro reparto: serve permesso esplicito sul ruolo,
        // gia' verificato a monte da PermessiMatrix.
        return true;
    }
}
