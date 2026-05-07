<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Enums;

use App\Constants\RepartoId;

/**
 * Backed enum dei reparti di Grafica Nappa.
 *
 * Il `value` è lo slug presente nella tabella `reparti.nome` (vedi
 * RepartiSeeder). L'enum integra con `App\Constants\RepartoId` via il
 * metodo `id()`: nessuna duplicazione di costanti, RepartoId resta
 * la sorgente di verità degli ID interi.
 *
 * Aggiungere un nuovo caso QUI implica:
 *  1. aggiungere la costante in {@see RepartoId}
 *  2. aggiornare RepartiSeeder
 *  3. aggiornare il match di id()/tipo()/oreSettimana() qui sotto
 */
enum CodiceReparto: string
{
    case SPEDIZIONE      = 'spedizione';
    case PRODUZIONE      = 'produzione';
    case MAGAZZINO       = 'magazzino';
    case DIGITALE        = 'digitale';
    case FUSTELLA        = 'fustella';
    case LEGATORIA       = 'legatoria';
    case PIEGAINCOLLA    = 'piegaincolla';
    case PLASTIFICAZIONE = 'plastificazione';
    case PRESTAMPA       = 'prestampa';
    case STAMPA_A_CALDO  = 'stampa a caldo';
    case STAMPA_OFFSET   = 'stampa offset';
    case ESTERNO         = 'esterno';

    /**
     * ID intero corrispondente nella tabella `reparti`.
     * Letto da {@see RepartoId} per evitare doppia sorgente di verità.
     */
    public function id(): int
    {
        return match ($this) {
            self::SPEDIZIONE      => RepartoId::SPEDIZIONE,
            self::PRODUZIONE      => RepartoId::PRODUZIONE,
            self::MAGAZZINO       => RepartoId::MAGAZZINO,
            self::DIGITALE        => RepartoId::DIGITALE,
            self::FUSTELLA        => RepartoId::FUSTELLA,
            self::LEGATORIA       => RepartoId::LEGATORIA,
            self::PIEGAINCOLLA    => RepartoId::PIEGAINCOLLA,
            self::PLASTIFICAZIONE => RepartoId::PLASTIFICAZIONE,
            self::PRESTAMPA       => RepartoId::PRESTAMPA,
            self::STAMPA_A_CALDO  => RepartoId::STAMPA_A_CALDO,
            self::STAMPA_OFFSET   => RepartoId::STAMPA_OFFSET,
            self::ESTERNO         => RepartoId::ESTERNO,
        };
    }

    /**
     * Nome leggibile per UI (rispetta convenzione DB: lowercase con spazi).
     */
    public function nome(): string
    {
        return $this->value;
    }

    /**
     * Macro-categoria del reparto.
     */
    public function tipo(): TipoReparto
    {
        return match ($this) {
            self::PRESTAMPA       => TipoReparto::PRESTAMPA,
            self::STAMPA_OFFSET,
            self::STAMPA_A_CALDO,
            self::DIGITALE        => TipoReparto::STAMPA,
            self::PLASTIFICAZIONE,
            self::FUSTELLA,
            self::PIEGAINCOLLA,
            self::LEGATORIA       => TipoReparto::FINITURA,
            self::MAGAZZINO,
            self::PRODUZIONE      => TipoReparto::LOGISTICA,
            self::SPEDIZIONE      => TipoReparto::SPEDIZIONE,
            self::ESTERNO         => TipoReparto::ESTERNO,
        };
    }

    /**
     * Ore settimanali nominali del reparto (somma turni × 5/6 giorni).
     *
     *  - Stampa offset (XL106): 24h × 5gg = 120
     *  - Stampa a caldo (JOH): 16h × 5gg = 80
     *  - Reparti diurni 6-22:  16h × 5gg = 80
     *  - Esterno/Logistica:    8h × 5gg = 40 (nominali)
     *
     * NB: valore "di targa", non tiene conto di assenze, festivi o terzo
     * turno straordinario. Per capacità reale usa {@see CapacitaService}.
     */
    public function oreSettimana(): int
    {
        return match ($this) {
            self::STAMPA_OFFSET                  => 120,
            self::STAMPA_A_CALDO, self::DIGITALE => 80,
            self::PLASTIFICAZIONE,
            self::FUSTELLA,
            self::PIEGAINCOLLA,
            self::LEGATORIA,
            self::PRESTAMPA,
            self::SPEDIZIONE                     => 80,
            self::PRODUZIONE,
            self::MAGAZZINO,
            self::ESTERNO                        => 40,
        };
    }

    /**
     * Lookup sicuro da ID intero (RepartoId) → enum.
     * Restituisce null se l'ID non corrisponde a nessun caso noto.
     */
    public static function fromId(int $id): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->id() === $id) {
                return $case;
            }
        }
        return null;
    }
}
