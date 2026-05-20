<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MateriePrimeCarteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('materie_prime_carte')->delete();

        // === CARTULARIA — CARTONCINI STORA ENSO ===
        $cartularia = [
            // [tipo, nome, grammatura, eur_ton_alto, eur_ton_medio, eur_ton_basso]
            ['CKB',      'CKB Stora Enso',                '195 g/m²',     1375, 1385, 1405],
            ['CKB',      'CKB Stora Enso',                '220-415 g/m²', 1360, 1370, 1390],
            ['CKB',      'CKB NUDE Stora Enso',           '205 g/m²',     1435, 1445, 1465],
            ['CKB',      'CKB NUDE Stora Enso',           '230-345 g/m²', 1420, 1430, 1450],
            ['GC2',      'GC2 TAMBRITE New Stora Enso',   '200 g/m²',     1370, 1380, 1400],
            ['GC2',      'GC2 TAMBRITE New Stora Enso',   '210-220 g/m²', 1350, 1360, 1380],
            ['GC2',      'GC2 TAMBRITE New Stora Enso',   '230-325 g/m²', 1330, 1340, 1360],
            ['GC1',      'GC1 Performa White Stora Enso', '220 g/m²',     1385, 1395, 1415],
            ['GC1',      'GC1 Performa White Stora Enso', '240-400 g/m²', 1370, 1380, 1400],
            ['GC2',      'GC2 Performa Light Stora Enso', '220 g/m²',     1385, 1395, 1415],
            ['GC2',      'GC2 Performa Light Stora Enso', '240-320 g/m²', 1370, 1380, 1400],
            ['GC2',      'GC2 Performa Cream Stora Enso', '230-360 g/m²', 1340, 1350, 1370],
            ['GC2',      'GC2 Performa Nova HS Stora Enso','200 g/m²',    1185, 1195, 1215],
            ['GC2',      'GC2 Performa Nova HS Stora Enso','225-315 g/m²',1170, 1180, 1200],
            ['GC1',      'GC1 Performa Lumi Stora Enso',  '200 g/m²',     1215, 1225, 1245],
            ['GC1',      'GC1 Performa Lumi Stora Enso',  '230-310 g/m²', 1200, 1210, 1230],
            ['GC1',      'GC1 Performa Brilliance Stora Enso','180-200 g/m²',1400,1410,1430],
            ['GC Liner', 'GC Liner Performa Agile Stora Enso','190 g/m²', 1390, 1400, 1420],
            ['SBS',      'GZ Ensocoat Stora Enso',        '250-380 g/m²', 1670, 1680, 1700],
            ['SBS',      'GZ Ensocoat 2S Stora Enso',     '240-400 g/m²', 1700, 1710, 1730],
            ['UC2',      'UC2 Foodbox Stora Enso',        '233-350 g/m²', 1410, 1420, 1440],
            ['Kraft',    'B/Kraft Stora Enso',            '190-205 g/m²', 1100, 1110, 1130],
        ];
        foreach ($cartularia as $c) {
            DB::table('materie_prime_carte')->insert([
                'tipo_cartone'   => $c[0],
                'nome'           => $c[1],
                'grammatura'     => $c[2],
                'eur_ton_alto'   => $c[3],
                'eur_ton_medio'  => $c[4],
                'eur_ton_basso'  => $c[5],
                'eur_kg'         => round($c[3]/1000, 4),
                'fornitore'      => 'Cartularia S.p.A.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // === PATINATE LUCIDE/OPACHE + USO MANO ===
        $patinate = [
            // [nome, grammatura, formato, eur_kg]
            // PATINATE LUCIDE
            ['Patinata lucida',  '90 g/m²',  '64×88',  1.20],
            ['Patinata lucida',  '115 g/m²', '64×88',  1.20],
            ['Patinata lucida',  '130 g/m²', '64×88',  1.20],
            ['Patinata lucida',  '150 g/m²', '64×88',  1.20],
            ['Patinata lucida',  '170 g/m²', '70×100', 1.20],
            ['Patinata lucida',  '200 g/m²', '70×100', 1.20],
            ['Patinata lucida',  '250 g/m²', '70×100', 1.20],
            ['Patinata lucida',  '300 g/m²', '70×100', 1.20],
            ['Patinata lucida',  '350 g/m²', '70×100', 1.20],
            // PATINATE OPACHE
            ['Patinata opaca',   '90 g/m²',  '64×88',  1.20],
            ['Patinata opaca',   '115 g/m²', '64×88',  1.20],
            ['Patinata opaca',   '130 g/m²', '64×88',  1.20],
            ['Patinata opaca',   '150 g/m²', '64×88',  1.20],
            ['Patinata opaca',   '170 g/m²', '70×100', 1.20],
            ['Patinata opaca',   '200 g/m²', '70×100', 1.20],
            ['Patinata opaca',   '250 g/m²', '70×100', 1.20],
            ['Patinata opaca',   '300 g/m²', '70×100', 1.20],
            ['Patinata opaca',   '350 g/m²', '70×100', 1.20],
            // USO MANO
            ['Uso mano',         '80 g/m²',  '64×88',  1.20],
            ['Uso mano',         '90 g/m²',  '64×88',  1.20],
            ['Uso mano',         '100 g/m²', '64×88',  1.20],
            ['Uso mano',         '120 g/m²', '70×100', 1.20],
            ['Uso mano',         '140 g/m²', '70×100', 1.20],
            ['Uso mano',         '160 g/m²', '70×100', 1.20],
            ['Uso mano',         '170 g/m²', '70×100', 1.20],
            ['Uso mano',         '200 g/m²', '70×100', 1.20],
        ];
        foreach ($patinate as $p) {
            DB::table('materie_prime_carte')->insert([
                'tipo_cartone'   => null,
                'nome'           => $p[0],
                'grammatura'     => $p[1],
                'formato'        => $p[2],
                'eur_kg'         => $p[3],
                'eur_ton_alto'   => $p[3] * 1000,
                'fornitore'      => 'Vari',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $this->command->info('MateriePrimeCarteSeeder: inserite ' . (count($cartularia) + count($patinate)) . ' tipologie carta');
    }
}
