<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Prinect;

use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\Enums\StatoWorkstep;
use App\Modules\Prinect\Services\PrinectJobsService;
use PHPUnit\Framework\TestCase;

/**
 * Unit test per PrinectJobsService.
 *
 * Mocka PrinectApiInterface (no Http reale) per validare:
 *  - filtraggio worksteps di stampa convenzionale,
 *  - mapping commessa <-> jobId Prinect,
 *  - estrazione job ID numerico,
 *  - "stampa confermata" inclusa la fallback per il bug 66811
 *    (workstep COMPLETED senza actualStartDate ma con activities reali).
 */
class PrinectJobsServiceTest extends TestCase
{
    /**
     * Fake minimale di PrinectApiInterface: registriamo le risposte canned
     * per i metodi usati dai test.
     */
    private function fakeApi(array $stubs = []): PrinectApiInterface
    {
        return new class($stubs) implements PrinectApiInterface {
            public function __construct(public array $stubs) {}
            public function isConfigured(): bool { return true; }
            public function getDevices(): ?array { return $this->stubs['devices'] ?? null; }
            public function getDeviceActivity(string $d, ?string $s = null, ?string $e = null): ?array { return null; }
            public function getDeviceConsumption(string $d, ?string $s = null, ?string $e = null): ?array { return null; }
            public function getJobs(?string $m = null, ?string $g = null): ?array { return null; }
            public function getJob(string $j): ?array { return null; }
            public function getWorksteps(string $j): ?array { return $this->stubs['ws'][$j] ?? null; }
            public function getWorkstepActivities(string $j, string $w): ?array {
                return $this->stubs['act']["{$j}/{$w}"] ?? null;
            }
            public function getWorkstepInk(string $j, string $w): ?array { return null; }
            public function getWorkstepQuality(string $j, string $w): ?array { return null; }
            public function getWorkstepPreview(string $j, string $w): ?array { return null; }
            public function getMilestones(?string $w = null): ?array { return null; }
        };
    }

    public function test_estrai_job_id_numerico(): void
    {
        $this->assertSame('66698', PrinectJobsService::estraiJobIdNumerico('66698 int'));
        $this->assertSame('66410', PrinectJobsService::estraiJobIdNumerico('66410'));
        $this->assertSame('66811', PrinectJobsService::estraiJobIdNumerico(66811));
        $this->assertNull(PrinectJobsService::estraiJobIdNumerico('abc'));
        $this->assertNull(PrinectJobsService::estraiJobIdNumerico(null));
        $this->assertNull(PrinectJobsService::estraiJobIdNumerico(''));
    }

    public function test_commessa_to_job_id_e_viceversa(): void
    {
        $svc = new PrinectJobsService($this->fakeApi());

        $this->assertSame('66811', $svc->commessaToJobId('0066811-26'));
        $this->assertSame('66811', $svc->commessaToJobId('66811-26'));
        $this->assertNull($svc->commessaToJobId('XXX-26'));

        $this->assertSame('0066811-26', $svc->jobIdToCommessa('66811', '26'));
    }

    public function test_get_worksteps_stampa_filtra_solo_conventional_printing(): void
    {
        $svc = new PrinectJobsService($this->fakeApi([
            'ws' => [
                '66811' => [
                    'worksteps' => [
                        ['id' => 'WS1', 'types' => ['Plate'], 'status' => 'COMPLETED'],
                        ['id' => 'WS2', 'types' => ['ConventionalPrinting'], 'status' => 'COMPLETED'],
                        ['id' => 'WS3', 'types' => ['ConventionalPrinting', 'Cutting'], 'status' => 'WAITING'],
                    ],
                ],
            ],
        ]));

        $result = $svc->getWorkstepsStampa('66811');

        $this->assertCount(2, $result);
        $this->assertSame(['WS2', 'WS3'], $result->pluck('id')->all());
    }

    public function test_get_worksteps_by_stato(): void
    {
        $svc = new PrinectJobsService($this->fakeApi([
            'ws' => [
                '66811' => [
                    'worksteps' => [
                        ['id' => 'A', 'types' => ['ConventionalPrinting'], 'status' => 'COMPLETED'],
                        ['id' => 'B', 'types' => ['ConventionalPrinting'], 'status' => 'WAITING'],
                        ['id' => 'C', 'types' => ['ConventionalPrinting'], 'status' => 'COMPLETED'],
                    ],
                ],
            ],
        ]));

        $completed = $svc->getWorkstepsByStato('66811', StatoWorkstep::Completed);

        $this->assertSame(['A', 'C'], $completed->pluck('id')->all());
    }

    public function test_stampa_confermata_se_actual_start_date_presente(): void
    {
        $svc = new PrinectJobsService($this->fakeApi());

        $this->assertTrue($svc->stampaConfermata('66811', [
            'id' => 'WS1',
            'status' => 'COMPLETED',
            'actualStartDate' => '2026-05-07T08:00:00+02:00',
            'amountProduced' => 5000,
        ]));
    }

    public function test_stampa_confermata_fallback_via_activities_per_bug_66811(): void
    {
        // Workstep COMPLETED ma SENZA actualStartDate, con activities che hanno
        // goodCycles > 0 → la stampa è effettivamente avvenuta.
        $svc = new PrinectJobsService($this->fakeApi([
            'act' => [
                '66811/WS1' => [
                    'activities' => [
                        ['name' => 'Avviamento', 'goodCycles' => 0, 'timeTypeName' => 'Avviamento'],
                        ['name' => 'Stampa', 'goodCycles' => 3200, 'timeTypeName' => 'Tempo di esecuzione'],
                    ],
                ],
            ],
        ]));

        $this->assertTrue($svc->stampaConfermata('66811', [
            'id' => 'WS1',
            'status' => 'COMPLETED',
            'actualStartDate' => null,
            'amountProduced' => 3200,
        ]));
    }

    public function test_stampa_non_confermata_se_no_activities_e_no_actual_start(): void
    {
        $svc = new PrinectJobsService($this->fakeApi([
            'act' => [
                '66811/WS1' => ['activities' => []],
            ],
        ]));

        $this->assertFalse($svc->stampaConfermata('66811', [
            'id' => 'WS1',
            'status' => 'COMPLETED',
            'actualStartDate' => null,
            'amountProduced' => 1000,
        ]));
    }

    public function test_stampa_non_confermata_se_status_non_completed_e_no_actual_start(): void
    {
        $svc = new PrinectJobsService($this->fakeApi());

        $this->assertFalse($svc->stampaConfermata('66811', [
            'id' => 'WS1',
            'status' => 'WAITING',
            'actualStartDate' => null,
            'amountProduced' => 0,
        ]));
    }
}
