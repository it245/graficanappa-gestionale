<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $ultimoCodice = DB::table('operatori')->where('codice_operatore', 'like', 'OWN%')
            ->orderBy('codice_operatore', 'desc')
            ->value('codice_operatore');
        $numero = $ultimoCodice ? (int) substr($ultimoCodice, -3) + 1 : 1;

        DB::table('operatori')->insert([
            'nome' => 'Generoso',
            'cognome' => 'Nappa',
            'codice_operatore' => 'OWN' . str_pad($numero, 3, '0', STR_PAD_LEFT),
            'ruolo' => 'owner',
            'reparto_id' => null,
            'attivo' => 1,
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('operatori')
            ->where('nome', 'Generoso')
            ->where('cognome', 'Nappa')
            ->where('ruolo', 'owner')
            ->delete();
    }
};
