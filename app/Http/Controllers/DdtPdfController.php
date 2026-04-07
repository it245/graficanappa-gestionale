<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DdtPdfController extends Controller
{
    /**
     * Genera PDF del DDT vendita da Onda.
     * GET /ddt/pdf/{numeroDdt}
     */
    public function genera($numeroDdt)
    {
        // 1. Recupera testata DDT da Onda
        $testa = DB::connection('onda')->selectOne("
            SELECT t.IdDoc, t.NumeroDocumento, t.DataDocumento, t.DataRegistrazione,
                   a.RagioneSociale AS ClienteNome,
                   a.Indirizzo AS ClienteIndirizzo,
                   a.CAP AS ClienteCap, a.Citta AS ClienteCitta,
                   a.Provincia AS ClienteProvincia, a.Nazione AS ClienteNazione,
                   a.Telefono AS ClienteTelefono,
                   a.PartitaIVA AS ClientePIVA, a.CodiceFiscale AS ClienteCF
            FROM ATTDocTeste t
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            WHERE t.TipoDocumento = 3
              AND t.NumeroDocumento = ?
        ", [$numeroDdt]);

        if (!$testa) {
            abort(404, "DDT n. {$numeroDdt} non trovato in Onda");
        }

        // 2. Recupera dati trasporto (coda)
        $coda = DB::connection('onda')->selectOne("
            SELECT c.NumeroColli, c.AspettoEsteriore, c.Peso,
                   c.DataInizioTrasporto, c.OraInizioTrasporto,
                   c.TrasportoACura, c.Annotazioni,
                   v.RagioneSociale AS VettoreNome,
                   v.Indirizzo AS VettoreIndirizzo, v.Citta AS VettoreCitta,
                   -- Destinazione diversa
                   dest.RagioneSociale AS DestNome,
                   dest.Indirizzo AS DestIndirizzo,
                   dest.CAP AS DestCap, dest.Citta AS DestCitta,
                   dest.Provincia AS DestProvincia,
                   dest.Telefono AS DestTelefono
            FROM ATTDocCoda c
            LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
            LEFT JOIN STDAnagrafiche dest ON c.IdDestinatario = dest.IdAnagrafica
            WHERE c.IdDoc = ?
        ", [$testa->IdDoc]);

        // 3. Recupera righe articoli (TipoRiga 1=articolo, 3=testo)
        $righe = DB::connection('onda')->select("
            SELECT r.TipoRiga, r.CodCommessa, r.Descrizione, r.Qta,
                   r.CodUnMis, r.CodArt
            FROM ATTDocRighe r
            WHERE r.IdDoc = ?
            ORDER BY r.NumRiga
        ", [$testa->IdDoc]);

        // 4. Carica mappatura RIF. ORD. MAXTRIS da Excel
        $rifMap = $this->caricaRifOrdMaxtris();

        // 5. Raggruppa righe per commessa con intestazione Rif.Ord.Cli.
        $righeRaggruppate = $this->raggruppaRighe($righe, $rifMap);

        // 6. Formatta dati
        $dataDdt = $testa->DataDocumento
            ? \Carbon\Carbon::parse($testa->DataDocumento)->format('d/m/Y')
            : '';
        $numeroPadded = str_pad($numeroDdt, 7, '0', STR_PAD_LEFT);

        $oraTrasporto = '';
        if ($coda && $coda->OraInizioTrasporto) {
            $oraTrasporto = substr($coda->OraInizioTrasporto, 0, 5);
        }
        $dataTrasporto = '';
        if ($coda && $coda->DataInizioTrasporto) {
            $dataTrasporto = \Carbon\Carbon::parse($coda->DataInizioTrasporto)->format('d/m/Y');
        }

        // Trasporto a cura: Cedente/Cessionario/Vettore
        $trasportoCura = 'Cedente'; // default
        if ($coda && $coda->TrasportoACura) {
            $map = [1 => 'Cedente', 2 => 'Cessionario', 3 => 'Vettore'];
            $trasportoCura = $map[$coda->TrasportoACura] ?? 'Cedente';
        }

        // Annotazioni: cerca "n. X bancali da rendere" nelle righe testo
        $annotazioni = '';
        if ($coda && $coda->Annotazioni) {
            $annotazioni = $coda->Annotazioni;
        }
        // Cerca anche righe di testo (TipoRiga=3) per note aggiuntive
        $noteRighe = collect($righe)
            ->filter(fn($r) => $r->TipoRiga == 3 && !empty(trim($r->Descrizione ?? '')))
            ->pluck('Descrizione')
            ->implode("\n");

        $data = [
            'numeroDdt'       => $numeroPadded,
            'dataDdt'         => $dataDdt,
            'testa'           => $testa,
            'coda'            => $coda,
            'righeRaggruppate' => $righeRaggruppate,
            'dataTrasporto'   => $dataTrasporto ?: $dataDdt,
            'oraTrasporto'    => $oraTrasporto,
            'trasportoCura'   => $trasportoCura,
            'annotazioni'     => $annotazioni,
            'noteRighe'       => $noteRighe,
        ];

        $pdf = Pdf::loadView('ddt.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("DDT_{$numeroPadded}.pdf");
    }

    /**
     * Carica la mappatura commessa → RIF. ORD. MAXTRIS dall'Excel ORDINE ASTUCCI.xlsx
     */
    private function caricaRifOrdMaxtris(): array
    {
        $excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
        $file = rtrim($excelPath, '/\\') . DIRECTORY_SEPARATOR . 'ORDINE ASTUCCI.xlsx';

        if (!file_exists($file)) {
            return [];
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $map = []; // commessa => rif_ord_maxtris

        foreach ($sheet->getRowIterator(2) as $row) { // skip header
            $cells = $row->getCellIterator('A', 'G');
            $cells->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cells as $cell) {
                $rowData[] = $cell->getValue();
            }

            $commessa = trim($rowData[0] ?? ''); // colonna A = commessa
            $rif = trim($rowData[6] ?? '');       // colonna G = RIF. ORD. MAXTRIS

            if ($commessa && $rif) {
                $map[$commessa] = $rif;
            }
        }

        return $map;
    }

    /**
     * Raggruppa righe DDT per commessa, inserendo intestazioni Rif.Ord.Cli.
     */
    private function raggruppaRighe(array $righe, array $rifMap): array
    {
        $gruppi = [];
        $currentCommessa = null;

        foreach ($righe as $riga) {
            // Salta righe di testo (saranno nelle note)
            if ($riga->TipoRiga != 1) {
                continue;
            }

            $commessa = trim($riga->CodCommessa ?? '');

            // Nuova commessa: crea intestazione
            if ($commessa && $commessa !== $currentCommessa) {
                $currentCommessa = $commessa;
                $rifOrd = $rifMap[$commessa] ?? null;

                $gruppi[] = [
                    'tipo'      => 'intestazione',
                    'commessa'  => $commessa,
                    'rif_ord'   => $rifOrd,
                ];
            }

            $gruppi[] = [
                'tipo'        => 'articolo',
                'descrizione' => $riga->Descrizione ?? '',
                'qta'         => $riga->Qta ?? 0,
                'um'          => $riga->CodUnMis ?? 'PZ',
            ];
        }

        return $gruppi;
    }
}
