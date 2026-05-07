<?php

namespace App\Constants;

/**
 * Stati delle fasi di produzione (`ordine_fasi.stato`).
 *
 * Valori numerici memorizzati nel DB. La colonna può anche contenere
 * stringhe non-numeriche (es. "EXT" o motivi di pausa testuali) — questi
 * casi sono backward-compat e NON sono rappresentati qui.
 *
 * @see config/fasi_priorita.php
 * @see App\Services\FaseStatoService
 */
final class StatoFase
{
    /** Caricata, predecessori non ancora completati. */
    public const NON_INIZIATA = 0;

    /** Predecessori terminati: la fase è pronta per essere avviata. */
    public const PRONTA = 1;

    /** Fase in lavorazione interna. */
    public const AVVIATA = 2;

    /** Fase completata. */
    public const TERMINATA = 3;

    /** Commessa consegnata (solo per fasi reparto spedizione). */
    public const CONSEGNATA = 4;

    /** Fase inviata a fornitore esterno (NON conta come terminata interna). */
    public const ESTERNO = 5;

    /**
     * Stati che indicano che la fase è stata "completata" in qualche modo
     * (terminata internamente, consegnata o inviata all'esterno).
     */
    public const COMPLETATI = [
        self::TERMINATA,
        self::CONSEGNATA,
        self::ESTERNO,
    ];

    private function __construct() {}
}
