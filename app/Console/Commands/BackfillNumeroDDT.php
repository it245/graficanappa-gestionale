<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ordine;

class BackfillNumeroDDT extends Command
{
    protected $signature = 'app:backfill-numero-ddt';
    protected $description = 'Popola numero_ddt_vendita e vettore_ddt da Onda per ordini esistenti con ddt_vendita_id';

    public function handle()
    {
        $ordini = Ordine::whereNotNull('ddt_vendita_id')
            ->where('ddt_vendita_id', '!=', 0)
            ->where(function ($q) {
                $q->whereNull('numero_ddt_vendita')
                  ->orWhere('numero_ddt_vendita', '')
                  ->orWhereNull('vettore_ddt')
                  ->orWhere('vettore_ddt', '');
            })
            ->get();

        $this->info("Ordini da aggiornare: {$ordini->count()}");

        $aggiornati = 0;
        foreach ($ordini as $ordine) {
            try {
                $ddt = DB::connection('onda')->select("
                    SELECT t.NumeroDocumento, v.RagioneSociale AS Vettore
                    FROM ATTDocTeste t
                    LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
                    LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
                    WHERE t.IdDoc = ?
                ", [$ordine->ddt_vendita_id]);

                if (!empty($ddt)) {
                    $numDoc = trim($ddt[0]->NumeroDocumento);
                    $vettore = trim($ddt[0]->Vettore ?? '');
                    $ordine->update([
                        'numero_ddt_vendita' => $numDoc,
                        'vettore_ddt' => $vettore,
                    ]);
                    $this->line("  #{$ordine->id} {$ordine->commessa} -> DDT {$numDoc} | Vettore: {$vettore}");
                    $aggiornati++;
                } else {
                    $this->warn("  #{$ordine->id} ddt_id={$ordine->ddt_vendita_id} non trovato in Onda");
                }
            } catch (\Exception $e) {
                $this->error("  Errore ordine #{$ordine->id}: {$e->getMessage()}");
            }
        }

        $this->info("Aggiornati: {$aggiornati}/{$ordini->count()}");
    }
}
