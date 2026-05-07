<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Services;

use App\Models\Reparto;
use App\Modules\Reparti\Enums\CodiceReparto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service applicativo: query e lookup sui reparti.
 *
 * Cache "reparti_attivi" 1h per evitare round-trip DB ad ogni chiamata
 * (dashboard owner / operatore caricano la lista a OGNI richiesta).
 *
 * NB: la cache va invalidata manualmente dopo seeder o modifiche
 * tabella reparti — vedi {@see clearCache()}.
 */
final class RepartoService
{
    private const CACHE_KEY = 'reparti_attivi';
    private const CACHE_TTL = 3600; // 1 ora

    /**
     * Tutti i reparti Eloquent, cached.
     *
     * @return Collection<int, Reparto>
     */
    public function tutti(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function () {
            return Reparto::query()->orderBy('id')->get();
        });
    }

    /**
     * Reparto per slug (nome DB). Null se non trovato.
     */
    public function bySlug(string $slug): ?Reparto
    {
        return $this->tutti()->firstWhere('nome', $slug);
    }

    /**
     * Reparto per ID. Null se non trovato.
     */
    public function byId(int $id): ?Reparto
    {
        return $this->tutti()->firstWhere('id', $id);
    }

    /**
     * Risolve {@see CodiceReparto} → modello Eloquent (via id()).
     */
    public function byCodice(CodiceReparto $codice): ?Reparto
    {
        return $this->byId($codice->id());
    }

    /**
     * Lookup ID intero → CodiceReparto enum.
     */
    public function codiceFromId(int $id): ?CodiceReparto
    {
        return CodiceReparto::fromId($id);
    }

    /**
     * Invalida la cache. Chiamare dopo seeder o modifiche manuali.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Mappa codice fase Onda → nome reparto MES (slug).
     *
     * Migrata da {@see \App\Services\OndaSyncService::getMappaReparti()} per
     * Strangler Fig. La regola pura {@see \App\Modules\Reparti\Rules\AssegnazioneFaseReparto}
     * collassa pseudo-reparti ("fustella piana", "tagliacarte", "finitura digitale")
     * sui canonical CodiceReparto enum: questo metodo invece preserva i nomi
     * granulari originali perchè la logica di dedup in OndaSync usa le stringhe
     * specifiche per applicare regole diverse (1 sola fustella per commessa,
     * tagliacarte per ordine, ecc.) — non possiamo riconciliarli senza
     * cambiare i numeri di output del sync.
     *
     * @return array<string, string>
     */
    public function mappaSlugToId(): array
    {
        return [
            'accopp+fust' => 'esterno',
            'ACCOPPIATURA.FOG.33.48INT' => 'esterno',
            'ACCOPPIATURA.FOGLI' => 'esterno',
            'Allest.Manuale' => 'legatoria',
            'Allest.Manuale0,2' => 'legatoria',
            'ALLEST.SHOPPER' => 'legatoria',
            'ALLEST.SHOPPER024' => 'legatoria',
            'ALLEST.SHOPPER030' => 'legatoria',
            'ALLESTIMENTO.CALENDARI' => 'legatoria',
            'ALLESTIMENTO.ESPOSITORI' => 'esterno',
            'CARTONATO.GEN' => 'esterno',
            'APPL.BIADESIVO30' => 'esterno',
            'appl.laccetto' => 'esterno',
            'ARROT2ANGOLI' => 'esterno',
            'ARROT4ANGOLI' => 'esterno',
            'AVVIAMENTISTAMPA.EST1.1' => 'esterno',
            'blocchi.manuale' => 'esterno',
            'BROSSCOPBANDELLAEST' => 'esterno',
            'BROSSCOPEST' => 'esterno',
            'BROSSFILOREFE/A4EST' => 'esterno',
            'BROSSFILOREFE/A5EST' => 'esterno',
            'BRT1' => 'spedizione',
            'brt1' => 'spedizione',
            'CORDONATURAPETRATTO' => 'legatoria',
            'DEKIA-Difficile' => 'legatoria',
            'FIN01' => 'finestratura',
            'FIN03' => 'finestratura',
            'FIN04' => 'finestratura',
            'FOIL.MGI.30M' => 'finitura digitale',
            'FOILMGI' => 'finitura digitale',
            'FUST.STARPACK.74X104' => 'esterno',
            'FUSTBIML75X106' => 'fustella piana',
            'FUSTbIML75X106' => 'fustella piana',
            'FUSTBOBST75X106' => 'fustella piana',
            'FUSTBOBSTRILIEVI' => 'fustella piana',
            'FUSTSTELG33.44' => 'fustella cilindrica',
            'FUSTSTELP25.35' => 'fustella cilindrica',
            'FUSTIML75X106' => 'fustella piana',
            'FUSTELLATURA72X51' => 'fustella',
            'FINESTRATURA.INT' => 'finestratura',
            'INCOLLAGGIO.PATTINA' => 'legatoria',
            'INCOLLAGGIOBLOCCHI' => 'legatoria',
            'LAVGEN' => 'legatoria',
            'NUM.PROGR.' => 'legatoria',
            'NUM33.44' => 'legatoria',
            'PERF.BUC' => 'legatoria',
            'PI01' => 'piegaincolla',
            'PI02' => 'piegaincolla',
            'PI03' => 'piegaincolla',
            'PIEGA2ANTECORDONE' => 'legatoria',
            'PIEGA2ANTESINGOLO' => 'legatoria',
            'PIEGA3ANTESINGOLO' => 'legatoria',
            'PIEGA8ANTESINGOLO' => 'legatoria',
            'PIEGA8TTAVO' => 'legatoria',
            'PIEGAMANUALE' => 'legatoria',
            'PLALUX1LATO' => 'plastificazione',
            'PLALUXBV' => 'plastificazione',
            'PLAOPA1LATO' => 'plastificazione',
            'PLAOPABV' => 'plastificazione',
            'PLAPOLIESARG1LATO' => 'plastificazione',
            'PLASAB1LATO' => 'plastificazione',
            'PLASABBIA1LATO' => 'plastificazione',
            'PLASOFTBV' => 'plastificazione',
            'PLASOFTBVEST' => 'plastificazione',
            'PLASOFTTOUCH1' => 'plastificazione',
            'PUNTOMETALLICO' => 'legatoria',
            'PUNTOMETALLICOEST' => 'legatoria',
            'PUNTOMETALLICOESTCOPERT.' => 'legatoria',
            'PUNTOMETAMANUALE' => 'legatoria',
            'RILIEVOASECCOJOH' => 'fustella piana',
            'SFUST' => 'legatoria',
            'SFUST.IML.FUSTELLATO' => 'legatoria',
            'SPIRBLOCCOLIBROA3' => 'legatoria',
            'SPIRBLOCCOLIBROA4' => 'legatoria',
            'SPIRBLOCCOLIBROA5' => 'legatoria',
            'STAMPA' => 'stampa offset',
            'STAMPA.OFFSET11.EST' => 'esterno',
            'STAMPABUSTE.EST' => 'esterno',
            'STAMPACALDOJOH' => 'stampa a caldo',
            'STAMPACALDOJOH0,1' => 'stampa a caldo',
            'STAMPAINDIGO' => 'digitale',
            'STAMPAINDIGOBN' => 'digitale',
            'STAMPAXL106' => 'stampa offset',
            'STAMPAXL106.1' => 'stampa offset',
            'STAMPAXL106.2' => 'stampa offset',
            'STAMPAXL106.3' => 'stampa offset',
            'STAMPAXL106.4' => 'stampa offset',
            'STAMPAXL106.5' => 'stampa offset',
            'STAMPAXL106.6' => 'stampa offset',
            'STAMPAXL106.7' => 'stampa offset',
            'TAGLIACARTE' => 'tagliacarte',
            'TAGLIACARTE.IML' => 'tagliacarte',
            'TAGLIOINDIGO' => 'tagliacarte',
            'UVSERIGRAFICOEST' => 'esterno',
            'UVSPOT.MGI.30M' => 'finitura digitale',
            'UVSPOT.MGI.30M.10' => 'finitura digitale',
            'UVSPOT.MGI.9M' => 'finitura digitale',
            'UVSPOTEST' => 'esterno',
            'UVSPOTSPESSEST' => 'esterno',
            'ZUND' => 'finitura digitale',
            'APPL.CORDONCINO0,035' => 'legatoria',
            '4graph' => 'esterno',
            'stampalaminaoro' => 'stampa a caldo',
            'STAMPALAMINAORO' => 'stampa a caldo',
            'ALL.COFANETTO.ISMAsrl' => 'esterno',
            'PMDUPLO36COP' => 'legatoria',
            'FINESTRATURA.MANUALE' => 'finestratura',
            'STAMPACALDOJOHEST' => 'esterno',
            'BROSSFRESATA/A5EST' => 'esterno',
            'PIEGA6ANTESINGOLO' => 'legatoria',
            'PIEGA4ANTESINGOLO' => 'legatoria',
            'PMDUPLO40AUTO' => 'legatoria',
            'BROSSPUR' => 'legatoria',

            // Fasi con "est" nel nome o prefisso "EXT" → esterno
            'est STAMPACALDOJOH' => 'esterno',
            'est FUSTSTELG33.44' => 'esterno',
            'est FUSTBOBST75X106' => 'esterno',
            'STAMPA.ESTERNA' => 'esterno',
            'EXTALL.COFANETTO.LEGOKART' => 'esterno',
            'EXTAllest.Manuale' => 'esterno',
            'EXTALLEST.SHOPPER' => 'esterno',
            'EXTALLESTIMENTO.ESPOSITOR' => 'esterno',
            'EXTAPPL.CORDONCINO0,035' => 'esterno',
            'EXTAVVIAMENTISTAMPA.EST1.' => 'esterno',
            'EXTBROSSCOPEST' => 'esterno',
            'EXTBROSSFILOREFE/A4EST' => 'esterno',
            'EXTBROSSFILOREFE/A5EST' => 'esterno',
            'EXTBROSSFRESATA/A4EST' => 'esterno',
            'EXTBROSSFRESATA/A5EST' => 'esterno',
            'EXTCARTONATO' => 'esterno',
            'EXTCARTONATO.GEN' => 'esterno',
            'EXTFUSTELLATURA72X51' => 'esterno',
            'EXTPUNTOMETALLICOEST' => 'esterno',
            'EXTSTAMPA.OFFSET11.EST' => 'esterno',
            'EXTSTAMPABUSTE.EST' => 'esterno',
            'EXTSTAMPASECCO' => 'esterno',
            'EXTUVSPOTEST' => 'esterno',
            'EXTUVSPOTSPESSEST' => 'esterno',

            // Altre fasi nuove
            'DEKIA-semplice' => 'legatoria',
            'STAMPASECCO' => 'fustella',
            'STAMPACALDO04' => 'stampa a caldo',
            'STAMPACALDOBR' => 'stampa a caldo',
            'STAMPAINDIGOBIANCO' => 'digitale',
        ];
    }

    /**
     * Mappa codice fase Onda → tipo (multifase / monofase / max 2 fasi).
     *
     * Migrata da {@see \App\Services\OndaSyncService::getTipoReparto()}.
     *
     * @return array<string, string>
     */
    public function tipoFromCodice(): array
    {
        return [
            // multifase
            'accopp+fust' => 'multifase',
            'FIN01' => 'multifase',
            'FIN03' => 'multifase',
            'FIN04' => 'multifase',
            'PI01' => 'multifase',
            'PI02' => 'multifase',
            'PI03' => 'multifase',
            'SFUST' => 'multifase',
            'SFUST.IML.FUSTELLATO' => 'multifase',
            // monofase
            'ACCOPPIATURA.FOG.33.48INT' => 'monofase',
            'ACCOPPIATURA.FOGLI' => 'monofase',
            'Allest.Manuale' => 'monofase',
            'ALLEST.SHOPPER' => 'monofase',
            'ALLEST.SHOPPER030' => 'monofase',
            'APPL.BIADESIVO30' => 'monofase',
            'appl.laccetto' => 'monofase',
            'ARROT2ANGOLI' => 'monofase',
            'ARROT4ANGOLI' => 'monofase',
            'AVVIAMENTISTAMPA.EST1.1' => 'monofase',
            'blocchi.manuale' => 'monofase',
            'BROSSCOPBANDELLAEST' => 'monofase',
            'BROSSCOPEST' => 'monofase',
            'BROSSFILOREFE/A4EST' => 'monofase',
            'BROSSFILOREFE/A5EST' => 'monofase',
            'BRT1' => 'monofase',
            'brt1' => 'monofase',
            'CARTONATO.GEN' => 'monofase',
            'CORDONATURAPETRATTO' => 'monofase',
            'DEKIA-Difficile' => 'monofase',
            'FOIL.MGI.30M' => 'monofase',
            'FOILMGI' => 'monofase',
            'FUST.STARPACK.74X104' => 'monofase',
            'FUSTBIML75X106' => 'monofase',
            'FUSTbIML75X106' => 'monofase',
            'FUSTBOBST75X106' => 'monofase',
            'FUSTBOBSTRILIEVI' => 'monofase',
            'FUSTSTELG33.44' => 'monofase',
            'FUSTSTELP25.35' => 'monofase',
            'INCOLLAGGIO.PATTINA' => 'monofase',
            'INCOLLAGGIOBLOCCHI' => 'monofase',
            'LAVGEN' => 'monofase',
            'NUM.PROGR.' => 'monofase',
            'NUM33.44' => 'monofase',
            'PERF.BUC' => 'monofase',
            'PIEGA2ANTECORDONE' => 'monofase',
            'PIEGA2ANTESINGOLO' => 'monofase',
            'PIEGA3ANTESINGOLO' => 'monofase',
            'PIEGA8ANTESINGOLO' => 'monofase',
            'PIEGA8TTAVO' => 'monofase',
            'PIEGAMANUALE' => 'monofase',
            'PLALUX1LATO' => 'monofase',
            'PLALUXBV' => 'monofase',
            'PLAOPA1LATO' => 'monofase',
            'PLAOPABV' => 'monofase',
            'PLAPOLIESARG1LATO' => 'monofase',
            'PLASAB1LATO' => 'monofase',
            'PLASABBIA1LATO' => 'monofase',
            'PLASOFTBV' => 'monofase',
            'PLASOFTBVEST' => 'monofase',
            'PLASOFTTOUCH1' => 'monofase',
            'PUNTOMETALLICO' => 'monofase',
            'PUNTOMETALLICOEST' => 'monofase',
            'PUNTOMETALLICOESTCOPERT.' => 'monofase',
            'PUNTOMETAMANUALE' => 'monofase',
            'RILIEVOASECCOJOH' => 'monofase',
            'SPIRBLOCCOLIBROA3' => 'monofase',
            'SPIRBLOCCOLIBROA4' => 'monofase',
            'SPIRBLOCCOLIBROA5' => 'monofase',
            'STAMPA' => 'monofase',
            'STAMPA.OFFSET11.EST' => 'monofase',
            'STAMPABUSTE.EST' => 'monofase',
            'STAMPACALDOJOH' => 'monofase',
            'STAMPACALDOJOH0,1' => 'monofase',
            'UVSERIGRAFICOEST' => 'monofase',
            'UVSPOT.MGI.30M' => 'monofase',
            'UVSPOT.MGI.9M' => 'monofase',
            'UVSPOTEST' => 'monofase',
            'UVSPOTSPESSEST' => 'monofase',
            'ZUND' => 'monofase',
            'APPL.CORDONCINO0,035' => 'monofase',
            '4graph' => 'monofase',
            'stampalaminaoro' => 'monofase',
            'STAMPALAMINAORO' => 'monofase',
            'ALL.COFANETTO.ISMAsrl' => 'monofase',
            'PMDUPLO36COP' => 'monofase',
            'FINESTRATURA.MANUALE' => 'monofase',
            'STAMPACALDOJOHEST' => 'monofase',
            'BROSSFRESATA/A5EST' => 'monofase',
            'PIEGA6ANTESINGOLO' => 'monofase',
            // max 2 fasi
            'STAMPAINDIGO' => 'monofase',
            'STAMPAINDIGOBN' => 'monofase',
            'STAMPAXL106' => 'monofase',
            'STAMPAXL106.1' => 'monofase',
            'STAMPAXL106.2' => 'monofase',
            'STAMPAXL106.3' => 'monofase',
            'STAMPAXL106.4' => 'monofase',
            'STAMPAXL106.5' => 'monofase',
            'STAMPAXL106.6' => 'monofase',
            'STAMPAXL106.7' => 'monofase',
            'STAMPAINDIGOBIANCO' => 'monofase',

            // Nuove fasi EXT e altre → monofase
            'est STAMPACALDOJOH' => 'monofase',
            'est FUSTSTELG33.44' => 'monofase',
            'est FUSTBOBST75X106' => 'monofase',
            'STAMPA.ESTERNA' => 'monofase',
            'EXTALL.COFANETTO.LEGOKART' => 'monofase',
            'EXTAllest.Manuale' => 'monofase',
            'EXTALLEST.SHOPPER' => 'monofase',
            'EXTALLESTIMENTO.ESPOSITOR' => 'monofase',
            'EXTAPPL.CORDONCINO0,035' => 'monofase',
            'EXTAVVIAMENTISTAMPA.EST1.' => 'monofase',
            'EXTBROSSCOPEST' => 'monofase',
            'EXTBROSSFILOREFE/A4EST' => 'monofase',
            'EXTBROSSFILOREFE/A5EST' => 'monofase',
            'EXTBROSSFRESATA/A4EST' => 'monofase',
            'EXTBROSSFRESATA/A5EST' => 'monofase',
            'EXTCARTONATO' => 'monofase',
            'EXTCARTONATO.GEN' => 'monofase',
            'EXTFUSTELLATURA72X51' => 'monofase',
            'EXTPUNTOMETALLICOEST' => 'monofase',
            'EXTSTAMPA.OFFSET11.EST' => 'monofase',
            'EXTSTAMPABUSTE.EST' => 'monofase',
            'EXTSTAMPASECCO' => 'monofase',
            'EXTUVSPOTEST' => 'monofase',
            'EXTUVSPOTSPESSEST' => 'monofase',
            'DEKIA-semplice' => 'monofase',
            'STAMPASECCO' => 'monofase',
            'STAMPACALDO04' => 'monofase',
            'STAMPACALDOBR' => 'monofase',
        ];
    }
}
