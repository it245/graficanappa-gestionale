<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CostiMacchineSeeder extends Seeder
{
    public function run(): void
    {
        // Pulisci tabelle (idempotente)
        DB::table('costi_aggiuntivi')->delete();
        DB::table('costi_fasce_tiratura')->delete();
        DB::table('costi_avviamento')->delete();
        DB::table('macchine_costi')->delete();

        // === MACCHINE ===
        $macchine = [
            ['slug' => 'xl106',         'nome' => 'Heidelberg XL 106-5-LYY+1L UV',  'tipo' => 'stampa_offset',    'formato_max' => '750x1060', 'formato_min' => '35x50',  'velocita_max' => 18000],
            ['slug' => 'konica14000',   'nome' => 'Konica 14000 — Stampa Digitale', 'tipo' => 'stampa_digitale',  'formato_max' => '330x700',  'formato_min' => null,     'velocita_max' => 120],
            ['slug' => 'bobst_novacut', 'nome' => 'Bobst Novacut 106 — Fustellatura','tipo' => 'fustella',         'formato_max' => '750x1060', 'formato_min' => '33x48',  'velocita_max' => 7000],
            ['slug' => 'visionfold110', 'nome' => 'Bobst Visionfold 110 — Piega-incolla','tipo' => 'piega_incolla', 'formato_max' => '1100',     'formato_min' => null,     'velocita_max' => 40000],
            ['slug' => 'brausse105',    'nome' => 'Brausse 105 — Stampa a Caldo',  'tipo' => 'stampa_caldo',     'formato_max' => '750x1050', 'formato_min' => null,     'velocita_max' => 4500],
            ['slug' => 'heidelberg_5272','nome' => 'Heidelberg 52x72 — Fustella piccola','tipo' => 'fustella',    'formato_max' => '520x720',  'formato_min' => null,     'velocita_max' => null],
            ['slug' => 'finestratrice', 'nome' => 'Finestratrice H&S',             'tipo' => 'finestratura',     'formato_max' => null,       'formato_min' => null,     'velocita_max' => null],
            ['slug' => 'kresia',        'nome' => 'Kresia — Plastificazione + UV', 'tipo' => 'plastificazione',  'formato_max' => null,       'formato_min' => null,     'velocita_max' => null],
            ['slug' => 'legraf',        'nome' => 'Legraf — Legatoria',            'tipo' => 'legatoria',        'formato_max' => null,       'formato_min' => null,     'velocita_max' => null],
            ['slug' => 'legokart',      'nome' => 'Legokart — Piega esterna',      'tipo' => 'piega_esterna',    'formato_max' => null,       'formato_min' => null,     'velocita_max' => null],
            ['slug' => 'ctp_agfa',      'nome' => 'CTP Agfa — Lastre offset',      'tipo' => 'ctp',              'formato_max' => null,       'formato_min' => null,     'velocita_max' => null],
            ['slug' => 'sae_spotimage', 'nome' => 'SAE Spotimage — UV+Foil digitale','tipo' => 'uv_foil_digitale','formato_max' => null,       'formato_min' => null,     'velocita_max' => null],
        ];

        $ids = [];
        foreach ($macchine as $m) {
            $ids[$m['slug']] = DB::table('macchine_costi')->insertGetId(array_merge($m, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // === AVVIAMENTO ===
        // XL106
        $avvXl = [
            ['1/0 (1 colore)',            1, 40, 15, 50],
            ['2/0 (2 colori)',            2, 80, 30, 100],
            ['4/0 (quadricromia)',        4, 115, 45, 150],
            ['4/0 + vernice piena UV',    5, 130, 51, 170],
            ['5/0 (quadri + Pantone)',    5, 140, 54, 180],
            ['5/0 + vernice UV',          6, 140, 54, 180],
            ['4/0 + UV spot drip-off',    6, 125, 48, 160],
            ['5/0 + UV spot drip-off',    7, 140, 54, 180],
            ['6/0 + 2 verniciature',      8, 160, 60, 200],
            ['Volta (retro)',             null, 75, 30, 50],
        ];
        foreach ($avvXl as $a) {
            DB::table('costi_avviamento')->insert([
                'macchina_id' => $ids['xl106'], 'configurazione' => $a[0], 'gruppi_usati' => $a[1],
                'costo_avviamento' => $a[2], 'tempo_avviamento_min' => $a[3], 'fogli_avviamento' => $a[4],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Bobst Novacut
        $avvBobst = [
            ['Fustella semplice (1 posa)',     50, 30, 50],
            ['Fustella complessa (multi-posa)',100, 60, 100],
            ['Cambio fustella',                 50, 30, 100],
            ['Con sfridatura automatica',     150, 70, 150],
        ];
        foreach ($avvBobst as $a) {
            DB::table('costi_avviamento')->insert([
                'macchina_id' => $ids['bobst_novacut'], 'configurazione' => $a[0],
                'costo_avviamento' => $a[1], 'tempo_avviamento_min' => $a[2], 'fogli_avviamento' => $a[3],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Visionfold
        $avvVf = [
            ['Lineare (1 punto colla)',    50, 30],
            ['Crash-lock (fondo auto)',    80, 48],
            ['4 punti',                   100, 60],
            ['6 punti',                   150, 90],
            ['Con finestra',               60, 36],
        ];
        foreach ($avvVf as $a) {
            DB::table('costi_avviamento')->insert([
                'macchina_id' => $ids['visionfold110'], 'configurazione' => $a[0],
                'costo_avviamento' => $a[1], 'tempo_avviamento_min' => $a[2],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Brausse 105
        $avvBr = [
            ['Caldo area piccola (<100cm²)',    75,  30, 50],
            ['Caldo area media (100-500cm²)',   100, 40, 100],
            ['Caldo area grande (>500cm²)',     150, 60, 200],
            ['Caldo area xl (>1000cm²)',        200, 80, 400],
            ['Rilievo a secco',                 100, 40, 100],
            ['Debossing',                       100, 40, 100],
            ['Combinato (caldo + rilievo)',     200, 80, 400],
        ];
        foreach ($avvBr as $a) {
            DB::table('costi_avviamento')->insert([
                'macchina_id' => $ids['brausse105'], 'configurazione' => $a[0],
                'costo_avviamento' => $a[1], 'tempo_avviamento_min' => $a[2], 'costo_cliche' => $a[3],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // === FASCE TIRATURA ===
        // XL106: 4/0, 5/0, +UV, drip-off
        $fasceXl = [
            [1, 500,    [['4/0', 0.0230], ['5/0', 0.0242], ['+UV', 0.0254], ['drip-off', 0.0266]]],
            [501, 1000, [['4/0', 0.0220], ['5/0', 0.0231], ['+UV', 0.0243], ['drip-off', 0.0255]]],
            [1001, 2000,[['4/0', 0.0210], ['5/0', 0.0221], ['+UV', 0.0232], ['drip-off', 0.0243]]],
            [2001, 3000,[['4/0', 0.0200], ['5/0', 0.0210], ['+UV', 0.0221], ['drip-off', 0.0232]]],
            [3001, 5000,[['4/0', 0.0190], ['5/0', 0.0200], ['+UV', 0.0209], ['drip-off', 0.0220]]],
            [5001, 10000,[['4/0', 0.0180], ['5/0', 0.0189], ['+UV', 0.0198], ['drip-off', 0.0208]]],
            [10001, 20000,[['4/0', 0.0170], ['5/0', 0.0179], ['+UV', 0.0187], ['drip-off', 0.0197]]],
            [20001, 50000,[['4/0', 0.0160], ['5/0', 0.0168], ['+UV', 0.0176], ['drip-off', 0.0185]]],
            [50001, 100000,[['4/0', 0.0150], ['5/0', 0.0158], ['+UV', 0.0165], ['drip-off', 0.0174]]],
            [100001, null,[['4/0', 0.0130], ['5/0', 0.0150], ['+UV', 0.0160], ['drip-off', 0.0170]]],
        ];
        foreach ($fasceXl as $f) {
            foreach ($f[2] as $v) {
                DB::table('costi_fasce_tiratura')->insert([
                    'macchina_id' => $ids['xl106'], 'da_qta' => $f[0], 'a_qta' => $f[1],
                    'variante' => $v[0], 'udm' => 'foglio', 'costo' => $v[1],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Konica click
        $click = [
            ['CMYK', '330x700',  0.10],
            ['CMYK', 'SRA3',     0.05],
            ['CMYK', 'A3',       0.05],
            ['CMYK', 'A4',       0.04],
            ['BN',   '330x700',  0.004],
            ['BN',   'SRA3',     0.002],
            ['BN',   'A3',       0.002],
            ['BN',   'A4',       0.002],
            ['BIANCO','SRA3',    0.15],
        ];
        foreach ($click as $c) {
            DB::table('costi_fasce_tiratura')->insert([
                'macchina_id' => $ids['konica14000'], 'da_qta' => 1, 'a_qta' => null,
                'variante' => $c[0], 'udm' => 'click', 'costo' => $c[2], 'formato' => $c[1],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Bobst Novacut fasce
        $fbobst = [
            [1, 500,     0.10],
            [501, 1000,  0.07],
            [1001, 2000, 0.06],
            [2001, 3000, 0.04],
            [3001, 5000, 0.033],
            [5001, 10000,0.03],
            [10001, 20000, 0.029],
            [20001, 50000, 0.028],
            [50001, 100000, 0.025],
        ];
        foreach ($fbobst as $f) {
            DB::table('costi_fasce_tiratura')->insert([
                'macchina_id' => $ids['bobst_novacut'], 'da_qta' => $f[0], 'a_qta' => $f[1],
                'variante' => 'standard', 'udm' => 'colpo', 'costo' => $f[2],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Visionfold fasce
        $fvf = [
            [1, 1000,     [['lineare', 10.0], ['crash-lock', 30.0], ['4-6 punti', 50.0]]],
            [1001, 3000,  [['lineare', 9.5],  ['crash-lock', 28.5], ['4-6 punti', 47.5]]],
            [3001, 5000,  [['lineare', 9.0],  ['crash-lock', 27.0], ['4-6 punti', 45.0]]],
            [5001, 10000, [['lineare', 8.7],  ['crash-lock', 26.1], ['4-6 punti', 43.5]]],
            [10001, 20000,[['lineare', 8.5],  ['crash-lock', 25.5], ['4-6 punti', 42.5]]],
            [20001, 50000,[['lineare', 8.0],  ['crash-lock', 24.0], ['4-6 punti', 40.0]]],
            [50001, null, [['lineare', 7.5],  ['crash-lock', 22.5], ['4-6 punti', 37.5]]],
        ];
        foreach ($fvf as $f) {
            foreach ($f[2] as $v) {
                DB::table('costi_fasce_tiratura')->insert([
                    'macchina_id' => $ids['visionfold110'], 'da_qta' => $f[0], 'a_qta' => $f[1],
                    'variante' => $v[0], 'udm' => '1000pz', 'costo' => $v[1],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Brausse 105 fasce
        $fbr = [
            [1, 500,     0.20, 0.06, 0.20],
            [501, 1000,  0.18, 0.03, 0.20],
            [1001, 2000, 0.16, 0.03, 0.20],
            [2001, 3000, 0.14, 0.03, 0.20],
            [3001, 5000, 0.12, 0.03, 0.20],
            [5001, 10000,0.11, 0.03, 0.20],
            [10001, null,0.10, 0.03, 0.20],
        ];
        foreach ($fbr as $f) {
            DB::table('costi_fasce_tiratura')->insert(['macchina_id' => $ids['brausse105'], 'da_qta' => $f[0], 'a_qta' => $f[1], 'variante' => 'caldo',    'udm' => 'colpo', 'costo' => $f[2], 'created_at' => now(), 'updated_at' => now()]);
            DB::table('costi_fasce_tiratura')->insert(['macchina_id' => $ids['brausse105'], 'da_qta' => $f[0], 'a_qta' => $f[1], 'variante' => 'rilievo',  'udm' => 'colpo', 'costo' => $f[3], 'created_at' => now(), 'updated_at' => now()]);
            DB::table('costi_fasce_tiratura')->insert(['macchina_id' => $ids['brausse105'], 'da_qta' => $f[0], 'a_qta' => $f[1], 'variante' => 'foil',     'udm' => 'mq',    'costo' => $f[4], 'created_at' => now(), 'updated_at' => now()]);
        }

        // === COSTI AGGIUNTIVI ===
        $aggXl = [
            ['Cambio lastra (per colore)', 'cad',     11],
            ['Pantone — preparazione',     'cad',     20],
            ['Pantone — consumo',          'eur/kg',  10],
            ['Vernice UV piena',           'eur/kg',  10],
            ['Vernice UV spot',            'eur/kg',  18],
            ['Lavaggio macchina',          'cad',     10],
            ['Sovrappr. carta >350g',      'eur/foglio', 0.001],
            ['Sovrappr. formato <50×70',   'eur/foglio', 0.001],
        ];
        foreach ($aggXl as $a) {
            DB::table('costi_aggiuntivi')->insert([
                'macchina_id' => $ids['xl106'], 'voce' => $a[0], 'udm' => $a[1], 'costo' => $a[2],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $aggKon = [
            ['Setup / RIP',           'cad',         15],
            ['Carta speciale',        'eur/foglio',  0.01],
            ['Supporto plastico',     'eur/foglio',  0.03],
            ['Dato variabile',        'eur/campo',   0.002],
        ];
        foreach ($aggKon as $a) {
            DB::table('costi_aggiuntivi')->insert([
                'macchina_id' => $ids['konica14000'], 'voce' => $a[0], 'udm' => $a[1], 'costo' => $a[2],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->command->info('CostiMacchineSeeder: inserite ' . count($macchine) . ' macchine + relativi listini.');
    }
}
