<?php

namespace App\Constants;

/**
 * ID dei reparti (tabella `reparti`).
 *
 * Sorgente di verità: database/seeders/RepartiSeeder.php
 * Mantenere allineato con seeder e DB di produzione.
 */
final class RepartoId
{
    public const SPEDIZIONE = 1;
    public const PRODUZIONE = 2;
    public const MAGAZZINO = 3;
    public const DIGITALE = 4;
    public const FUSTELLA = 5;
    public const LEGATORIA = 6;
    public const PIEGAINCOLLA = 7;
    public const PLASTIFICAZIONE = 8;
    public const PRESTAMPA = 9;
    public const STAMPA_A_CALDO = 10;
    public const STAMPA_OFFSET = 11;
    public const ESTERNO = 12;

    private function __construct() {}
}
