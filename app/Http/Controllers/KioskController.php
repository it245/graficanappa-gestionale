<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Models\Reparto;
use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;
use App\Services\SolarLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    public function index()
    {
        $oggi = Carbon::today();

        // === KPI HEADER ===
        $completateOggi = OrdineFase::where('stato', 3)
            ->where(function ($q) use ($oggi) {
                $q->whereDate('data_fine', $oggi)
                  ->orWhereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi));
            })->count();

        $inCorso = OrdineFase::whereRaw("stato REGEXP '^[0-9]+$'")
            ->where('stato', 2)
            ->where(fn($q) => $q->where('esterno', 0)->orWhereNull('esterno'))
            ->count();

        $inCoda = OrdineFase::whereRaw("stato REGEXP '^[0-9]+$'")
            ->where('stato', 1)
            ->where(fn($q) => $q->where('esterno', 0)->orWhereNull('esterno'))
            ->count();

        $fustelleAttive = OrdineFase::whereHas('faseCatalogo', fn($q) =>
            $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', ['fustella piana', 'fustella cilindrica']))
        )->where('stato', '<', 3)->count();

        // === PRINECT LIVE per XL106 ===
        $prinectLive = null;
        try {
            $prinect = app(PrinectService::class);
            $deviceData = $prinect->getDevices();
            $device = $deviceData['devices'][0] ?? $deviceData ?? null;
            if ($device && isset($device['deviceStatus'])) {
                $status = $device['deviceStatus'];
                $wsName = $status['workstepName'] ?? '';
                $jobName = $status['jobName'] ?? '';
                $employees = $status['employees'] ?? [];
                $operatore = !empty($employees) ? ($employees[0]['name'] ?? '') : '';
                // Estrai commessa dal jobName (es. "66849" → "0066849-26")
                $jobIdNum = PrinectSyncService::estraiJobIdNumerico($status['jobId'] ?? $jobName);
                $commessa = $jobIdNum ? str_pad($jobIdNum, 7, '0', STR_PAD_LEFT) . '-' . date('y') : '';
                $ordine = $commessa ? Ordine::where('commessa', $commessa)->first() : null;

                if ($commessa && $status['status'] ?? '' !== 'Idle') {
                    $prinectLive = [
                        'commessa' => $commessa,
                        'cliente' => $ordine->cliente_nome ?? '-',
                        'descrizione' => \Illuminate\Support\Str::limit($ordine->descrizione ?? $jobName, 50),
                        'operatore' => $operatore,
                        'job' => $jobName,
                        'status' => $status['status'] ?? 'Idle',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Prinect non disponibile
        }

        // === ZONA 1: MACCHINE IN CORSO ===
        $macchineConfig = [
            ['nome' => 'XL 106', 'reparti' => ['stampa offset'], 'fasi' => ['STAMPAXL106%', 'STAMPA XL%']],
            ['nome' => 'BOBST', 'reparti' => ['fustella piana']],
            ['nome' => 'JOH Caldo', 'reparti' => ['stampa a caldo']],
            ['nome' => 'Plastificatrice', 'reparti' => ['plastificazione']],
            ['nome' => 'Piegaincolla', 'reparti' => ['piegaincolla']],
            ['nome' => 'Finestratrice', 'reparti' => ['finestratura']],
            ['nome' => 'Fustella Cilindrica', 'reparti' => ['fustella cilindrica']],
            ['nome' => 'Canon V900', 'reparti' => ['digitale']],
            ['nome' => 'Finitura Digitale', 'reparti' => ['finitura digitale']],
            ['nome' => 'Tagliacarte', 'reparti' => ['tagliacarte']],
            ['nome' => 'Legatoria', 'reparti' => ['legatoria']],
        ];

        $macchine = [];
        foreach ($macchineConfig as $mc) {
            // XL106: usa Prinect live se disponibile
            if ($mc['nome'] === 'XL 106' && $prinectLive && $prinectLive['status'] !== 'Idle') {
                $macchine[] = [
                    'nome' => $mc['nome'],
                    'attiva' => true,
                    'commessa' => $prinectLive['commessa'],
                    'cliente' => $prinectLive['cliente'],
                    'descrizione' => $prinectLive['descrizione'],
                    'ore_lav' => '', // Prinect non dà ore in tempo reale
                ];
                continue;
            }

            $query = OrdineFase::with(['ordine', 'operatori', 'faseCatalogo.reparto'])
                ->where('stato', 2)
                ->where(fn($q) => $q->where('esterno', 0)->orWhereNull('esterno'))
                ->where(fn($q) => $q->whereNull('note')->orWhere('note', 'NOT LIKE', '%Inviato a:%'))
                ->whereHas('faseCatalogo', fn($q) =>
                    $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', $mc['reparti']))
                );

            // Per stampa offset, filtra per nome fase
            if (isset($mc['fasi'])) {
                $query->where(function ($q) use ($mc) {
                    foreach ($mc['fasi'] as $f) {
                        $q->orWhere('fase', 'LIKE', $f);
                    }
                });
            }

            $fase = $query->orderBy('priorita')->first();

            if ($fase) {
                // Calcola ore previste e lavorate
                $fasiOre = config('fasi_ore');
                $infoFase = $fasiOre[$fase->fase] ?? null;
                $orePrev = 0;
                if ($infoFase) {
                    $qtaCarta = $fase->ordine->qta_carta ?? 0;
                    $copieh = $infoFase['copieh'] ?: 1000;
                    $orePrev = round($infoFase['avviamento'] + ($qtaCarta / $copieh), 1);
                }

                $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
                if ($secPrinect > 0) {
                    $oreLav = round($secPrinect / 3600, 1);
                } else {
                    $totSecPausa = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);
                    $di = $fase->operatori->whereNotNull('pivot.data_inizio')->sortBy('pivot.data_inizio')->first()?->pivot->data_inizio;
                    $secLordo = $di ? abs(now()->getTimestamp() - Carbon::parse($di)->getTimestamp()) : 0;
                    $oreLav = round(max($secLordo - $totSecPausa, 0) / 3600, 1);
                }

                $operatore = $fase->operatori->pluck('nome')->implode(', ') ?: '';

                $macchine[] = [
                    'nome' => $mc['nome'],
                    'attiva' => true,
                    'commessa' => $fase->ordine->commessa ?? '-',
                    'cliente' => $fase->ordine->cliente_nome ?? '-',
                    'descrizione' => \Illuminate\Support\Str::limit($fase->ordine->descrizione ?? '-', 50),
                    'ore_lav' => $oreLav,
                    'ore_prev' => $orePrev ?: 1,
                ];
            } else {
                $macchine[] = [
                    'nome' => $mc['nome'],
                    'attiva' => false,
                    'commessa' => '', 'cliente' => '', 'descrizione' => '',
                    'ore_lav' => 0, 'ore_prev' => 0,
                ];
            }
        }

        // === ZONA 2: PROSSIMI LAVORI ===
        $prossimi = [];
        foreach ($macchineConfig as $mc) {
            // Prima cerca stato 1 (pronto), se non ce ne sono cerca stato 0 (caricato)
            $fasiCoda = OrdineFase::with('ordine')
                ->where('stato', 1)
                ->where(fn($q) => $q->where('esterno', 0)->orWhereNull('esterno'))
                ->whereHas('faseCatalogo', fn($q) =>
                    $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', $mc['reparti']))
                )
                ->orderBy('priorita')
                ->limit(3)
                ->get();

            if ($fasiCoda->isEmpty()) {
                $fasiCoda = OrdineFase::with('ordine')
                    ->where('stato', 0)
                    ->where(fn($q) => $q->where('esterno', 0)->orWhereNull('esterno'))
                    ->whereHas('faseCatalogo', fn($q) =>
                        $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', $mc['reparti']))
                    )
                    ->orderBy('priorita')
                    ->limit(3)
                    ->get();
            }

            if ($fasiCoda->isNotEmpty()) {
                $items = [];
                foreach ($fasiCoda as $f) {
                    $desc = \Illuminate\Support\Str::limit($f->ordine->descrizione ?? '-', 45);
                    $fustella = \App\Helpers\DescrizioneParser::parseFustella(
                        $f->ordine->descrizione ?? '', $f->ordine->cliente_nome ?? '', ''
                    );
                    $commessa = $f->ordine->commessa ?? '-';
                    $items[] = ['desc' => $desc, 'badge' => $commessa, 'badge_cls' => 'verde'];
                }
                $prossimi[strtoupper($mc['nome'])] = $items;
            }
        }

        // === ZONA 3: OBIETTIVO ===
        // Fasi completate nell'ultima ora
        $ultimaOra = OrdineFase::where('stato', 3)
            ->where('data_fine', '>=', now()->subHour())
            ->count();

        // Ore segnate oggi (somma pivot)
        $oreSegnate = DB::table('fase_operatore')
            ->whereDate('data_inizio', $oggi)
            ->whereNotNull('data_fine')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, data_inizio, data_fine) - COALESCE(secondi_pausa, 0)) as sec')
            ->value('sec');
        $oreSegnate = round(max($oreSegnate ?? 0, 0) / 3600, 1);

        // === ZONA 4: UTILIZZO MACCHINE ===
        $utilizzo = [];
        $repartiUtilizzo = [
            ['nome' => 'XL 106 (16h)', 'reparti' => ['stampa offset'], 'ore_disp' => 16],  // 6-22
            ['nome' => 'BOBST', 'reparti' => ['fustella piana'], 'ore_disp' => 16],       // 6-22
            ['nome' => 'JOH Caldo', 'reparti' => ['stampa a caldo'], 'ore_disp' => 16],   // 6-22
            ['nome' => 'Plastificatrice', 'reparti' => ['plastificazione'], 'ore_disp' => 8], // 8-17 (1h pausa)
            ['nome' => 'Piegaincolla', 'reparti' => ['piegaincolla'], 'ore_disp' => 14],  // 6-20
            ['nome' => 'Finestratrice', 'reparti' => ['finestratura'], 'ore_disp' => 14], // 6-20
            ['nome' => 'Fustella Cilindrica', 'reparti' => ['fustella cilindrica'], 'ore_disp' => 8], // 8-17 (1h pausa)
            ['nome' => 'Canon V900', 'reparti' => ['digitale', 'finitura digitale'], 'ore_disp' => 8], // 8-17 (1h pausa)
            ['nome' => 'Tagliacarte', 'reparti' => ['tagliacarte'], 'ore_disp' => 8],     // 8-17 (1h pausa)
            ['nome' => 'Legatoria', 'reparti' => ['legatoria'], 'ore_disp' => 14],        // 6-20
        ];

        // Ore trascorse dall'inizio turno per calcolare % proporzionale
        $oraCorrente = (float) now()->format('H') + (float) now()->format('i') / 60;

        foreach ($repartiUtilizzo as $ru) {
            $repartoIds = Reparto::whereIn('nome', $ru['reparti'])->pluck('id');
            // Conta ore di oggi: fasi avviate oggi O ancora in corso (data_fine NULL) O finite oggi
            // Per fasi avviate prima di oggi ma ancora in corso, conta solo da mezzanotte
            $inizioOggi = $oggi . ' 00:00:00';
            $secOggi = DB::table('fase_operatore')
                ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
                ->where(function ($q) use ($oggi) {
                    $q->whereDate('fase_operatore.data_inizio', $oggi)           // avviate oggi
                      ->orWhere(function ($q2) use ($oggi) {
                          $q2->where('fase_operatore.data_inizio', '<', $oggi . ' 00:00:00')
                             ->where(function ($q3) use ($oggi) {
                                 $q3->whereNull('fase_operatore.data_fine')       // ancora in corso
                                    ->orWhereDate('fase_operatore.data_fine', $oggi); // finite oggi
                             });
                      });
                })
                ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(fase_operatore.data_inizio, ?), COALESCE(fase_operatore.data_fine, NOW())) - COALESCE(fase_operatore.secondi_pausa, 0)) as sec", [$inizioOggi])
                ->value('sec');

            $oreUsate = round(max($secOggi ?? 0, 0) / 3600, 1);

            // Calcola ore disponibili proporzionali al momento della giornata
            $oreDisp = $ru['ore_disp'];
            if ($oreDisp == 24) {
                // 3 turni: disponibile da mezzanotte, proporzionale a ora corrente
                $oreDispOra = max($oraCorrente, 1);
            } elseif ($oreDisp == 16) {
                // 6-22: disponibile dalle 6
                $oreDispOra = max(min($oraCorrente - 6, 16), 1);
            } elseif ($oreDisp == 14) {
                // 6-20: disponibile dalle 6
                $oreDispOra = max(min($oraCorrente - 6, 14), 1);
            } else {
                // 8-17 (8h con pausa): disponibile dalle 8
                $oreDispOra = max(min($oraCorrente - 8, 8), 1);
            }

            $pct = $oreDispOra > 0 ? min(round(($oreUsate / $oreDispOra) * 100), 100) : 0;

            $utilizzo[] = ['nome' => $ru['nome'], 'pct' => $pct];
        }

        // === SOLAR ===
        $solar = (new SolarLogService())->getDati();

        // === NOTA TV ===
        $notaTv = Cache::get('kiosk_nota_tv', '');

        return view('kiosk', [
            'kpi' => [
                'completate' => $completateOggi,
                'in_corso' => $inCorso,
                'in_coda' => $inCoda,
                'fustelle' => $fustelleAttive,
            ],
            'macchine' => $macchine,
            'prossimi' => $prossimi,
            'obiettivo' => [
                'completate' => $completateOggi,
                'ultima_ora' => $ultimaOra,
                'ore' => $oreSegnate,
            ],
            'utilizzo' => $utilizzo,
            'solar' => $solar,
            'notaTv' => $notaTv,
        ]);
    }

    /**
     * Salva la nota TV (chiamata dall'owner)
     */
    public function salvaNota(Request $request)
    {
        $nota = trim($request->input('nota', ''));
        try {
            Cache::put('kiosk_nota_tv', $nota, now()->addHours(24));
        } catch (\Throwable $e) {
            // Fallback: salva in file
            file_put_contents(storage_path('app/kiosk_nota.txt'), $nota);
        }
        return response()->json(['success' => true]);
    }

    /**
     * Restituisce la nota TV corrente
     */
    public function getNota()
    {
        $nota = Cache::get('kiosk_nota_tv', '');
        if (!$nota && file_exists(storage_path('app/kiosk_nota.txt'))) {
            $nota = file_get_contents(storage_path('app/kiosk_nota.txt'));
        }
        return response()->json(['nota' => $nota]);
    }
}
