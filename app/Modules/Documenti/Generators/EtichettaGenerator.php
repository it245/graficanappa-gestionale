<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Generators;

use App\Modules\Documenti\Contracts\DocumentoGeneratorInterface;
use App\Modules\Documenti\Enums\FormatoDocumento;
use App\Modules\Documenti\Enums\TipoDocumento;
use App\Modules\Documenti\Rules\GtinRule;

final class EtichettaGenerator implements DocumentoGeneratorInterface
{
    public function __construct(
        private readonly GtinRule $gtinRule = new GtinRule(),
    ) {
    }

    /**
     * Context atteso: ['fase_id' => int, 'qta' => int, 'gtin' => ?string, 'cod_art' => ?string].
     * Se 'gtin' assente lo genera tramite GtinRule da 'cod_art' + 'qta'.
     * Restituisce path PNG generato.
     */
    public function genera(array $context): string
    {
        $faseId = (int) ($context['fase_id'] ?? 0);
        $qta = (int) ($context['qta'] ?? 0);

        if ($faseId <= 0) {
            throw new \InvalidArgumentException('fase_id obbligatorio');
        }

        $gtin = $context['gtin'] ?? null;

        if ($gtin === null) {
            $codArt = (string) ($context['cod_art'] ?? '');

            if ($codArt === '') {
                throw new \InvalidArgumentException('gtin oppure cod_art obbligatori');
            }

            $gtin = $this->gtinRule->genera($codArt, $qta);
        }

        if (!$this->gtinRule->valida($gtin)) {
            throw new \InvalidArgumentException("GTIN non valido: {$gtin}");
        }

        $dir = storage_path('app/documenti/etichette');

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossibile creare directory: {$dir}");
        }

        $path = sprintf('%s/etichetta_%d_%s.png', $dir, $faseId, $gtin);

        $this->disegnaLabelPng($path, $gtin, $qta, $faseId);

        return $path;
    }

    public function tipo(): TipoDocumento
    {
        return TipoDocumento::Etichetta;
    }

    public function formato(): FormatoDocumento
    {
        return FormatoDocumento::Png;
    }

    /**
     * Disegna PNG label: rettangolo bianco + datamatrix placeholder + testo GTIN/qta.
     * In produzione il datamatrix vero e' delegato a libreria esterna (es. tc-lib-barcode);
     * qui generiamo una griglia di moduli deterministica dal GTIN per coerenza visiva.
     */
    private function disegnaLabelPng(string $path, string $gtin, int $qta, int $faseId): void
    {
        $w = 400;
        $h = 200;
        $img = imagecreatetruecolor($w, $h);

        if ($img === false) {
            throw new \RuntimeException('imagecreatetruecolor fallito');
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        // Datamatrix placeholder 16x16 moduli derivato da hash GTIN.
        $size = 16;
        $modulo = 8;
        $offX = 20;
        $offY = 20;
        $bits = str_pad(decbin(crc32($gtin)), 32, '0', STR_PAD_LEFT);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $bit = $bits[($y * $size + $x) % strlen($bits)];

                if ($bit === '1') {
                    imagefilledrectangle(
                        $img,
                        $offX + $x * $modulo,
                        $offY + $y * $modulo,
                        $offX + ($x + 1) * $modulo,
                        $offY + ($y + 1) * $modulo,
                        $black,
                    );
                }
            }
        }

        $textX = $offX + $size * $modulo + 20;
        imagestring($img, 5, $textX, 20, "GTIN: {$gtin}", $black);
        imagestring($img, 5, $textX, 50, "QTA: {$qta}", $black);
        imagestring($img, 3, $textX, 90, "Fase: {$faseId}", $black);

        if (!imagepng($img, $path)) {
            imagedestroy($img);
            throw new \RuntimeException("Salvataggio PNG fallito: {$path}");
        }

        imagedestroy($img);
    }
}
