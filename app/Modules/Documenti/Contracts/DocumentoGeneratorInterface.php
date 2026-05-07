<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Contracts;

use App\Modules\Documenti\Enums\FormatoDocumento;
use App\Modules\Documenti\Enums\TipoDocumento;

interface DocumentoGeneratorInterface
{
    /**
     * Genera il documento e restituisce il path assoluto del file prodotto.
     *
     * @param array<string, mixed> $context
     */
    public function genera(array $context): string;

    public function tipo(): TipoDocumento;

    public function formato(): FormatoDocumento;
}
