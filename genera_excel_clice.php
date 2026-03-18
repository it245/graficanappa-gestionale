<?php
/**
 * Genera Excel per inventario e catalogazione cliché stampa a caldo.
 * Uso: php genera_excel_clice.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\EanProdotto;
use App\Helpers\DescrizioneParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

echo "Generando Excel inventario cliché...\n";

// Raccogli dati
$fasiJoh = OrdineFase::where('fase', 'LIKE', 'STAMPACALDOJOH%')->with('ordine')->get();

$fustelleMap = [];
foreach ($fasiJoh as $f) {
    $o = $f->ordine;
    if (!$o) continue;
    $desc = $o->descrizione ?? '';
    $cliente = $o->cliente_nome ?? '';
    $notePre = $o->note_prestampa ?? '';
    $fsCodice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);
    if (!$fsCodice) continue;

    $codici = array_map('trim', explode('/', $fsCodice));
    foreach ($codici as $codice) {
        if (!$codice) continue;
        if (!isset($fustelleMap[$codice])) {
            $fustelleMap[$codice] = ['codice_fs' => $codice, 'clienti' => [], 'varianti' => []];
        }
        $fustelleMap[$codice]['clienti'][$cliente] = $cliente;

        // Estrai variante dalla descrizione (dopo il codice FS)
        $variante = '';
        if (preg_match('/FS\d+[_\s]*(.+?)(?:\s+STAMPA|\s+F\.to|\s+STAMPARE|\s+ALRO|$)/i', $desc, $mv)) {
            $variante = trim($mv[1]);
            // Pulisci
            $variante = preg_replace('/^\d+\s*/', '', $variante);
            $variante = trim($variante, " \t\n\r\0\x0B,.");
        }
        if (!$variante) $variante = $desc;

        $fustelleMap[$codice]['varianti'][] = [
            'commessa' => $o->commessa,
            'cliente' => $cliente,
            'descrizione' => $desc,
            'variante' => $variante,
            'stato' => $f->stato,
        ];
    }
}
ksort($fustelleMap);

