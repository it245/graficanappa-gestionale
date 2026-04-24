<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

error_reporting(E_ERROR | E_PARSE);

use App\Models\ClicheAnagrafica;
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/storage/app/private/cliche_catalogo.xlsx';
if (!file_exists($file)) {
    exit("File non trovato: $file\n");
}

$sheet = IOFactory::load($file)->getActiveSheet();
$rows = $sheet->toArray();
array_shift($rows);

$created = 0;
$updated = 0;
$skipped = 0;

foreach ($rows as $r) {
    $numero = (int) ($r[0] ?? 0);
    if ($numero < 1) { $skipped++; continue; }

    $desc = trim((string) ($r[1] ?? ''));
    $qta = is_numeric($r[2] ?? null) ? (int) $r[2] : null;
    $ulteriori = trim((string) ($r[3] ?? ''));
    $scatola = is_numeric($r[4] ?? null) ? (int) $r[4] : null;

    if ($desc === '') { $skipped++; continue; }

    $note = $ulteriori !== '' ? "Articoli ulteriori: $ulteriori" : null;

    $existing = ClicheAnagrafica::where('numero', $numero)->first();
    if ($existing) {
        $existing->update([
            'descrizione_raw' => $desc,
            'qta' => $qta,
            'scatola' => $scatola,
            'note' => $note,
        ]);
        $updated++;
    } else {
        ClicheAnagrafica::create([
            'numero' => $numero,
            'descrizione_raw' => $desc,
            'qta' => $qta,
            'scatola' => $scatola,
            'note' => $note,
        ]);
        $created++;
    }
}

echo "Import completato:\n";
echo "  Creati: $created\n";
echo "  Aggiornati: $updated\n";
echo "  Saltati: $skipped\n";
echo "  Totale in DB: " . ClicheAnagrafica::count() . "\n";
