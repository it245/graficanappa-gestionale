<?php

namespace App\Http\Services;

use App\Models\PrinectAttivita;
use App\Models\Ordine;
use App\Models\OrdineFase;

class PrinectSyncService
{
    protected PrinectService $prinect;

    public function __construct(PrinectService $prinect)
    {
        $this->prinect = $prinect;
    }

    public function sincronizzaAttivita(): int
    {
        $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
        $data = $this->prinect->getDeviceActivity($deviceId);

        if (!$data || !isset($data['activities'])) {
            return 0;
        }

        $importate = 0;
        $commesseAggiornate = [];

        foreach ($data['activities'] as $att) {
            $startTime = isset($att['startTime']) ? date('Y-m-d H:i:s', strtotime($att['startTime'])) : null;
            $endTime = isset($att['endTime']) ? date('Y-m-d H:i:s', strtotime($att['endTime'])) : null;

            if (!$startTime) continue;

            // Dedup: skip se esiste gia' con stesso device_id + start_time + activity_id
            $esiste = PrinectAttivita::where('device_id', $deviceId)
                ->where('start_time', $startTime)
                ->where('activity_id', $att['id'] ?? null)
                ->exists();

            if ($esiste) continue;

            // Estrai commessa dal job_id: es. job_id "66455" => "0066455-26"
            $jobId = $att['workstep']['job']['id'] ?? null;
            $commessa = null;
            if ($jobId && is_numeric($jobId)) {
                $anno = date('y');
                $commessa = str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-' . $anno;
            }

            // Operatori: unisci nomi
            $operatori = '';
            if (!empty($att['employees'])) {
                $nomi = array_map(function ($e) {
                    return trim(($e['firstName'] ?? '') . ' ' . ($e['name'] ?? ''));
                }, $att['employees']);
                $operatori = implode(', ', $nomi);
            }

            PrinectAttivita::create([
                'device_id'            => $att['device']['id'] ?? $deviceId,
                'device_name'          => $att['device']['name'] ?? null,
                'activity_id'          => $att['id'] ?? null,
                'activity_name'        => $att['name'] ?? null,
                'time_type_name'       => $att['timeTypeName'] ?? null,
                'time_type_group'      => $att['timeTypeGroupName'] ?? null,
                'prinect_job_id'       => $jobId,
                'prinect_job_name'     => $att['workstep']['job']['name'] ?? null,
                'commessa_gestionale'  => $commessa,
                'workstep_name'        => $att['workstep']['name'] ?? null,
                'good_cycles'          => $att['goodCycles'] ?? 0,
                'waste_cycles'         => $att['wasteCycles'] ?? 0,
                'start_time'           => $startTime,
                'end_time'             => $endTime,
                'operatore_prinect'    => $operatori ?: null,
                'cost_center'          => $att['costCenter'] ?? null,
            ]);

            $importate++;

            if ($commessa) {
                $commesseAggiornate[$commessa] = true;
            }
        }

        // Aggiorna fogli_buoni/scarto sulle fasi di stampa offset per ogni commessa toccata
        foreach (array_keys($commesseAggiornate) as $commessa) {
            $this->aggiornaFogliCommessa($commessa);
        }

        return $importate;
    }

    /**
     * Aggiorna fogli_buoni, fogli_scarto, tempo_avviamento_sec, tempo_esecuzione_sec
     * sulle fasi di stampa offset (STAMPAXL106*) della commessa dal totale Prinect.
     */
    protected function aggiornaFogliCommessa(string $commessa): void
    {
        // Somma totali da Prinect per questa commessa
        $totali = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->selectRaw('
                SUM(good_cycles) as fogli_buoni,
                SUM(waste_cycles) as fogli_scarto
            ')
            ->first();

        $tempi = PrinectAttivita::where('commessa_gestionale', $commessa)->get();
        $secAvviamento = 0;
        $secProduzione = 0;
        foreach ($tempi as $att) {
            if (!$att->start_time || !$att->end_time) continue;
            $diff = $att->start_time->diffInSeconds($att->end_time);
            if ($att->activity_name === 'Avviamento') {
                $secAvviamento += $diff;
            } else {
                $secProduzione += $diff;
            }
        }

        // Trova le fasi di stampa offset (STAMPAXL106*) per questa commessa
        $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
            ->where('fase', 'LIKE', 'STAMPAXL106%')
            ->get();

        foreach ($fasi as $fase) {
            $fase->fogli_buoni = $totali->fogli_buoni ?? 0;
            $fase->fogli_scarto = $totali->fogli_scarto ?? 0;
            $fase->tempo_avviamento_sec = $secAvviamento;
            $fase->tempo_esecuzione_sec = $secProduzione;
            $fase->save();
        }
    }
}
