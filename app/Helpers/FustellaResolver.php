<?php

namespace App\Helpers;

class FustellaResolver
{
    /**
     * Cerca PDF fustella in public/fustelle/ basandosi sul codice (es. FS0291).
     * Ritorna ['url', 'filename', 'codice', 'descrizione', 'dimensioni'] oppure null.
     */
    public static function resolve(?string $codice): ?array
    {
        if (!$codice) return null;
        $codice = strtoupper(trim($codice));
        if (!preg_match('/^(FS|KS)\d{3,5}$/', $codice)) return null;

        $dir = public_path('fustelle');
        if (!is_dir($dir)) return null;

        // Match prefisso (es. "FS0291 - AST.pdf") o ovunque ("ABBINAMENTO FS0291 + FS0292.pdf")
        $prefix = glob($dir . DIRECTORY_SEPARATOR . $codice . '*.pdf');
        $contained = glob($dir . DIRECTORY_SEPARATOR . '*' . $codice . '*.pdf');
        $files = !empty($prefix) ? $prefix : $contained;
        if (empty($files)) return null;

        $file = $files[0];
        $name = basename($file);

        return [
            'url'         => asset('fustelle/' . rawurlencode($name)),
            'filename'    => $name,
            'codice'      => $codice,
            'descrizione' => self::parseDescrizione($name, $codice),
            'dimensioni'  => self::parseDimensioni($name),
        ];
    }

    private static function parseDescrizione(string $filename, string $codice): string
    {
        $name = preg_replace('/\.pdf$/i', '', $filename);
        $name = preg_replace('/^' . preg_quote($codice, '/') . '\s*[-_]?\s*/i', '', $name);
        return trim($name);
    }

    private static function parseDimensioni(string $filename): ?string
    {
        // Pattern "120 x 100 x H120" / "33x23" / "85 x 85 x 85"
        if (preg_match('/(\d+)\s*x\s*(\d+)(?:\s*x\s*h?\s*(\d+))?/i', $filename, $m)) {
            return $m[3] ?? null
                ? $m[1] . '×' . $m[2] . '×' . $m[3] . ' mm'
                : $m[1] . '×' . $m[2] . ' mm';
        }
        return null;
    }
}
