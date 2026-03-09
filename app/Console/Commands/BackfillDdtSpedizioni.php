<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ordine;
use App\Models\DdtSpedizione;

class BackfillDdtSpedizioni extends Command
{
    protected $signature = 'app:backfill-ddt-spedizioni {--days=90 : Quanti giorni indietro cercare}';
    protected $description = 'Popola la tabella ddt_spedizioni da Onda per tutti i DDT vendita recenti';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Cercando DDT vendita degli ultimi {$days} giorni su Onda...");

        $righeDDT = DB::connection('onda')->select("
            SELECT t.IdDoc, r.CodCommessa, t.DataDocumento, t.NumeroDocumento,
                   a.RagioneSociale AS Cliente,
                   v.RagioneSociale AS Vettore,
                   SUM(r.Qta) AS QtaDDT
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
            LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
            WHERE t.TipoDocumento = 3
              AND t.DataRegistrazione >= DATEADD(day, -?, GETDATE())
              AND r.CodCommessa IS NOT NULL AND r.CodCommessa != ''
              AND r.TipoRiga = 1
            GROUP BY t.IdDoc, r.CodCommessa, t.DataDocumento, t.NumeroDocumento, a.RagioneSociale, v.RagioneSociale
        ", [$days]);

        $this->info("Trovate " . count($righeDDT) . " righe DDT su Onda.");

        $creati = 0;
        $aggiornati = 0;
        $skipped = 0;

        foreach ($righeDDT as $riga) {
            $codCommessa = trim($riga->CodCommessa ?? '');
            if (!$codCommessa) continue;

            $ordine = Ordine::where('commessa', $codCommessa)->first();
            if (!$ordine) {
                $skipped++;
                continue;
            }

            $result = DdtSpedizione::updateOrCreate(
                ['onda_id_doc' => $riga->IdDoc, 'commessa' => $codCommessa],
                [
                    'numero_ddt'   => trim($riga->NumeroDocumento ?? ''),
                    'data_ddt'     => $riga->DataDocumento ? substr($riga->DataDocumento, 0, 10) : null,
                    'vettore'      => trim($riga->Vettore ?? ''),
                    'cliente_nome' => trim($riga->Cliente ?? ''),
                    'ordine_id'    => $ordine->id,
                    'qta'          => (float) ($riga->QtaDDT ?? 0),
                ]
            );

            if ($result->wasRecentlyCreated) {
                $creati++;
            } else {
                $aggiornati++;
            }
        }

        $this->info("Completato: {$creati} creati, {$aggiornati} aggiornati, {$skipped} commesse non trovate nel MES.");

        // Mostra riepilogo BRT
        $brtCount = DdtSpedizione::where('vettore', 'LIKE', '%BRT%')->count();
        $brtDDT = DdtSpedizione::where('vettore', 'LIKE', '%BRT%')
            ->select('numero_ddt')
            ->distinct()
            ->count();
        $this->info("DDT BRT: {$brtCount} righe, {$brtDDT} DDT unici.");
    }
}
