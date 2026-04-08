<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DdtPdfService
{
    /** Cache della mappatura Excel (evita di ricaricare il file per ogni DDT) */
    private static ?array $rifMapCache = null;

    /**
     * Genera il PDF per un DDT e lo salva su disco.
     * Ritorna il path del file generato, o null se non trovato.
     */
    public static function generaESalva(string $numeroDdt): ?string
    {
        $numeroPadded = str_pad($numeroDdt, 7, '0', STR_PAD_LEFT);

        // Controlla se già generato
        $outputDir = self::getOutputDir();
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . "DDT_{$numeroPadded}.pdf";
        if (file_exists($outputPath)) {
            return $outputPath;
        }

        $data = self::preparaDati($numeroPadded);
        if (!$data) return null;

        $pdf = Pdf::loadView('ddt.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        // Crea directory se non esiste
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputPath, $pdf->output());
        Log::info("DDT PDF generato: {$outputPath}");

        return $outputPath;
    }

    /**
     * Genera il PDF per visualizzazione nel browser (stream).
     */
    public static function stream(string $numeroDdt)
    {
        $numeroPadded = str_pad($numeroDdt, 7, '0', STR_PAD_LEFT);

        $data = self::preparaDati($numeroPadded);
        if (!$data) {
            abort(404, "DDT n. {$numeroDdt} non trovato in Onda");
        }

        $pdf = Pdf::loadView('ddt.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("DDT_{$numeroPadded}.pdf");
    }

    /**
     * Directory di output per i PDF DDT.
     */
    private static function getOutputDir(): string
    {
        $excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
        return rtrim($excelPath, '/\\') . DIRECTORY_SEPARATOR . 'ddt';
    }

    /**
     * Prepara tutti i dati per il template Blade.
     */
    private static function preparaDati(string $numeroPadded): ?array
    {
        // 1. Testata DDT
        $testa = DB::connection('onda')->selectOne("
            SELECT t.IdDoc, t.NumeroDocumento, t.DataDocumento, t.DataRegistrazione,
                   a.RagioneSociale AS ClienteNome,
                   a.Indirizzo AS ClienteIndirizzo,
                   a.Cap AS ClienteCap, a.Citta AS ClienteCitta,
                   a.Provincia AS ClienteProvincia, a.CodNazione AS ClienteNazione,
                   a.Telefono AS ClienteTelefono,
                   a.PartitaIva AS ClientePIVA, a.CodiceFiscale AS ClienteCF
            FROM ATTDocTeste t
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            WHERE t.TipoDocumento = 3
              AND t.NumeroDocumento = ?
              AND YEAR(t.DataDocumento) = YEAR(GETDATE())
        ", [$numeroPadded]);

        // Fallback: se non trovato nell'anno corrente, cerca il più recente
        if (!$testa) {
            $testa = DB::connection('onda')->selectOne("
                SELECT t.IdDoc, t.NumeroDocumento, t.DataDocumento, t.DataRegistrazione,
                       a.RagioneSociale AS ClienteNome,
                       a.Indirizzo AS ClienteIndirizzo,
                       a.Cap AS ClienteCap, a.Citta AS ClienteCitta,
                       a.Provincia AS ClienteProvincia, a.CodNazione AS ClienteNazione,
                       a.Telefono AS ClienteTelefono,
                       a.PartitaIva AS ClientePIVA, a.CodiceFiscale AS ClienteCF
                FROM ATTDocTeste t
                LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
                WHERE t.TipoDocumento = 3
                  AND t.NumeroDocumento = ?
                ORDER BY t.DataDocumento DESC
            ", [$numeroPadded]);
        }

        if (!$testa) return null;

        // 2. Coda (trasporto, destinazione, vettore)
        $coda = DB::connection('onda')->selectOne("
            SELECT c.TotNumColli AS NumeroColli, c.Aspetto AS AspettoEsteriore,
                   c.TotPesoLordo AS Peso,
                   c.DataTrasporto, c.OraTrasporto,
                   c.CodTrasporto AS TrasportoACura,
                   c.CausaleTrasporto,
                   c.RagSocSped AS DestNome,
                   c.IndirizzoSped AS DestIndirizzo,
                   c.CapSped AS DestCap, c.CittaSped AS DestCitta,
                   c.ProvSped AS DestProvincia,
                   v.RagioneSociale AS VettoreNome,
                   v.Indirizzo AS VettoreIndirizzo, v.Citta AS VettoreCitta
            FROM ATTDocCoda c
            LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
            WHERE c.IdDoc = ?
        ", [$testa->IdDoc]);

        // 3. Righe articoli
        $righe = DB::connection('onda')->select("
            SELECT r.TipoRiga, r.CodCommessa, r.Descrizione, r.Qta,
                   r.CodUnMis, r.CodArt, r.NonStampa
            FROM ATTDocRighe r
            WHERE r.IdDoc = ?
              AND (r.NonStampa IS NULL OR r.NonStampa = 0)
            ORDER BY r.NrRiga
        ", [$testa->IdDoc]);

        // 4. Mappatura Excel RIF. ORD. MAXTRIS (cached)
        if (self::$rifMapCache === null) {
            self::$rifMapCache = self::caricaRifOrdMaxtris();
        }
        $rifMap = self::$rifMapCache;

        // 5. Prepara righe con RIF
        $righeFinali = self::preparaRighe($righe, $rifMap);

        // 6. Formattazione
        $dataDdt = $testa->DataDocumento
            ? \Carbon\Carbon::parse($testa->DataDocumento)->format('d/m/Y')
            : '';

        $oraTrasporto = '';
        if ($coda && $coda->OraTrasporto) {
            $oraTrasporto = substr($coda->OraTrasporto, 0, 5);
        }
        $dataTrasporto = '';
        if ($coda && $coda->DataTrasporto) {
            $dataTrasporto = \Carbon\Carbon::parse($coda->DataTrasporto)->format('d/m/Y');
        }

        $trasportoCura = 'Cedente';
        if ($coda && $coda->TrasportoACura) {
            $map = [1 => 'Cedente', 2 => 'Cessionario', 3 => 'Vettore'];
            $trasportoCura = $map[$coda->TrasportoACura] ?? 'Cedente';
        }

        $causale = 'Vendita';
        if ($coda && $coda->CausaleTrasporto) {
            $causaliMap = [1 => 'Vendita', 2 => 'Conto lavorazione', 3 => 'Conto visione', 4 => 'Reso'];
            $causale = $causaliMap[$coda->CausaleTrasporto] ?? $coda->CausaleTrasporto;
        }

        // Note: righe testo escluse intestazioni Rif. Ord.Cli. e disclaimer
        $noteRighe = collect($righe)
            ->filter(fn($r) => $r->TipoRiga == 3
                && !empty(trim($r->Descrizione ?? ''))
                && !str_starts_with(trim($r->Descrizione), 'Rif. Ord.Cli.')
                && !str_contains(strtolower($r->Descrizione ?? ''), 'contestazioni'))
            ->pluck('Descrizione')
            ->implode("\n");

        $nazione = 'Italia';
        if ($testa->ClienteNazione && !in_array($testa->ClienteNazione, ['IT', 'it', ''])) {
            $nazione = $testa->ClienteNazione;
        }

        return [
            'numeroDdt'       => $numeroPadded,
            'dataDdt'         => $dataDdt,
            'testa'           => $testa,
            'coda'            => $coda,
            'righeFinali'     => $righeFinali,
            'dataTrasporto'   => $dataTrasporto ?: $dataDdt,
            'oraTrasporto'    => $oraTrasporto,
            'trasportoCura'   => $trasportoCura,
            'causale'         => $causale,
            'nazione'         => $nazione,
            'noteRighe'       => $noteRighe,
        ];
    }

    private static function caricaRifOrdMaxtris(): array
    {
        $excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
        $file = rtrim($excelPath, '/\\') . DIRECTORY_SEPARATOR . 'ORDINE ASTUCCI.xlsx';

        if (!file_exists($file)) {
            return ['dettaglio' => [], 'commessa' => []];
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $map = [];
        $mapCommessa = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $r = $row->getRowIndex();
            $commessa = trim($sheet->getCell("F$r")->getValue() ?? '');
            $descrizione = trim($sheet->getCell("B$r")->getValue() ?? '');
            $rif = trim($sheet->getCell("G$r")->getValue() ?? '');

            if (!$rif) continue;

            $commesse = array_map('trim', explode('-', $commessa));
            $tutteNumeriche = collect($commesse)->every(fn($c) => is_numeric($c));

            if ($tutteNumeriche && count($commesse) > 1) {
                foreach ($commesse as $c) {
                    $key = $c . '|' . self::normalizza($descrizione);
                    $map[$key] = $rif;
                    if (!isset($mapCommessa[$c])) $mapCommessa[$c] = $rif;
                }
            } else {
                $key = $commessa . '|' . self::normalizza($descrizione);
                $map[$key] = $rif;
                if (!isset($mapCommessa[$commessa])) $mapCommessa[$commessa] = $rif;
            }
        }

        return ['dettaglio' => $map, 'commessa' => $mapCommessa];
    }

    private static function normalizza(string $desc): string
    {
        $desc = mb_strtoupper($desc);
        return preg_replace('/[^A-Z0-9]/', '', $desc);
    }

    private static function commessaCorta(string $codCommessa): string
    {
        $parts = explode('-', $codCommessa);
        return ltrim($parts[0], '0') ?: '0';
    }

    private static function preparaRighe(array $righe, array $rifMap): array
    {
        $risultato = [];
        $dettaglio = $rifMap['dettaglio'] ?? [];
        $perCommessa = $rifMap['commessa'] ?? [];

        foreach ($righe as $riga) {
            if ($riga->TipoRiga != 1) continue;

            $codCommessa = trim($riga->CodCommessa ?? '');
            $commCorta = self::commessaCorta($codCommessa);
            $descNorm = self::normalizza($riga->Descrizione ?? '');

            $rifOrd = $dettaglio[$commCorta . '|' . $descNorm] ?? '';
            if (!$rifOrd) {
                $rifOrd = $perCommessa[$commCorta] ?? '';
            }

            $risultato[] = [
                'descrizione' => $riga->Descrizione ?? '',
                'rif_ord'     => $rifOrd,
                'qta'         => $riga->Qta ?? 0,
                'um'          => $riga->CodUnMis ?? 'PZ',
            ];
        }

        return $risultato;
    }
}
