<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Enums;

enum TipoDocumento: string
{
    case BollaLavorazione = 'bolla_lavorazione';
    case SchedaProduzione = 'scheda_produzione';
    case Etichetta = 'etichetta';
    case ReportOre = 'report_ore';
    case PianoProduzione = 'piano_produzione';
    case Fustella = 'fustella';

    public function label(): string
    {
        return match ($this) {
            self::BollaLavorazione => 'Bolla di Lavorazione',
            self::SchedaProduzione => 'Scheda di Produzione',
            self::Etichetta => 'Etichetta Operatore',
            self::ReportOre => 'Report Ore',
            self::PianoProduzione => 'Piano di Produzione',
            self::Fustella => 'Fustella Archivio',
        };
    }

    public function formatoDefault(): FormatoDocumento
    {
        return match ($this) {
            self::Etichetta => FormatoDocumento::Png,
            self::PianoProduzione => FormatoDocumento::Xlsx,
            default => FormatoDocumento::Pdf,
        };
    }
}