// Stili
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D11317']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$subHeaderStyle = [
    'font' => ['bold' => true, 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$cellBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setCreator('MES Grafica Nappa')->setTitle('Inventario Cliché Stampa a Caldo');

// ============================
// FOGLIO 1: ISTRUZIONI
// ============================
$sheetIstr = $spreadsheet->getActiveSheet();
$sheetIstr->setTitle('Istruzioni');

$istruzioni = [
    ['INVENTARIO CLICHÉ STAMPA A CALDO — GRAFICA NAPPA'],
    [''],
    ['COME USARE QUESTO FILE:'],
    [''],
    ['1. FOGLIO "Inventario per Cliente"'],
    ['   - Cliché raggruppati per CLIENTE (Italiana Confetti, Sweet Club, ecc.)'],
    ['   - Per ogni codice FS sono elencate le varianti conosciute dal MES'],
    ['   - COMPILARE: colonna "N. Cliché" con il numero scritto col pennarello (C-001, C-002...)'],
    ['   - COMPILARE: colonna "Posizione" con la posizione sullo scaffale (es. A-01, B-03)'],
    ['   - COMPILARE: colonna "Stato" (OK / Da riparare / Mancante / In macchina)'],
    [''],
    ['2. FOGLIO "Inventario per Codice FS"'],
    ['   - Stessi dati ma ordinati per codice fustella'],
    ['   - Utile per cercare un cliché specifico'],
    [''],
    ['3. FOGLIO "Scaffali Proposti"'],
    ['   - Schema di organizzazione scaffali suggerito'],
    ['   - Ogni scaffale = un cliente o gruppo di clienti'],
    [''],
    ['SUGGERIMENTO ORGANIZZAZIONE:'],
    ['   - Scaffale A → ITALIANA CONFETTI (cliente principale, ~25 fustelle)'],
    ['   - Scaffale B → SWEET CLUB + ITABAKERS'],
    ['   - Scaffale C → TUTTI GLI ALTRI CLIENTI (ordine alfabetico per FS)'],
    ['   - Dentro ogni scaffale: ordinare per codice FS crescente'],
    ['   - Cliché della stessa FS vanno INSIEME nella stessa posizione'],
    [''],
    ['NUMERAZIONE CLICHÉ:'],
    ['   - Scrivere col pennarello indelebile sul cliché: C-001, C-002, C-003...'],
    ['   - Un numero univoco per ogni cliché fisico'],
    ['   - Più cliché con lo stesso FS avranno numeri diversi (es. C-047=FS0898 Strega, C-048=FS0898 Pistacchio)'],
];

$row = 1;
foreach ($istruzioni as $riga) {
    $sheetIstr->setCellValue("A$row", $riga[0] ?? '');
    $row++;
}
$sheetIstr->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('D11317');
$sheetIstr->getStyle('A3')->getFont()->setBold(true)->setSize(12);
$sheetIstr->getStyle('A20')->getFont()->setBold(true)->setSize(12);
$sheetIstr->getStyle('A27')->getFont()->setBold(true)->setSize(12);
$sheetIstr->getColumnDimension('A')->setWidth(100);

// ============================
// FOGLIO 2: PER CLIENTE
// ============================
$sheetCliente = $spreadsheet->createSheet();
$sheetCliente->setTitle('Inventario per Cliente');

$headers = ['Cliente', 'Codice FS', 'Variante / Descrizione', 'Codice EAN', 'Commessa', 'N. Cliché (pennarello)', 'Posizione Scaffale', 'Stato'];

// Carica tutti gli EAN per match con descrizioni Italiana Confetti
$eanProdotti = EanProdotto::all();
function trovaEan($descrizione, $eanProdotti) {
    $descLower = strtolower($descrizione);
    foreach ($eanProdotti as $ean) {
        $artLower = strtolower($ean->articolo);
        // Match: l'articolo EAN è contenuto nella descrizione o viceversa
        if (strlen($artLower) > 5 && (str_contains($descLower, $artLower) || str_contains($artLower, $descLower))) {
            return $ean->codice_ean;
        }
        // Match parziale: prime 20 lettere
        $descShort = substr($descLower, 0, 25);
        $artShort = substr($artLower, 0, 25);
        if (strlen($artShort) > 10 && $descShort === $artShort) {
            return $ean->codice_ean;
        }
    }
    return '';
}
$col = 'A';
foreach ($headers as $h) {
    $sheetCliente->setCellValue($col . '1', $h);
    $col++;
}
$sheetCliente->getStyle('A1:H1')->applyFromArray($headerStyle);
$sheetCliente->setAutoFilter('A1:H1');

// Raggruppa per cliente
$perCliente = [];
foreach ($fustelleMap as $fs) {
    foreach ($fs['varianti'] as $v) {
        $perCliente[$v['cliente']][] = [
            'codice_fs' => $fs['codice_fs'],
            'variante' => $v['variante'],
            'commessa' => $v['commessa'],
            'descrizione' => $v['descrizione'],
        ];
    }
}
ksort($perCliente);

$row = 2;
foreach ($perCliente as $cliente => $varianti) {
    // Deduplica per codice_fs + variante
    $visti = [];
    foreach ($varianti as $v) {
        $key = $v['codice_fs'] . '|' . $v['variante'];
        if (isset($visti[$key])) continue;
        $visti[$key] = true;

        $eanCode = trovaEan($v['variante'], $eanProdotti);
        if (!$eanCode) $eanCode = trovaEan($v['descrizione'], $eanProdotti);

        $sheetCliente->setCellValue("A$row", $cliente);
        $sheetCliente->setCellValue("B$row", $v['codice_fs']);
        $sheetCliente->setCellValue("C$row", substr($v['variante'], 0, 60));
        $sheetCliente->setCellValue("D$row", $eanCode);
        $sheetCliente->setCellValue("E$row", $v['commessa']);
        $sheetCliente->setCellValue("F$row", ''); // Da compilare
        $sheetCliente->setCellValue("G$row", ''); // Da compilare
        $sheetCliente->setCellValue("H$row", ''); // Da compilare

        // Sfondo giallo sulle celle da compilare
        $sheetCliente->getStyle("F$row:H$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFCC']],
        ]);
        $row++;
    }
}
$sheetCliente->getStyle("A2:H$row")->applyFromArray($cellBorder);
foreach (['A' => 30, 'B' => 12, 'C' => 50, 'D' => 16, 'E' => 14, 'F' => 22, 'G' => 18, 'H' => 15] as $c => $w) {
    $sheetCliente->getColumnDimension($c)->setWidth($w);
}

// ============================
// FOGLIO 3: PER CODICE FS
// ============================
$sheetFS = $spreadsheet->createSheet();
$sheetFS->setTitle('Inventario per Codice FS');

$col = 'A';
foreach ($headers as $h) {
    $sheetFS->setCellValue($col . '1', $h);
    $col++;
}
$sheetFS->getStyle('A1:H1')->applyFromArray($headerStyle);
$sheetFS->setAutoFilter('A1:H1');

