<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Services;

use App\Models\OrdineFase;

/**
 * Calcolo progresso produttivo di una commessa basato sulle fasi.
 *
 * "Completata" = stato fase >= 3 (terminata o consegnata).
 *
 * @example
 *   $svc = app(ProgressoService::class);
 *   $svc->calcolaProgresso('0067164-26');
 *   // ['totale' => 12, 'completate' => 7, 'percentuale' => 58.33]
 *   $svc->faseCorrente('0067164-26'); // 'STAMPAOFFSET'
 */
final class ProgressoService
{
    /**
     * @return array{totale: int, completate: int, percentuale: float}
     */
    public function calcolaProgresso(string $codCommessa): array
    {
        $fasi = OrdineFase::query()
            ->whereHas('ordine', fn ($q) => $q->where('commessa', $codCommessa))
            ->get(['id', 'stato']);

        $totale = $fasi->count();
        if ($totale === 0) {
            return ['totale' => 0, 'completate' => 0, 'percentuale' => 0.0];
        }

        $completate = $fasi->filter(fn ($f) => $this->statoIntero($f->stato) >= 3)->count();
        $percentuale = round(($completate / $totale) * 100, 2);

        return [
            'totale' => $totale,
            'completate' => $completate,
            'percentuale' => (float) $percentuale,
        ];
    }

    /**
     * Nome della fase più "avanzata" attualmente in corso (stato 2 = avviato).
     * Se non ci sono fasi avviate ritorna null.
     *
     * "Più avanzata" = maggior priorità nel ciclo (priorita più bassa = prima)
     * — rispecchia la convenzione del config/fasi_priorita.php.
     */
    public function faseCorrente(string $codCommessa): ?string
    {
        $fase = OrdineFase::query()
            ->whereHas('ordine', fn ($q) => $q->where('commessa', $codCommessa))
            ->where('stato', 2)
            ->orderByDesc('priorita') // priorità alta = step più avanzato del ciclo
            ->first(['fase']);

        return $fase?->fase;
    }

    private function statoIntero(mixed $raw): int
    {
        if (is_int($raw)) {
            return $raw;
        }
        if (is_numeric($raw)) {
            return (int) $raw;
        }
        // Stringa non numerica (es. 'pausa') = ancora in corso.
        return 2;
    }
}
