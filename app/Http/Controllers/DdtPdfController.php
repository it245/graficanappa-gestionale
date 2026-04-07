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
        // Zero-pad a 7 cifre (860 → 0000860)
        $numeroPadded = str_pad($numeroDdt, 7, '0', STR_PAD_LEFT);

        // 1. Recupera testata DDT da Onda (anno corrente per evitare duplicati)
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

        if (!$testa) {
            abort(404, "DDT n. {$numeroDdt} non trovato in Onda");
        }

        // 2. Recupera dati trasporto (coda)
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

        // 3. Recupera righe articoli (TipoRiga 1=articolo, 3=testo)
        $righe = DB::connection('onda')->select("
            SELECT r.TipoRiga, r.CodCommessa, r.Descrizione, r.Qta,
                   r.CodUnMis, r.CodArt
            FROM ATTDocRighe r
            WHERE r.IdDoc = ?
            ORDER BY r.NrRiga
        ", [$testa->IdDoc]);

        // 4. Carica mappatura RIF. ORD. MAXTRIS da Excel (commessa+descrizione → rif)
        $rifMap = $this->caricaRifOrdMaxtris();

        // 5. Prepara righe con RIF. ORD. accanto a ogni articolo
        $righeFinali = $this->preparaRighe($righe, $rifMap);

        // 6. Formatta dati
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

        // Trasporto a cura: Cedente/Cessionario/Vettore
        $trasportoCura = 'Cedente';
        if ($coda && $coda->TrasportoACura) {
            $map = [1 => 'Cedente', 2 => 'Cessionario', 3 => 'Vettore'];
            $trasportoCura = $map[$coda->TrasportoACura] ?? 'Cedente';
        }

        // Causale trasporto: mappa codici comuni
        $causale = 'Vendita';
        if ($coda && $coda->CausaleTrasporto) {
            $causaliMap = [1 => 'Vendita', 2 => 'Conto lavorazione', 3 => 'Conto visione', 4 => 'Reso'];
            $causale = $causaliMap[$coda->CausaleTrasporto] ?? $coda->CausaleTrasporto;
        }

        // Note: righe di testo (TipoRiga=3), escluse intestazioni "Rif. Ord.Cli."
        $noteRighe = collect($righe)
            ->filter(fn($r) => $r->TipoRiga == 3
                && !empty(trim($r->Descrizione ?? ''))
                && !str_starts_with(trim($r->Descrizione), 'Rif. Ord.Cli.'))
            ->pluck('Descrizione')
            ->implode("\n");

        // Nazione: mappa codice ISO → nome
        $nazione = 'Italia';
        if ($testa->ClienteNazione && !in_array($testa->ClienteNazione, ['IT', 'it', ''])) {
            $nazione = $testa->ClienteNazione;
        }

        $data = [
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

        $pdf = Pdf::loadView('ddt.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("DDT_{$numeroPadded}.pdf");
    }

    /**
     * Carica mappatura dall'Excel ORDINE ASTUCCI.xlsx
     * Chiave: "commessa_corta|descrizione_normalizzata" → RIF. ORD. MAXTRIS
     * Fallback: "commessa_corta" → RIF (primo trovato)
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
        $map = [];          // "commessa|desc_normalizzata" → rif
        $mapCommessa = [];  // "commessa" → rif (primo trovato, fallback)

        foreach ($sheet->getRowIterator(2) as $row) {
            $r = $row->getRowIndex();
            $commessa = trim($sheet->getCell("F$r")->getValue() ?? '');
            $descrizione = trim($sheet->getCell("B$r")->getValue() ?? '');
            $rif = trim($sheet->getCell("G$r")->getValue() ?? '');

            if (!$rif) continue;

            // Gestisci commesse multiple (es. "66050-66055-66056")
            $commesse = array_map('trim', explode('-', $commessa));
            // Se sono tutte numeriche, sono commesse separate; altrimenti è una sola stringa
            $tutteNumeriche = collect($commesse)->every(fn($c) => is_numeric($c));

            if ($tutteNumeriche && count($commesse) > 1) {
                foreach ($commesse as $c) {
                    $key = $c . '|' . $this->normalizza($descrizione);
                    $map[$key] = $rif;
                    if (!isset($mapCommessa[$c])) $mapCommessa[$c] = $rif;
                }
            } else {
                $key = $commessa . '|' . $this->normalizza($descrizione);
                $map[$key] = $rif;
                if (!isset($mapCommessa[$commessa])) $mapCommessa[$commessa] = $rif;
            }
        }

        return ['dettaglio' => $map, 'commessa' => $mapCommessa];
    }

    /**
     * Normalizza descrizione per matching fuzzy:
     * rimuove spazi extra, punteggiatura, maiuscolo
     */
    private function normalizza(string $desc): string
    {
        $desc = mb_strtoupper($desc);
        $desc = preg_replace('/[^A-Z0-9]/', '', $desc);
        return $desc;
    }

    /**
     * Converte commessa Onda (0066649-26) → formato corto Excel (66649)
     */
    private function commessaCorta(string $codCommessa): string
    {
        // "0066649-26" → prendi prima del trattino e rimuovi zeri iniziali
        $parts = explode('-', $codCommessa);
        return ltrim($parts[0], '0') ?: '0';
    }

    /**
     * Prepara righe articoli con RIF. ORD. MAXTRIS
     */
    private function preparaRighe(array $righe, array $rifMap): array
    {
        $risultato = [];
        $dettaglio = $rifMap['dettaglio'] ?? [];
        $perCommessa = $rifMap['commessa'] ?? [];

        foreach ($righe as $riga) {
            if ($riga->TipoRiga != 1) continue;

            $codCommessa = trim($riga->CodCommessa ?? '');
            $commCorta = $this->commessaCorta($codCommessa);
            $descNorm = $this->normalizza($riga->Descrizione ?? '');

            // Cerca match esatto commessa+descrizione
            $rifOrd = $dettaglio[$commCorta . '|' . $descNorm] ?? '';

            // Fallback: solo per commessa
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
