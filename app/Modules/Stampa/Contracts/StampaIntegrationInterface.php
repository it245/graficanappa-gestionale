<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Contracts;

/**
 * Contratto comune per ogni integrazione esterna di stampa
 * (Prinect per offset XL106, Fiery per Canon V900/Indigo, futuri plotter, ecc.).
 *
 * Wrappa i Service applicativi (PrinectService, FieryService) dietro una
 * interfaccia uniforme così che il dominio Stampa non dipenda da SDK
 * specifici e si possano aggiungere nuove macchine/integrazioni senza
 * toccare il codice consumer.
 */
interface StampaIntegrationInterface
{
    /**
     * Identificativo univoco dell'integrazione (es. "prinect", "fiery").
     */
    public function getId(): string;

    /**
     * Restituisce il job attualmente in stampa sulla macchina, o null
     * se la macchina è ferma / nessun job attivo.
     *
     * Forma del payload: array associativo con almeno
     *  - jobId: string|int
     *  - nome: string
     *  - copieFatte: int
     *  - copieRichieste: int
     *  - inizio: string|null (ISO8601)
     *
     * @return array<string,mixed>|null
     */
    public function getJobInStampa(): ?array;

    /**
     * Restituisce i job completati in una finestra temporale.
     *
     * @param  \DateTimeInterface|null  $da   Estremo inferiore (incluso). Default: 24h fa.
     * @param  \DateTimeInterface|null  $a    Estremo superiore (incluso). Default: ora.
     * @return array<int,array<string,mixed>>
     */
    public function getJobsCompletati(?\DateTimeInterface $da = null, ?\DateTimeInterface $a = null): array;

    /**
     * Verifica se l'integrazione è raggiungibile (ping API, server status).
     */
    public function isOnline(): bool;
}
