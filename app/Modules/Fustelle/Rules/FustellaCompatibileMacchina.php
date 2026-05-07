<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Rules;

use App\Modules\Fustelle\Enums\TipoFustella;

/**
 * Regola di compatibilità tra tipo fustella e macchina (id BOBST/JOH/ZUND/...).
 *
 * Allineata a {@see \App\Modules\Macchine\MacchinaRegistry} (id macchina).
 * Usata dallo scheduler Mossa 37 per assegnare la fustella alla macchina giusta
 * (rilievi seq.39 vs fustelle seq.40 sul BOBST).
 */
final class FustellaCompatibileMacchina
{
    /**
     * @return bool true se il tipo è eseguibile sulla macchina indicata.
     */
    public function eCompatibile(TipoFustella $tipo, string $macchinaId): bool
    {
        $id = strtoupper(trim($macchinaId));
        return in_array($id, $tipo->macchineCompatibili(), true);
    }

    /**
     * @return list<string> elenco macchine compatibili con il tipo.
     */
    public function macchineCompatibili(TipoFustella $tipo): array
    {
        return $tipo->macchineCompatibili();
    }

    /**
     * Motivo testuale dell'incompatibilità (per UI/log).
     */
    public function motivoIncompatibilita(TipoFustella $tipo, string $macchinaId): ?string
    {
        if ($this->eCompatibile($tipo, $macchinaId)) {
            return null;
        }
        $compat = implode(', ', $tipo->macchineCompatibili()) ?: 'nessuna';
        return "Tipo {$tipo->value} non lavorabile su {$macchinaId}. Macchine compatibili: {$compat}";
    }
}
