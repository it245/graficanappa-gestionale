<?php

declare(strict_types=1);

namespace App\Modules\Macchine;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\Rules\RegoleBOBST;
use App\Modules\Macchine\Rules\RegoleJOH;
use App\Modules\Macchine\Rules\RegolePiegaincolla;
use App\Modules\Macchine\Rules\RegoleStandard;
use App\Modules\Macchine\Rules\RegoleXL106;
use InvalidArgumentException;

/**
 * Registry statico delle macchine fisiche di Grafica Nappa.
 *
 * Sorgente di verita per ora "in-memory" (no DB): cambia qui per
 * aggiungere/rimuovere macchine. In futuro questo registry potra
 * leggere da una tabella `macchine` senza modifiche al chiamante.
 */
final class MacchinaRegistry
{
    /**
     * Cache delle istanze gia costruite (chiave = id macchina).
     *
     * @var array<string, MacchinaInterface>|null
     */
    private static ?array $cache = null;

    /**
     * Costruisce (e cache-a) tutte le regole macchina.
     *
     * @return array<string, MacchinaInterface>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $regole = [
            new RegoleXL106(),
            new RegoleJOH(),
            new RegoleBOBST(),
            new RegolePiegaincolla(),

            // Macchine standard 6-22 lun-ven
            new RegoleStandard(
                'STEL',
                'STEL - Fustella Cilindrica',
                2000,
                ['reparto' => 'fustella cilindrica'],
            ),
            new RegoleStandard(
                'PLAST',
                'Plastificazione',
                3000,
                ['reparto' => 'plastica', 'sequenza' => 20],
            ),
            new RegoleStandard(
                'FIN',
                'Finestratura',
                2500,
                ['reparto' => 'finestratura', 'sequenza' => 100],
            ),
            new RegoleStandard(
                'INDIGO',
                'HP Indigo - Stampa Digitale',
                1800,
                ['reparto' => 'digitale', 'sequenza' => 11],
            ),
            new RegoleStandard(
                'TAGLIO',
                'Tagliacarte',
                3500,
                ['reparto' => 'taglio', 'sequenza' => 37],
            ),
            new RegoleStandard(
                'LEGAT',
                'Legatoria',
                2000,
                ['reparto' => 'legatoria', 'sequenza' => 120],
            ),
            new RegoleStandard(
                'ZUND',
                'Zund - Finitura Digitale (taglio)',
                1500,
                ['reparto' => 'finitura digitale', 'sequenza' => 35],
            ),
            new RegoleStandard(
                'MGI',
                'MGI - Foil Digitale (UVSpot)',
                1200,
                ['reparto' => 'esterno/foil digitale'],
            ),
        ];

        $map = [];
        foreach ($regole as $r) {
            $map[$r->getId()] = $r;
        }

        self::$cache = $map;

        return self::$cache;
    }

    /**
     * Ritorna le regole per la macchina richiesta.
     *
     * @throws InvalidArgumentException Se l'id non e registrato.
     */
    public static function find(string $id): MacchinaInterface
    {
        $tutte = self::all();
        $key = strtoupper(trim($id));

        if (! isset($tutte[$key])) {
            throw new InvalidArgumentException(
                "Macchina '{$id}' non registrata in MacchinaRegistry."
            );
        }

        return $tutte[$key];
    }

    /**
     * Verifica se una macchina e registrata.
     */
    public static function exists(string $id): bool
    {
        return isset(self::all()[strtoupper(trim($id))]);
    }

    /**
     * Resetta la cache (utile nei test).
     */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
