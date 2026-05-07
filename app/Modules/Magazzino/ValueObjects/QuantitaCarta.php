<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\ValueObjects;

use App\Modules\Carta\Rules\ConversioneFogliKgRule;
use App\Modules\Carta\ValueObjects\Formato;
use InvalidArgumentException;

/**
 * Value Object immutabile che rappresenta una quantità di carta
 * con la sua unità di misura.
 *
 * Permette conversione kg <-> fogli noto formato e grammatura,
 * e somma sicura tra quantità della stessa UM.
 *
 * @example
 *  $q = new QuantitaCarta(120.0, 'kg');
 *  $fogli = $q->convertiInFogli(grammaturaGm2: 300.0, areaFoglioM2: 0.50);
 *  // -> QuantitaCarta(800.0, 'fg')
 *
 * @example somma
 *  $tot = (new QuantitaCarta(500, 'fg'))->somma(new QuantitaCarta(250, 'fg'));
 *  // -> QuantitaCarta(750.0, 'fg')
 */
final readonly class QuantitaCarta
{
    public const UM_FOGLI       = 'fg';
    public const UM_KILOGRAMMI  = 'kg';
    public const UM_PEZZI       = 'pz';

    private const UNITA_AMMESSE = [self::UM_FOGLI, self::UM_KILOGRAMMI, self::UM_PEZZI];

    public function __construct(
        public float $valore,
        public string $unitaMisura,
    ) {
        if (! in_array($unitaMisura, self::UNITA_AMMESSE, true)) {
            throw new InvalidArgumentException(
                "Unità di misura non valida: {$unitaMisura}. Ammesse: " . implode(',', self::UNITA_AMMESSE)
            );
        }
        if ($valore < 0 && $unitaMisura !== self::UM_KILOGRAMMI) {
            // tolleriamo negativi solo per rettifiche di peso
            throw new InvalidArgumentException("Valore negativo non ammesso per UM {$unitaMisura}");
        }
    }

    /**
     * Converte la quantità in fogli, dato peso unitario.
     *
     * Strangler Fig: la formula peso/foglio è single source of truth
     * in {@see ConversioneFogliKgRule}. Qui si costruisce il Formato
     * a partire dall'area indicata (assunto: foglio quadrato equivalente).
     *
     * @param float $grammatura     g/m² (es. 300)
     * @param float $areaFoglioM2   area in m² del singolo foglio (es. 70x100 cm = 0.70)
     * @throws InvalidArgumentException se chiamato su UM diverse da kg/fg
     */
    public function convertiInFogli(float $grammatura, float $areaFoglioM2 = 1.0): self
    {
        if ($this->unitaMisura === self::UM_FOGLI) {
            return $this; // già in fogli
        }
        if ($this->unitaMisura !== self::UM_KILOGRAMMI) {
            throw new InvalidArgumentException(
                "Conversione in fogli supportata solo da kg, ricevuto: {$this->unitaMisura}"
            );
        }
        if ($grammatura <= 0.0 || $areaFoglioM2 <= 0.0) {
            throw new InvalidArgumentException('Grammatura e area foglio devono essere > 0');
        }

        // Costruisci un Formato quadrato equivalente all'area indicata,
        // così la formula resta delegata interamente a ConversioneFogliKgRule.
        $latoMm = (int) round(sqrt($areaFoglioM2) * 1000.0);
        $formato = new Formato($latoMm, $latoMm);

        $fogli = (new ConversioneFogliKgRule())
            ->fogliDaKg((int) round($grammatura), $formato, $this->valore);

        return new self((float) $fogli, self::UM_FOGLI);
    }

    /**
     * Somma due quantità con UM coerente.
     *
     * @throws InvalidArgumentException se UM diverse
     */
    public function somma(self $altra): self
    {
        if ($this->unitaMisura !== $altra->unitaMisura) {
            throw new InvalidArgumentException(
                "Impossibile sommare UM diverse: {$this->unitaMisura} vs {$altra->unitaMisura}"
            );
        }
        return new self($this->valore + $altra->valore, $this->unitaMisura);
    }

    public function isZero(): bool
    {
        return abs($this->valore) < 1e-9;
    }

    public function __toString(): string
    {
        return rtrim(rtrim(number_format($this->valore, 3, '.', ''), '0'), '.') . ' ' . $this->unitaMisura;
    }
}
