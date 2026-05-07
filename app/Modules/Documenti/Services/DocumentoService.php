<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Services;

use App\Modules\Documenti\Contracts\DocumentoGeneratorInterface;
use App\Modules\Documenti\Enums\TipoDocumento;
use App\Modules\Documenti\Generators\EtichettaGenerator;
use App\Modules\Documenti\ValueObjects\MetadatiDocumento;
use Illuminate\Support\Facades\Log;

final class DocumentoService
{
    /**
     * Mappa tipo -> generator. Ad oggi solo Etichetta ha implementazione "modulare";
     * le altre restano delegate ai service legacy (non modificati).
     *
     * @var array<string, class-string<DocumentoGeneratorInterface>>
     */
    private array $generators = [
        TipoDocumento::Etichetta->value => EtichettaGenerator::class,
    ];

    public function __construct(
        private readonly ?\Closure $resolver = null,
    ) {
    }

    public function genera(TipoDocumento $tipo, array $context): string
    {
        $generator = $this->resolveGenerator($tipo);
        $path = $generator->genera($context);

        $this->logGenerazione(MetadatiDocumento::daFile(
            path: $path,
            tipo: $generator->tipo(),
            formato: $generator->formato(),
            generatoDa: $context['generato_da'] ?? null,
        ));

        return $path;
    }

    /**
     * Cancella file in storage/app/documenti piu vecchi di N giorni.
     * Restituisce il numero di file eliminati.
     */
    public function cleanup(int $oltreGiorni = 30): int
    {
        $root = storage_path('app/documenti');

        if (!is_dir($root)) {
            return 0;
        }

        $cutoff = time() - ($oltreGiorni * 86_400);
        $count = 0;

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iter as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                if (@unlink($file->getPathname())) {
                    $count++;
                }
            }
        }

        Log::info('Documenti cleanup', ['eliminati' => $count, 'giorni' => $oltreGiorni]);

        return $count;
    }

    public function logGenerazione(MetadatiDocumento $meta): void
    {
        Log::info('Documento generato', $meta->toArray());
    }

    private function resolveGenerator(TipoDocumento $tipo): DocumentoGeneratorInterface
    {
        if (!isset($this->generators[$tipo->value])) {
            throw new \RuntimeException("Generator non registrato per tipo: {$tipo->value}");
        }

        $class = $this->generators[$tipo->value];

        if ($this->resolver !== null) {
            $instance = ($this->resolver)($class);
        } else {
            $instance = function_exists('app') ? app($class) : new $class();
        }

        if (!$instance instanceof DocumentoGeneratorInterface) {
            throw new \RuntimeException("Generator {$class} non implementa DocumentoGeneratorInterface");
        }

        return $instance;
    }
}
