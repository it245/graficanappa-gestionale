<?php

namespace App\Services;

use App\Models\Ordine;
use Illuminate\Support\Facades\DB;

class CostoConsuntivoService
{
    /**
     * Calcola tutte le voci di costo per una commessa terminata.
     * Ritorna array strutturato di voci [categoria => [voci...]].
     */
    public function calcola(string $commessa): array
    {
        $ordini = Ordine::where('commessa', $commessa)
            ->with('fasi.faseCatalogo.reparto')
            ->get();
        if ($ordini->isEmpty()) return [];

        $voci = [];

        // Aggrega fasi per macchina + flag esterno
        $perMacchina = $this->aggregaFasi($ordini);

        // Carta
        $voci = array_merge($voci, $this->calcolaCarta($ordini));

        // Per ogni macchina (interna o esterna)
        foreach ($perMacchina as $info) {
            if (!$info['slug']) continue;
            $voci = array_merge($voci, $this->calcolaMacchina($info, $ordini));
        }

        // Manodopera (ore reparto × tariffa CostoReparto)
        $voci = array_merge($voci, $this->calcolaManodopera($ordini));

        // Scarti (fogli persi × €/foglio carta)
        $voci = array_merge($voci, $this->calcolaScarti($ordini));

        // Carica override esistenti da DB e applica
        $override = DB::table('commessa_costi_voci')
            ->where('commessa', $commessa)
            ->where('override_manuale', true)
            ->get()
            ->keyBy('voce_chiave');

        foreach ($voci as &$v) {
            if (isset($override[$v['voce_chiave']])) {
                $o = $override[$v['voce_chiave']];
                $v['importo'] = (float) $o->importo;
                $v['qta'] = $o->qta !== null ? (float) $o->qta : $v['qta'];
                $v['prezzo_unit'] = $o->prezzo_unit !== null ? (float) $o->prezzo_unit : $v['prezzo_unit'];
                $v['override_manuale'] = true;
                $v['autore_override'] = $o->autore_override;
            } else {
                $v['override_manuale'] = false;
            }
        }

        return $voci;
    }

