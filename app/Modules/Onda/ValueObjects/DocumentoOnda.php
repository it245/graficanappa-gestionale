<?php

declare(strict_types=1);

namespace App\Modules\Onda\ValueObjects;

use App\Modules\Onda\Enums\TipoDocumentoOnda;
use Carbon\Carbon;

/**
 * Rappresentazione tipizzata di un documento Onda (ATTDocTeste).
 *
 * Wrappa la stdClass grezza che torna l'Adapter, esponendo accessor
 * con il giusto tipo (Carbon per le date, TipoDocumentoOnda per il tipo).
 */
final class DocumentoOnda
{
    public function __construct(
        public readonly int $idDoc,
        public readonly TipoDocumentoOnda $tipo,
        public readonly ?string $codCommessa,
        public readonly ?Carbon $dataDocumento,
        public readonly ?string $ragioneSociale,
        public readonly ?string $descrizione = null,
    ) {}

    /**
     * Costruisce dal payload grezzo Onda (stdClass).
     */
    public static function daRiga(\stdClass $r): self
    {
        return new self(
            idDoc: (int) ($r->IdDoc ?? 0),
            tipo: TipoDocumentoOnda::from((int) ($r->TipoDocumento ?? TipoDocumentoOnda::OrdineProduzione->value)),
            codCommessa: isset($r->CodCommessa) ? (string) $r->CodCommessa : null,
            dataDocumento: !empty($r->DataDocumento) ? Carbon::parse($r->DataDocumento) : null,
            ragioneSociale: isset($r->RagioneSociale) ? (string) $r->RagioneSociale : null,
            descrizione: isset($r->Descrizione) ? (string) $r->Descrizione : null,
        );
    }
}
