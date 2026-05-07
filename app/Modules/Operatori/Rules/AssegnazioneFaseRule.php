<?php

declare(strict_types=1);

namespace App\Modules\Operatori\Rules;

use App\Models\Operatore;
use App\Models\OrdineFase;
use App\Modules\Operatori\Enums\RuoloOperatore;

/**
 * Regole business per assegnare un OrdineFase a un Operatore.
 *
 * Non scrive sul DB: solo controlli puri.
 * Integrazione con il modello Operatore esistente avviene tramite
 * la relazione belongsToMany `reparti()` (pivot operatore_reparto)
 * e l'attributo legacy `reparto` (CSV) gestito da `getRepartiArrayAttribute`.
 */
final class AssegnazioneFaseRule
{
    /**
     * Verifica che l'operatore possa essere assegnato a una fase.
     */
    public static function canAssegnareFase(Operatore $op, OrdineFase $fase): bool
    {
        if (! self::operatoreAttivo($op)) {
            return false;
        }

        // Admin/Owner bypassano il vincolo di reparto.
        $ruolo = RuoloOperatore::fromStringOrDefault($op->ruolo ?? null);
        if ($ruolo === RuoloOperatore::Admin || $ruolo === RuoloOperatore::Owner) {
            return ! self::faseAttivaSuAltroOperatore($op, $fase);
        }

        if (! self::operatoreAppartieneAlReparto($op, $fase)) {
            return false;
        }

        if (self::faseAttivaSuAltroOperatore($op, $fase)) {
            return false;
        }

        // Fase gia' terminata/consegnata (stati 3 e 4) non riassegnabile.
        $stato = (int) ($fase->stato ?? 0);
        if ($stato >= 3) {
            return false;
        }

        return true;
    }

    private static function operatoreAttivo(Operatore $op): bool
    {
        // `attivo` non e' fillable: leggiamo l'attributo grezzo se presente.
        $attivo = $op->getAttribute('attivo');

        // Tolleriamo schemi senza la colonna: in quel caso si considera attivo.
        if ($attivo === null) {
            return true;
        }

        return (bool) $attivo;
    }

    private static function operatoreAppartieneAlReparto(Operatore $op, OrdineFase $fase): bool
    {
        $repartoFase = self::normalizza($fase->reparto ?? null);
        if ($repartoFase === '') {
            return false;
        }

        // 1) pivot operatore_reparto
        try {
            $reparti = $op->reparti()->pluck('reparti.nome')->all();
            foreach ($reparti as $nome) {
                if (self::normalizza((string) $nome) === $repartoFase) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // se la relazione non e' disponibile in test, fallback sotto.
        }

        // 2) accessor legacy CSV su `reparto`
        $csv = $op->reparti_array ?? [];
        if (is_array($csv)) {
            foreach ($csv as $nome) {
                if (self::normalizza((string) $nome) === $repartoFase) {
                    return true;
                }
            }
        }

        // 3) reparto_id singolo via relazione belongsTo
        if (isset($op->reparto) && is_object($op->reparto) && isset($op->reparto->nome)) {
            if (self::normalizza((string) $op->reparto->nome) === $repartoFase) {
                return true;
            }
        }

        return false;
    }

    private static function faseAttivaSuAltroOperatore(Operatore $op, OrdineFase $fase): bool
    {
        $opIdFase = $fase->operatore_id ?? null;
        if ($opIdFase === null) {
            return false;
        }

        if ((int) $opIdFase === (int) $op->getKey()) {
            return false;
        }

        // stato 2 = avviato (vedi feedback_stati_fasi).
        return (int) ($fase->stato ?? 0) === 2;
    }

    private static function normalizza(?string $v): string
    {
        return strtolower(trim((string) $v));
    }
}