    /**
     * Salva snapshot voci in DB (per fastread + cronologia).
     */
    public function persisti(string $commessa): void
    {
        $voci = $this->calcola($commessa);

        // Cancella vecchie voci NON override (mantieni override manuali)
        DB::table('commessa_costi_voci')
            ->where('commessa', $commessa)
            ->where('override_manuale', false)
            ->delete();

        foreach ($voci as $v) {
            if ($v['override_manuale'] ?? false) continue; // override già in DB
            DB::table('commessa_costi_voci')->updateOrInsert(
                ['commessa' => $commessa, 'voce_chiave' => $v['voce_chiave']],
                [
                    'categoria'   => $v['categoria'],
                    'descrizione' => $v['descrizione'],
                    'qta'         => $v['qta'] ?? null,
                    'udm'         => $v['udm'] ?? null,
                    'prezzo_unit' => $v['prezzo_unit'] ?? null,
                    'importo'     => $v['importo'],
                    'override_manuale' => false,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }

    public function totale(array $voci): float
    {
        return (float) array_sum(array_column($voci, 'importo'));
    }

    public function totalePerCategoria(array $voci): array
    {
        $out = [];
        foreach ($voci as $v) {
            $cat = $v['categoria'];
            $out[$cat] = ($out[$cat] ?? 0) + $v['importo'];
        }
        return $out;
    }

    // ============ INTERNAL ============

    private function aggregaFasi($ordini): array
    {
        $per = [];
        foreach ($ordini as $ord) {
            foreach ($ord->fasi as $fase) {
                $esterno = (bool) ($fase->esterno ?? false)
                    || (bool) preg_match('/Inviato\s+a:/i', $fase->note ?? '');
                $slug = $fase->faseCatalogo->macchina_slug ?? null;
                $key = ($slug ?? 'unknown') . ':' . ($esterno ? 'ext' : 'int');
                $per[$key] ??= [
                    'slug' => $slug, 'esterno' => $esterno,
                    'qta_prod' => 0, 'fogli_buoni' => 0, 'fogli_scarto' => 0,
                    'scarti' => 0, 'inchiostro_g' => 0,
                    'fasi' => [], 'fornitori' => [],
                ];
                $per[$key]['qta_prod']     += (int) ($fase->qta_prod ?? 0);
                $per[$key]['fogli_buoni']  += (int) ($fase->fogli_buoni ?? 0);
                $per[$key]['fogli_scarto'] += (int) ($fase->fogli_scarto ?? 0);
                $per[$key]['scarti']       += (int) ($fase->scarti ?? 0);
                $per[$key]['inchiostro_g'] += (float) ($fase->inchiostro_g ?? 0);
                $per[$key]['fasi'][] = $fase;
                if ($esterno && preg_match('/Inviato\s+a:\s*(.+)/i', $fase->note ?? '', $m)) {
                    $per[$key]['fornitori'][] = trim($m[1]);
                }
            }
        }
        return $per;
    }

    private function calcolaCarta($ordini): array
    {
        // Identifica carta vera (ordine con cod_carta valido, non SEMILAV*).
        $byCarta = [];
        foreach ($ordini as $ord) {
            $carta = trim($ord->carta ?? '');
            $cod = trim($ord->cod_carta ?? '');
            if (!$carta) continue;
            // Skip semilavorato o carta = descrizione
            if (preg_match('/^SEMILAV/i', $cod)) continue;
            if (mb_stripos($carta, 'AST.') === 0 || mb_stripos($carta, 'CUBO') === 0
                || mb_stripos($carta, 'LIBRO') === 0 || mb_stripos($carta, 'COPERTINA') === 0
                || mb_stripos($carta, 'BLOCCO') === 0) continue;
            $byCarta[$carta] ??= ['qta_prev' => 0, 'cod' => $cod, 'um' => $ord->UM_carta ?? 'fg'];
            $byCarta[$carta]['qta_prev'] += (int) ($ord->qta_carta ?? 0);
        }

        // Fogli effettivamente STAMPATI (Prinect fogli_buoni + fogli_scarto = totale carta usata)
        $fogliReali = 0;
        foreach ($ordini as $ord) {
            foreach ($ord->fasi as $fase) {
                $repartoLower = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                if (!str_contains($repartoLower, 'stampa offset') && !str_contains($repartoLower, 'digitale')) continue;
                $fogli = (int) ($fase->fogli_buoni ?? 0) + (int) ($fase->fogli_scarto ?? 0);
                if ($fogli === 0) $fogli = (int) ($fase->qta_prod ?? 0);
                if ($fogli > $fogliReali) $fogliReali = $fogli;
            }
        }

        // Usa fogli reali se > 0, altrimenti preventivo
        foreach ($byCarta as $nome => &$info) {
            $info['qta'] = $fogliReali > 0 ? $fogliReali : $info['qta_prev'];
        }
        unset($info);

        $voci = [];
        $i = 0;
        foreach ($byCarta as $nome => $info) {
            $i++;
            $info['qta'] = $info['qta']; // qta_carta preventivo (fogli)
            // Parse: cerca grammatura + formato
            $gramm = preg_match('/(\d{2,4})\s*(?:g\/?m²?|gr?)/i', $nome, $mG) ? (int) $mG[1] : null;
            $formato = preg_match('/(\d{2,3})\s*[x×]\s*(\d{2,3})/i', $nome, $mF) ? ($mF[1] . 'x' . $mF[2]) : null;

            // Lookup listino
            $eurKg = null;
            $listinoMatch = null;
            if ($gramm) {
                $listinoMatch = DB::table('materie_prime_carte')
                    ->where('grammatura', 'LIKE', "%{$gramm}%")
                    ->orderByDesc('eur_kg')
                    ->first();
                if ($listinoMatch) $eurKg = (float) $listinoMatch->eur_kg;
            }

            // Calcola peso foglio (cm² × g/m²) → kg
            $pesoKg = null;
            if ($gramm && $formato) {
                [$l1, $l2] = array_map('intval', explode('x', $formato));
                $pesoKg = ($l1 / 100) * ($l2 / 100) * ($gramm / 1000);
            }

            $costoFoglio = ($pesoKg && $eurKg) ? round($pesoKg * $eurKg, 4) : 0;
            $totale = round($costoFoglio * $info['qta'], 2);

            $voci[] = [
                'categoria'   => 'carta',
                'voce_chiave' => "carta.{$i}",
                'descrizione' => "Carta: {$nome}" . ($listinoMatch ? " (listino: {$listinoMatch->nome})" : ' ⚠️ non in listino'),
                'qta'         => $info['qta'],
                'udm'         => 'fg',
                'prezzo_unit' => $costoFoglio,
                'importo'     => $totale,
            ];
        }
        return $voci;
    }

    private function calcolaMacchina(array $info, $ordini): array
    {
        $slug = $info['slug'];
        $voci = [];

        if ($info['esterno']) {
            // Lavorazione esterna — match fornitore in catalogo
            $fornitori = array_unique($info['fornitori']);
            $fornitorePrincipale = $fornitori[0] ?? '';
            $fornitoreSlug = $this->matchFornitoreEsterno($fornitorePrincipale);
            $fornitoreNome = '';
            if ($fornitoreSlug) {
                $row = DB::table('macchine_costi')->where('slug', $fornitoreSlug)->first();
                $fornitoreNome = $row->nome ?? $fornitoreSlug;
            }
            $voci[] = [
                'categoria'   => 'esterno',
                'voce_chiave' => "ext.{$slug}." . md5($fornitorePrincipale),
                'descrizione' => "Esterno {$slug}"
                    . ($fornitorePrincipale ? " → {$fornitorePrincipale}" : '')
                    . ($fornitoreSlug ? " (listino: {$fornitoreNome})" : ' ⚠️ fornitore non riconosciuto'),
                'qta'         => $info['qta_prod'],
                'udm'         => 'pz',
                'prezzo_unit' => null,
                'importo'     => 0,
            ];
            return $voci;
        }

        $macchina = DB::table('macchine_costi')->where('slug', $slug)->first();
        if (!$macchina) return $voci;

        // Quantità di riferimento: per stampa offset usa fogli_buoni, altrimenti qta_prod
        $qta = $slug === 'xl106' ? ($info['fogli_buoni'] ?: $info['qta_prod']) : $info['qta_prod'];

        // Tariffa fascia
        $variante = $this->scegliVariante($slug, $info, $ordini);
        $tariffa = DB::table('costi_fasce_tiratura')
            ->where('macchina_id', $macchina->id)
            ->where('variante', $variante)
            ->where('da_qta', '<=', $qta)
            ->where(function ($q) use ($qta) {
                $q->where('a_qta', '>=', $qta)->orWhereNull('a_qta');
            })
            ->orderByDesc('da_qta')
            ->first();

        if ($tariffa) {
            // udm: foglio, colpo, click, 1000pz, mq
            $importo = match ($tariffa->udm) {
                '1000pz' => ($qta / 1000) * $tariffa->costo,
                default  => $qta * $tariffa->costo,
            };
            $voci[] = [
                'categoria'   => $macchina->tipo,
                'voce_chiave' => "{$slug}.lavorazione",
                'descrizione' => "{$macchina->nome} — {$variante} (fascia {$tariffa->da_qta}-" . ($tariffa->a_qta ?? '∞') . ')',
                'qta'         => $qta,
                'udm'         => $tariffa->udm,
                'prezzo_unit' => (float) $tariffa->costo,
                'importo'     => round($importo, 2),
            ];
        }

        // Inchiostro (solo XL106)
        if ($slug === 'xl106' && $info['inchiostro_g'] > 0) {
            $voci[] = [
                'categoria'   => 'stampa_offset',
                'voce_chiave' => 'xl106.inchiostro',
                'descrizione' => 'Inchiostro (€10/kg)',
                'qta'         => round($info['inchiostro_g'] / 1000, 3),
                'udm'         => 'kg',
                'prezzo_unit' => 10,
                'importo'     => round(($info['inchiostro_g'] / 1000) * 10, 2),
            ];
        }

        // Avviamento: auto-detect configurazione dal contesto
        $configurazione = $this->scegliConfigurazioneAvviamento($slug, $info);
        $avv = DB::table('costi_avviamento')
            ->where('macchina_id', $macchina->id)
            ->where('configurazione', $configurazione)
            ->first();
        if (!$avv) {
            $avv = DB::table('costi_avviamento')
                ->where('macchina_id', $macchina->id)
                ->orderBy('id')
                ->first();
        }
        if ($avv) {
            $voci[] = [
                'categoria'   => $macchina->tipo,
                'voce_chiave' => "{$slug}.avviamento",
                'descrizione' => "Avviamento {$macchina->nome} ({$avv->configurazione})",
                'qta'         => 1,
                'udm'         => 'cad',
                'prezzo_unit' => (float) $avv->costo_avviamento,
                'importo'     => (float) $avv->costo_avviamento,
            ];
        }

        return $voci;
    }

    /**
     * Match nome fornitore (es "LEGOKART S.A.S.", "KRESIA SRL", "LEGATORIA SALVATORE TONTI")
     * con slug catalogo fornitori esterni.
     */
    private function matchFornitoreEsterno(string $nomeFornitore): ?string
    {
        $n = mb_strtolower($nomeFornitore);
        $map = [
            'legokart'                      => 'legokart',
            'kresia'                        => 'kresia',
            'legraf'                        => 'legraf',
            'tonti'                         => 'legraf',           // Legatoria Salvatore Tonti = legatoria esterna
            'salvatore tonti'               => 'legraf',
            'soluzioni imballaggi shopper'  => 'soluzioni_imballaggi_shopper',
            'soluzioni imballaggi'          => 'soluzioni_imballaggi_acc',
            'soluzioni'                     => 'soluzioni_imballaggi_acc',
            'neoprint'                      => 'neoprint',
            'sae'                           => 'sae_spotimage',
            'spotimage'                     => 'sae_spotimage',
        ];
        foreach ($map as $key => $slug) {
            if (str_contains($n, $key)) return $slug;
        }
        return null;
    }

    private function calcolaManodopera($ordini): array
    {
        $voci = [];
        $perReparto = [];
        foreach ($ordini as $ord) {
            foreach ($ord->fasi as $fase) {
                $rep = $fase->faseCatalogo->reparto ?? null;
                if (!$rep) continue;
                $repId = $rep->id;
                $perReparto[$repId] ??= ['nome' => $rep->nome, 'sec' => 0];
                $sec = (int) (($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0));
                if ($sec === 0) {
                    foreach ($fase->operatori as $op) {
                        if (!$op->pivot->data_inizio || !$op->pivot->data_fine) continue;
                        $pausa = (int) ($op->pivot->secondi_pausa ?? 0);
                        $diff = \Carbon\Carbon::parse($op->pivot->data_fine)->getTimestamp()
                              - \Carbon\Carbon::parse($op->pivot->data_inizio)->getTimestamp();
                        $sec += max($diff - $pausa, 0);
                    }
                }
                $perReparto[$repId]['sec'] += $sec;
            }
        }
        foreach ($perReparto as $repId => $info) {
            if ($info['sec'] <= 0) continue;
            $ore = round($info['sec'] / 3600, 2);
            $tariffa = \App\Models\CostoReparto::tariffaAllaData($repId, now()->toDateString());
            if ($tariffa <= 0) continue; // skip se reparto non ha tariffa configurata
            $voci[] = [
                'categoria'   => 'manodopera',
                'voce_chiave' => "manodopera.r{$repId}",
                'descrizione' => "Manodopera {$info['nome']} ({$ore}h × €{$tariffa}/h)",
                'qta'         => $ore,
                'udm'         => 'h',
                'prezzo_unit' => $tariffa,
                'importo'     => round($ore * $tariffa, 2),
            ];
        }
        return $voci;
    }

    private function calcolaScarti($ordini): array
    {
        $voci = [];
        $totScarti = 0;
        foreach ($ordini as $ord) {
            foreach ($ord->fasi as $fase) {
                $totScarti += (int) ($fase->fogli_scarto ?? 0);
                $totScarti += (int) ($fase->scarti ?? 0);
            }
        }
        if ($totScarti <= 0) return $voci;

        // Stima €/foglio dalla carta principale ordine
        $primoOrdine = $ordini->first();
        $eurFoglio = 0;
        $cartaInfo = trim($primoOrdine->carta ?? '');
        if ($cartaInfo) {
            $gramm = preg_match('/(\d{2,4})\s*(?:g\/?m²?|gr?)/i', $cartaInfo, $mG) ? (int) $mG[1] : null;
            $formato = preg_match('/(\d{2,3})\s*[x×]\s*(\d{2,3})/i', $cartaInfo, $mF) ? ($mF[1] . 'x' . $mF[2]) : null;
            if ($gramm && $formato) {
                $listino = DB::table('materie_prime_carte')->where('grammatura', 'LIKE', "%{$gramm}%")->first();
                if ($listino) {
                    [$l1, $l2] = array_map('intval', explode('x', $formato));
                    $peso = ($l1 / 100) * ($l2 / 100) * ($gramm / 1000);
                    $eurFoglio = round($peso * (float) $listino->eur_kg, 4);
                }
            }
        }
        $importo = round($totScarti * $eurFoglio, 2);
        $voci[] = [
            'categoria'   => 'scarti',
            'voce_chiave' => 'scarti.carta',
            'descrizione' => "Scarti carta ({$totScarti} fogli × €{$eurFoglio}/fg)",
            'qta'         => $totScarti,
            'udm'         => 'fg',
            'prezzo_unit' => $eurFoglio,
            'importo'     => $importo,
        ];
        return $voci;
    }

    /**
     * Auto-detect configurazione avviamento dalla note delle fasi ([COL: 4C + DRIP OFF]).
     */
    private function scegliConfigurazioneAvviamento(string $slug, array $info): string
    {
        if ($slug !== 'xl106') {
            return match ($slug) {
                'bobst_novacut'   => 'Fustella complessa (multi-posa)',
                'visionfold110'   => 'Crash-lock (fondo auto)',
                'brausse105'      => 'Caldo area media (100-500cm²)',
                default           => '',
            };
        }

        // XL106: parsing [COL: ...] note
        $notes = '';
        foreach ($info['fasi'] ?? [] as $f) $notes .= ' ' . ($f->note ?? '');
        $notes = mb_strtoupper($notes);

        $haDrip = preg_match('/DRIP\s*OFF/i', $notes);
        $haUv   = preg_match('/\bUV\b/i', $notes);
        $haPant = preg_match('/PANTONE|\d\s*COLORI\s*\+\s*ORO/i', $notes);

        // Numero colori
        $colori = 4; // default
        if (preg_match('/(\d)\s*[CcF]/', $notes, $m)) $colori = (int) $m[1];
        if (preg_match('/(\d)\s*COLORI/i', $notes, $m)) $colori = (int) $m[1];

        if ($haDrip) {
            return $colori >= 5 ? '5/0 + UV spot drip-off' : '4/0 + UV spot drip-off';
        }
        if ($haUv) {
            return $colori >= 5 ? '5/0 + vernice UV' : '4/0 + vernice piena UV';
        }
        return match (true) {
            $colori >= 5 || $haPant => '5/0 (quadri + Pantone)',
            $colori === 4           => '4/0 (quadricromia)',
            $colori === 2           => '2/0 (2 colori)',
            $colori === 1           => '1/0 (1 colore)',
            default                 => '4/0 (quadricromia)',
        };
    }

    private function scegliVariante(string $slug, array $info, $ordini): string
    {
        if ($slug === 'xl106') {
            $notes = '';
            foreach ($info['fasi'] ?? [] as $f) $notes .= ' ' . ($f->note ?? '');
            $notes = mb_strtoupper($notes);
            if (preg_match('/DRIP\s*OFF/i', $notes)) return 'drip-off';
            if (preg_match('/\bUV\b/i', $notes))    return '+UV';
            if (preg_match('/PANTONE|\d\s*COLORI\s*\+/i', $notes)) return '5/0';
            return '4/0';
        }
        if ($slug === 'visionfold110') {
            $notes = '';
            foreach ($info['fasi'] ?? [] as $f) $notes .= ' ' . ($f->note ?? '');
            if (preg_match('/CRASH/i', $notes))    return 'crash-lock';
            if (preg_match('/4\s*PUNTI|6\s*PUNTI/i', $notes)) return '4-6 punti';
            return 'crash-lock'; // default più frequente per astucci
        }
        return match ($slug) {
            'konica14000'   => 'CMYK',
            'bobst_novacut' => 'standard',
            'brausse105'    => 'caldo',
            default         => 'standard',
        };
    }
}
