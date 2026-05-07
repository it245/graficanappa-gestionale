<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Enums;

enum FormatoDocumento: string
{
    case Pdf = 'pdf';
    case Xlsx = 'xlsx';
    case Csv = 'csv';
    case Png = 'png';
    case Json = 'json';

    public function mimeType(): string
    {
        return match ($this) {
            self::Pdf => 'application/pdf',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv',
            self::Png => 'image/png',
            self::Json => 'application/json',
        };
    }

    public function estensione(): string
    {
        return '.' . $this->value;
    }
}