$row = 2;
foreach ($fustelleMap as $fs) {
    $visti = [];
    foreach ($fs['varianti'] as $v) {
        $key = $v['variante'];
        if (isset($visti[$key])) continue;
        $visti[$key] = true;

        $eanCode = trovaEan($v['variante'], $eanProdotti);
        if (!$eanCode) $eanCode = trovaEan($v['descrizione'], $eanProdotti);

        $sheetFS->setCellValue("A$row", $v['cliente']);
        $sheetFS->setCellValue("B$row", $fs['codice_fs']);
        $sheetFS->setCellValue("C$row", substr($v['variante'], 0, 60));
        $sheetFS->setCellValue("D$row", $eanCode);
        $sheetFS->setCellValue("E$row", $v['commessa']);
        $sheetFS->setCellValue("F$row", '');
        $sheetFS->setCellValue("G$row", '');
        $sheetFS->setCellValue("H$row", '');

        $sheetFS->getStyle("F$row:H$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFCC']],
        ]);
        $row++;
    }
}
$sheetFS->getStyle("A2:H$row")->applyFromArray($cellBorder);
foreach (['A' => 30, 'B' => 12, 'C' => 50, 'D' => 16, 'E' => 14, 'F' => 22, 'G' => 18, 'H' => 15] as $c => $w) {
    $sheetFS->getColumnDimension($c)->setWidth($w);
}

// ============================
// FOGLIO 4: SCAFFALI PROPOSTI
// ============================
$sheetScaff = $spreadsheet->createSheet();
$sheetScaff->setTitle('Scaffali Proposti');

$sheetScaff->setCellValue('A1', 'Scaffale');
$sheetScaff->setCellValue('B1', 'Cliente');
$sheetScaff->setCellValue('C1', 'N. Fustelle');
$sheetScaff->setCellValue('D1', 'N. Cliché Stimati');
$sheetScaff->setCellValue('E1', 'Note');
$sheetScaff->getStyle('A1:E1')->applyFromArray($headerStyle);

// Conta fustelle per cliente
$fsPerCliente = [];
foreach ($fustelleMap as $fs) {
    foreach ($fs['clienti'] as $cl) {
        if (!isset($fsPerCliente[$cl])) $fsPerCliente[$cl] = 0;
        $fsPerCliente[$cl]++;
    }
}
arsort($fsPerCliente);

$scaffali = [
    ['A', 'ITALIANA CONFETTI SRL', 0, 0, 'Cliente principale — astucci 1kg, 500gr, shopper, scatole diamond, vanity'],
    ['B', 'SWEET CLUB SRL', 0, 0, 'Campane, uova, praline pasquali'],
    ['C', 'ITABAKERS SRL', 0, 0, 'Macarons Maxtris, Donuts'],
    ['D', 'COMPROF + PROMOPHARMA + MIA COSMETICS', 0, 0, 'Astucci cosmetici/farmaceutici'],
    ['E', 'ALTRI CLIENTI', 0, 0, 'Liguori, GMF Oliviero, Imbalplast, Starpur, ecc. — ordine per codice FS'],
];

$row = 2;
foreach ($scaffali as $s) {
    $sheetScaff->setCellValue("A$row", $s[0]);
    $sheetScaff->setCellValue("B$row", $s[1]);

    // Conta fustelle per questo scaffale
    $nFs = 0;
    $nCl = 0;
    foreach ($fsPerCliente as $cl => $count) {
        if (stripos($s[1], substr($cl, 0, 10)) !== false || ($s[0] === 'E' && !in_array($cl, ['ITALIANA CONFETTI SRL', 'SWEET CLUB SRL', 'ITABAKERS SRL', 'COMPROF MILANO S.R.L.', 'PROMOPHARMA SPA', 'MIA COSMETICS SRL']))) {
            $nFs += $count;
            $nCl += $count * 4; // stima 4 cliché per FS
        }
    }

    $sheetScaff->setCellValue("C$row", $nFs ?: '~');
    $sheetScaff->setCellValue("D$row", $nCl ?: '~');
    $sheetScaff->setCellValue("E$row", $s[4]);
    $row++;
}

$sheetScaff->getStyle("A2:E$row")->applyFromArray($cellBorder);
foreach (['A' => 10, 'B' => 40, 'C' => 14, 'D' => 18, 'E' => 60] as $c => $w) {
    $sheetScaff->getColumnDimension($c)->setWidth($w);
}

// Salva
$nomeFile = 'Inventario_Clice_Stampa_a_Caldo.xlsx';
$percorso = __DIR__ . '/storage/' . $nomeFile;
$writer = new Xlsx($spreadsheet);
$writer->save($percorso);

echo "Fustelle: " . count($fustelleMap) . "\n";
echo "Varianti totali: " . array_sum(array_map(fn($fs) => count($fs['varianti']), $fustelleMap)) . "\n";
echo "File: $percorso\n";
