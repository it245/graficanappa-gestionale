<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PAUSE commessa 0067387-26 ===\n";
$pause = DB::table('pausa_operatores')
    ->where('ordine_id', function($q) {
        $q->select('id')->from('ordini')->where('commessa', '0067387-26')->limit(1);
    })
    ->orWhereIn('ordine_id', function($q) {
        $q->select('id')->from('ordini')->where('commessa', '0067387-26');
    })
    ->orderBy('data_ora')
    ->get();

if ($pause->isEmpty()) {
    echo "  Nessuna pausa registrata.\n";
} else {
    foreach ($pause as $p) {
        $stato = $p->fine ? '✅ chiusa' : '⚠️ APERTA';
        $durata = '';
        if ($p->fine) {
            $sec = strtotime($p->fine) - strtotime($p->data_ora);
            $durata = sprintf(' (%dm %ds)', intdiv($sec, 60), $sec % 60);
        } else {
            $sec = time() - strtotime($p->data_ora);
            $durata = sprintf(' (dura da %dh %dm)', intdiv($sec, 3600), intdiv($sec % 3600, 60));
        }
        echo "  id={$p->id} | ordine_id={$p->ordine_id} | fase={$p->fase} | inizio={$p->data_ora} | fine=" . ($p->fine ?? 'NULL') . " | {$stato}{$durata}\n";
    }
}
