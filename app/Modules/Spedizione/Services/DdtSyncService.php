<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Services;

use App\Models\DdtSpedizione;
use App\Models\Ordine;
use App\Services\DdtPdfService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sincronizza i DDT di vendita da Onda (SQL Server) → MES (MySQL).
 *
 * Strangler Fig: estrazione dal God Class {@see \App\Services\OndaSyncService}
 * (metodo storico {@see \App\Services\OndaSyncService::sincronizzaDDTVendita()}).
 *
 * Logica SQL identica all'originale:
 *  - GROUP BY t.IdDoc, r.CodCommessa, r.CodArt
 *  - match Ordine per (commessa + cod_art), fallback only-commessa
 *  - DdtSpedizione::updateOrCreate per (onda_id_doc, commessa)
 *  - Aggiorna SEMPRE qta_ddt_vendita sull'Ordine (legacy field)
 *  - Genera PDF DDT solo per record nuovi
 */
final class DdtSyncService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Esegue la sync dei DDT di vendita su una finestra di N giorni.
     *
     * @param int $giorni Finestra (giorni indietro su DataRegistrazione Onda).
     * @return array{inseriti:int, aggiornati:int, errori:int}
     */
    public function syncFromOnda(int $giorni = 30): array
    {
        $inseriti = 0;
        $aggiornati = 0;
        $errori = 0;

        // CodCommessa + CodArt nelle RIGHE: GROUP BY anche cod_art per distinguere
        // articoli stessa commessa.
        $righeDDT = $this->db->connection('onda')->select("
            SELECT t.IdDoc, r.CodCommessa, r.CodArt, t.DataDocumento, t.NumeroDocumento,
                   a.RagioneSociale AS Cliente,
                   v.RagioneSociale AS Vettore,
                   SUM(r.Qta) AS QtaDDT
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
            LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
            WHERE t.TipoDocumento = 3
              AND t.DataRegistrazione >= DATEADD(day, ?, GETDATE())
              AND r.CodCommessa IS NOT NULL AND r.CodCommessa != ''
              AND r.TipoRiga = 1
            GROUP BY t.IdDoc, r.CodCommessa, r.CodArt, t.DataDocumento, t.NumeroDocumento, a.RagioneSociale, v.RagioneSociale
        ", [-1 * abs($giorni)]);

        if (empty($righeDDT)) {
            Log::info('DDT sync', ['inseriti' => 0, 'aggiornati' => 0, 'errori' => 0]);
            return ['inseriti' => 0, 'aggiornati' => 0, 'errori' => 0];
        }

        $pdfGenerati = []; // traccia DDT per cui abbiamo già generato il PDF

        $this->db->connection()->transaction(function () use ($righeDDT, &$inseriti, &$aggiornati, &$errori, &$pdfGenerati) {
            foreach ($righeDDT as $riga) {
                try {
                    [$wasNew, $touched] = $this->processaRiga($riga, $pdfGenerati);
                    if ($wasNew) {
                        $inseriti++;
                    } elseif ($touched) {
                        $aggiornati++;
                    }
                } catch (Throwable $e) {
                    $errori++;
                    Log::warning('DDT sync riga: errore', [
                        'commessa' => $riga->CodCommessa ?? null,
                        'idDoc'    => $riga->IdDoc ?? null,
                        'errore'   => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('DDT sync', [
            'inseriti'   => $inseriti,
            'aggiornati' => $aggiornati,
            'errori'     => $errori,
        ]);

        return [
            'inseriti'   => $inseriti,
            'aggiornati' => $aggiornati,
            'errori'     => $errori,
        ];
    }

    /**
     * @param object   $riga         riga aggregata SQL Server
     * @param string[] $pdfGenerati  ref: lista DDT per cui PDF già rigenerato in questa run
     * @return array{0:bool,1:bool} [wasNew, touched]
     */
    private function processaRiga(object $riga, array &$pdfGenerati): array
    {
        $codCommessa = trim($riga->CodCommessa ?? '');
        if ($codCommessa === '') {
            return [false, false];
        }

        $idDoc = $riga->IdDoc;
        $qtaDDT = (float) ($riga->QtaDDT ?? 0);
        $numeroDDT = trim($riga->NumeroDocumento ?? '');
        $vettore = trim($riga->Vettore ?? '');
        $cliente = trim($riga->Cliente ?? '');
        $codArt = trim($riga->CodArt ?? '');

        // Match per commessa + cod_art (ordini diversi stessa commessa = articoli distinti)
        // Fallback al primo ordine se cod_art non match (compatibilita' DDT vecchi)
        $ordine = Ordine::where('commessa', $codCommessa)
            ->where('cod_art', $codArt)
            ->first();
        if (!$ordine) {
            $ordine = Ordine::where('commessa', $codCommessa)->first();
        }

        $ddtNuovo = false;
        if ($ordine) {
            $esistente = DdtSpedizione::where('onda_id_doc', $idDoc)
                ->where('commessa', $codCommessa)
                ->exists();

            DdtSpedizione::updateOrCreate(
                ['onda_id_doc' => $idDoc, 'commessa' => $codCommessa],
                [
                    'numero_ddt'   => $numeroDDT,
                    'data_ddt'     => $riga->DataDocumento ? substr($riga->DataDocumento, 0, 10) : null,
                    'vettore'      => $vettore,
                    'cliente_nome' => $cliente,
                    'ordine_id'    => $ordine->id,
                    'qta'          => $qtaDDT,
                ]
            );

            if (!$esistente) {
                $ddtNuovo = true;
            }

            // Aggiorna campo legacy sull'ordine (sempre, non solo prima volta)
            // Cosi' ordini con cod_art diverso ricevono qta_ddt_vendita corretta
            $ordine->update([
                'ddt_vendita_id'      => $idDoc,
                'numero_ddt_vendita'  => $numeroDDT,
                'vettore_ddt'         => $vettore,
                'qta_ddt_vendita'     => $qtaDDT,
            ]);
        }

        Log::info("DDT Vendita: commessa {$codCommessa} DDT {$numeroDDT} (IdDoc {$idDoc}, qta: {$qtaDDT})");

        // Genera PDF automaticamente solo per DDT nuovi (non già in DB)
        if ($ddtNuovo && $numeroDDT && !in_array($numeroDDT, $pdfGenerati, true)) {
            try {
                DdtPdfService::generaESalva($numeroDDT);
                $pdfGenerati[] = $numeroDDT;
            } catch (\Exception $e) {
                Log::warning("DDT PDF: errore generazione DDT {$numeroDDT}: " . $e->getMessage());
            }
        }

        return [$ddtNuovo, (bool) $ordine];
    }
}
