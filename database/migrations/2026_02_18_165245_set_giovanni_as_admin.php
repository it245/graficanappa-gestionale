<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('operatori')
            ->where('nome', 'Giovanni')
            ->update([
                'ruolo' => 'admin',
                'codice_operatore' => 'ADM001',
                'password' => Hash::make('admin2026'),
            ]);
    }

    public function down(): void
    {
        DB::table('operatori')
            ->where('nome', 'Giovanni')
            ->where('ruolo', 'admin')
            ->update([
                'ruolo' => 'owner',
                'password' => null,
            ]);
    }
};
