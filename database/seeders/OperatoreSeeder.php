<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Operatore;
use Illuminate\Support\DB;
use Illuminate\Support\Facades\Hash;

class OperatoreSeeder extends Seeder
{
    public function run()
    {
        $operatori = [
            ['reparto' => 'spedizione', 'nome' => 'FRANCESCO', 'cognome' => 'SCOTTI'],
            ['reparto' => 'spedizione', 'nome' => 'LORENZO', 'cognome' => 'CRISANTI'],
            ['reparto' => 'spedizione', 'nome' => 'GIANGIUSEPPE', 'cognome' => 'SCARANO'],
            ['reparto' => 'digitale', 'nome' => 'FRANCESCO', 'cognome' => 'VERDE'],
            ['reparto' => 'fustella', 'nome' => 'DOMENICO', 'cognome' => 'MARFELLA'],
            ['reparto' => 'fustella', 'nome' => 'PAOLO', 'cognome' => 'BORTONE'],
            ['reparto' => 'legatoria', 'nome' => 'LUIGI', 'cognome' => 'MENALE'],
            ['reparto' => 'piegaincolla', 'nome' => 'GIANNANTONIO', 'cognome' => 'TORROMACCO'],
            ['reparto' => 'piegaincolla', 'nome' => 'PASQUALE', 'cognome' => 'IULIANO'],
            ['reparto' => 'piegaincolla', 'nome' => 'CIRO', 'cognome' => 'RAO'],
            ['reparto' => 'plastificazione', 'nome' => 'LUCA', 'cognome' => 'SORBO'],
            ['reparto' => 'prestampa', 'nome' => 'ANTONIO', 'cognome' => 'CASTELLANO'],
            ['reparto' => 'prestampa', 'nome' => 'MIRKO', 'cognome' => "D'ORAZIO"],
            ['reparto' => 'prestampa', 'nome' => 'FRANCESCO', 'cognome' => 'FRANCESE'],
            ['reparto' => 'prestampa', 'nome' => 'MICHELE', 'cognome' => 'RUSSO'],
            ['reparto' => 'stampa a caldo', 'nome' => 'VINCENZO', 'cognome' => 'GARGIULO'],
            ['reparto' => 'stampa a caldo', 'nome' => 'DIEGO', 'cognome' => 'PAGANO'],
            ['reparto' => 'stampa offset', 'nome' => 'BENITO', 'cognome' => 'MENALE'],
            ['reparto' => 'stampa offset', 'nome' => 'LUIGI', 'cognome' => 'MARINO'],
            ['reparto' => 'stampa offset', 'nome' => 'COSIMO', 'cognome' => 'MORMILE'],
            ['reparto' => 'stampa offset', 'nome' => 'GIUSEPPE', 'cognome' => 'TORROMACCO'],
            ['reparto' => 'stampa offset', 'nome' => 'VINCENZO', 'cognome' => 'MARRONE'],
            ['reparto' => 'stampa offset', 'nome' => 'RAFFAELE', 'cognome' => 'BARBATO'],
            ['reparto' => 'stampa offset', 'nome' => 'ALESSANDRO', 'cognome' => 'ZAMPELLA'],
            ['reparto' => 'stampa offset', 'nome' => 'Christian', 'cognome' => 'Simonetti'],
            ['reparto' => 'legatoria', 'nome' => 'MARIO', 'cognome' => 'SANTORO'],
            ['reparto' => 'spedizione', 'nome' => 'FRANCESCO', 'cognome' => 'IULIANO'],
        ];

        $ultimoCodice = Operatore::orderBy('codice_operatore','desc')->value('codice_operatore');
        $numero = $ultimoCodice ? (int) substr($ultimoCodice, -3) : 0;

        foreach ($operatori as $op) {
            $numero++;
            $nome = ucfirst(strtolower($op['nome']));
             $cognome = ucfirst(strtolower($op['cognome']));
         $iniziali=strtoupper($nome[0].$cognome[0]);
         $codice= $iniziali.str_pad($numero,3,'0',STR_PAD_LEFT);
        

            Operatore::create([
                'nome' =>$nome,
                'cognome' =>$cognome,
                'codice_operatore' =>$codice,
                'ruolo' => 'operatore',
                'reparto' => $op['reparto'],
                'attivo' => 1,
                'password' => Hash::make('password123'),
            ]);
        }

        
        $numero++;

        // Owner globale con tutti i reparti
        Operatore::create([
            'nome' => 'Antonio',
            'cognome' => 'Nappa',
            'codice_operatore' => 'OWN'.str_pad($numero,3,'0',STR_PAD_LEFT),
            'ruolo' => 'owner',
            'reparto' => 'spedizione,digitale,fustella,legatoria,piegaincolla,plastificazione,prestampa,stampa a caldo,stampa offset,generico,finestre,esterno',
            'attivo' => 1,
            'password' => Hash::make('password123'),
        ]);
    }
}