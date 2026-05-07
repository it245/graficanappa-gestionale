<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Enums;

/**
 * Stati possibili di un DDT di spedizione.
 *
 * Mappa una vista MES-friendly sopra agli eventi grezzi BRT/Onda.
 */
enum StatoSpedizione: string
{
    case Aperto = 'aperto';
    case InTransito = 'in_transito';
    case Consegnato = 'consegnato';
    case RitiratoMagazzino = 'ritirato_magazzino';
    case InRitardo = 'in_ritardo';
    case Eccezione = 'eccezione';
    case AnomaliaDestinatario = 'anomalia_destinatario';

    public function label(): string
    {
        return match ($this) {
            self::Aperto => 'Aperto',
            self::InTransito => 'In transito',
            self::Consegnato => 'Consegnato',
            self::RitiratoMagazzino => 'Ritirato in magazzino',
            self::InRitardo => 'In ritardo',
            self::Eccezione => 'Eccezione',
            self::AnomaliaDestinatario => 'Anomalia destinatario',
        };
    }

    public function eFinale(): bool
    {
        return match ($this) {
            self::Consegnato, self::RitiratoMagazzino => true,
            default => false,
        };
    }

    public function eAnomalia(): bool
    {
        return match ($this) {
            self::Eccezione, self::AnomaliaDestinatario, self::InRitardo => true,
            default => false,
        };
    }

    /**
     * Mappa una stringa libera (es. descrizione evento BRT) sullo stato corretto.
     */
    public static function daDescrizioneBrt(?string $descrizione): self
    {
        if ($descrizione === null || $descrizione === '') {
            return self::Aperto;
        }

        $d = mb_strtoupper($descrizione);

        return match (true) {
            str_contains($d, 'CONSEGNATA') => self::Consegnato,
            str_contains($d, 'RITIRATA') && str_contains($d, 'MAGAZZINO') => self::RitiratoMagazzino,
            str_contains($d, 'ANOMALIA') => self::AnomaliaDestinatario,
            str_contains($d, 'GIACENZA') || str_contains($d, 'ECCEZIONE') => self::Eccezione,
            str_contains($d, 'TRANSITO') || str_contains($d, 'PARTITA') || str_contains($d, 'IN CONSEGNA') => self::InTransito,
            default => self::Aperto,
        };
    }
}
