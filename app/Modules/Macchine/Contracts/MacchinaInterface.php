<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Contracts;

use App\Modules\Macchine\Models\MacchinaConfig;

/**
 * Contratto comune a tutte le regole macchina di Grafica Nappa.
 *
 * Ogni macchina fisica (XL106, JOH, BOBST, ecc.) implementa questa
 * interfaccia in una propria classe Regole* dentro App\Modules\Macchine\Rules.
 */
interface MacchinaInterface
{
    /**
     * Identificativo univoco della macchina (es. "XL106", "JOH").
     */
    public function getId(): string;

    /**
     * Nome leggibile della macchina (es. "Heidelberg XL106 - Stampa Offset").
     */
    public function getNome(): string;

    /**
     * Restituisce la finestra oraria operativa come stringa human-readable
     * (es. "24h lun-ven", "6-22 lun-ven + sab 6-13").
     */
    public function getTurno(): string;

    /**
     * Capacità nominale in fogli/ora (a regime, escluso avviamento).
     */
    public function getCapacitaOraria(): int;

    /**
     * Indica se la macchina richiede tempo di cambio configurazione
     * tra un job e l'altro (BOBST rilievi/fustelle, Piegaincolla PI01/02/03).
     */
    public function richiedeCambioConfig(): bool;

    /**
     * Ore necessarie per il cambio configurazione.
     * Restituisce 0.0 quando richiedeCambioConfig() === false.
     */
    public function oreCambioConfig(): float;

    /**
     * Esporta la configurazione come DTO immutabile, utilizzabile dai
     * service di calcolo (CalcoloOreService, scheduler, ecc.).
     */
    public function toConfig(): MacchinaConfig;
}
