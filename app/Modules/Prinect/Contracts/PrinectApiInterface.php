<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Contracts;

/**
 * Contratto astratto per la REST API Heidelberg Prinect Pressroom Manager.
 *
 * Volutamente "thin": espone gli endpoint REST come metodi PHP, senza
 * trasformazioni o business logic. La traduzione HTTP -> array vive
 * nell'Adapter (PrinectHttpAdapter); la business logic vive nei Service
 * (PrinectJobsService, PrinectInkService, ecc.).
 *
 * Questo permette di:
 *  - mockare l'API in test (CI senza Heidelberg fisica raggiungibile);
 *  - cambiare il transport (Http facade, Guzzle, fake) senza toccare i Service;
 *  - centralizzare la documentazione delle risorse REST in un solo file.
 *
 * Tutti i metodi restituiscono array decoded dal JSON, oppure null in caso
 * di errore HTTP / API non disponibile (mai eccezioni propagate al chiamante,
 * coerente con il comportamento legacy di PrinectService).
 */
interface PrinectApiInterface
{
    /**
     * Indica se il client è configurato (env presente).
     */
    public function isConfigured(): bool;

    // ===== Device =====

    /**
     * Lista tutti i device (macchine) registrate nel Pressroom Manager.
     * @return array<string,mixed>|null
     */
    public function getDevices(): ?array;

    /**
     * Activity timeline di un device in una finestra temporale.
     *
     * @param  string       $deviceId  ID device Prinect (es. "4001" per XL106)
     * @param  string|null  $start     ISO8601 con timezone, es. "2026-05-07T00:00:00+02:00"
     * @param  string|null  $end       ISO8601 con timezone
     * @return array<string,mixed>|null
     */
    public function getDeviceActivity(string $deviceId, ?string $start = null, ?string $end = null): ?array;

    /**
     * Consumi (carta, energia) per un device.
     * @return array<string,mixed>|null
     */
    public function getDeviceConsumption(string $deviceId, ?string $start = null, ?string $end = null): ?array;

    // ===== Jobs =====

    /**
     * Lista jobs filtrabili per modifica/stato globale.
     * @return array<string,mixed>|null
     */
    public function getJobs(?string $modifiedSince = null, ?string $globalStatus = null): ?array;

    /**
     * Singolo job per ID Prinect (numerico, senza zero-padding).
     * @return array<string,mixed>|null
     */
    public function getJob(string $jobId): ?array;

    /**
     * Worksteps di un job. Ogni job ha 1..N worksteps (es. ConventionalPrinting,
     * Plate, Cutting, ecc.).
     * @return array<string,mixed>|null
     */
    public function getWorksteps(string $jobId): ?array;

    /**
     * Activities (passaggi macchina) di un singolo workstep — prova certa
     * di stampa avvenuta. Endpoint più affidabile di amountProduced quando
     * actualStartDate è null (vedi bug 66811).
     * @return array<string,mixed>|null
     */
    public function getWorkstepActivities(string $jobId, string $workstepId): ?array;

    /**
     * Consumo inchiostri (CMYK + spot) per workstep.
     * @return array<string,mixed>|null
     */
    public function getWorkstepInk(string $jobId, string $workstepId): ?array;

    /**
     * Misurazioni qualità (densità, ΔE, ecc.) per workstep.
     * @return array<string,mixed>|null
     */
    public function getWorkstepQuality(string $jobId, string $workstepId): ?array;

    /**
     * Preview JDF/PDF del workstep.
     * @return array<string,mixed>|null
     */
    public function getWorkstepPreview(string $jobId, string $workstepId): ?array;

    /**
     * Master data: lista dei milestone configurati nel Pressroom Manager.
     * NB: l'API Heidelberg espone i milestone a livello globale, non per
     * workstep. Il parametro $workstepId è accettato per estensioni future
     * ma attualmente ignorato.
     * @return array<string,mixed>|null
     */
    public function getMilestones(?string $workstepId = null): ?array;
}
