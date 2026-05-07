<?php

declare(strict_types=1);

namespace App\Modules\Documenti\ValueObjects;

use App\Modules\Documenti\Enums\FormatoDocumento;
use App\Modules\Documenti\Enums\TipoDocumento;
use DateTimeImmutable;

final readonly class MetadatiDocumento
{
    public function __construct(
        public TipoDocumento $tipo,
        public FormatoDocumento $formato,
        public int $dimensioneByte,
        public string $mimeType,
        public string $hashSha256,
        public DateTimeImmutable $dataGenerazione,
        public ?int $generatoDa = null,
    ) {
    }

    public static function daFile(
        string $path,
        TipoDocumento $tipo,
        FormatoDocumento $formato,
        ?int $generatoDa = null,
    ): self {
        if (!is_file($path)) {
            throw new \RuntimeException("File non trovato: {$path}");
        }

        return new self(
            tipo: $tipo,
            formato: $formato,
            dimensioneByte: (int) filesize($path),
            mimeType: $formato->mimeType(),
            hashSha256: hash_file('sha256', $path) ?: '',
            dataGenerazione: new DateTimeImmutable(),
            generatoDa: $generatoDa,
        );
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo->value,
            'formato' => $this->formato->value,
            'dimensione_byte' => $this->dimensioneByte,
            'mime_type' => $this->mimeType,
            'hash_sha256' => $this->hashSha256,
            'data_generazione' => $this->dataGenerazione->format(DATE_ATOM),
            'generato_da' => $this->generatoDa,
        ];
    }
}
