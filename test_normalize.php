<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function normalizza(string $desc): string
{
    $desc = mb_strtoupper($desc);
    $desc = preg_replace('/\([^)]*\)/', '', $desc);
    $stopwords = [
        'ASTUCCIO', 'ASTUCCI', 'AST.', 'AST',
        'VASSOIO', 'VASSOI', 'VASS.', 'VASS',
        'BOX', 'PACK', 'SCATOLA', 'CONFEZIONE',
        'FORMATO', 'SLEEVE', 'KIT', 'SET',
        'CARTONATO', 'COFANETTO',
        'MAXTRIS', 'DA',
        'IL', 'LA', 'GLI', 'LE', 'DI', 'DEL', 'DELLA',
    ];
    $desc = preg_replace('/\bCADEAUX?\b/', 'CADEAU', $desc);
    foreach ($stopwords as $w) {
        $desc = preg_replace('/\b' . preg_quote($w, '/') . '\b/u', '', $desc);
    }
    return preg_replace('/[^A-Z0-9]/', '', $desc);
}

$onda = 'AST.1 KG DOLCE SPOSA (20*250)';
$excel = 'ASTUCCI DA 1KG. MAXTRIS DOLCE SPOSA';

echo "Onda  : '$onda'\n";
echo "Norm  : '" . normalizza($onda) . "'\n\n";
echo "Excel : '$excel'\n";
echo "Norm  : '" . normalizza($excel) . "'\n\n";

echo "Match: " . (normalizza($onda) === normalizza($excel) ? "SI ✅" : "NO ❌") . "\n";

// Test stessa via DdtPdfService::caricaRifOrdMaxtris
echo "\n=== Test live DdtPdfService cache RIF ===\n";
$reflection = new ReflectionClass(\App\Services\DdtPdfService::class);
$method = $reflection->getMethod('caricaRifOrdMaxtris');
$method->setAccessible(true);
$rifMap = $method->invoke(null);
$detail = $rifMap['dettaglio'];
echo "Total detail entries: " . count($detail) . "\n";

$key = '67201|' . normalizza($onda);
echo "Lookup key: '$key'\n";
echo "Match: " . ($detail[$key] ?? 'MISS') . "\n";
