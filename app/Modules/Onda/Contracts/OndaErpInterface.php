<?php

declare(strict_types=1);

namespace App\Modules\Onda\Contracts;

use Carbon\Carbon;

/**
 * Contratto astratto per l'accesso al gestionale Onda (SQL Server).
 *
 * Espone solo metodi di lettura "puri" (no business logic, no transformation):
 * il caller riceve righe grezze (stdClass) e si occupa del mapping su entità MES.
 *
 * Vantaggi dell'astrazione:
 *  - in test si fornisce un fake/stub senza dover collegare SQL Server
 *  - swap futuro ERP (es. SAP, Onda v2) sostituendo la sola implementazione
 *  - business logic separata da I/O (Single Responsibility)
 *
 * Tutti i metodi tornano array di stdClass (payload Onda), non Eloquent models:
 * la mappatura su Ordine/OrdineFase/DdtSpedizione resta nei *SyncService.
 */
interface OndaErpInterface
{
    /**
     * Ordini di produzione attivi (TipoDocumento=2) sopra una data soglia.
     *
     * Ritorna righe con: CodCommessa, PrdIdDoc, CodArt, OC_Descrizione,
     * ClienteNome, QtaDaProdurre, DataPresConsegna, DataRegistrazione,
     * CodCarta, DescrizioneCarta, QtaCarta, UMCarta, TotMerce, NotePrestampa,
     * Responsabile, CommentoProduzione, OrdineCliente, CostoMateriali,
     * SuppBaseCM, SuppAltezzaCM, Resa, TotSupporti, CodFase, CodMacchina,
     * QtaDaLavorare, UMFase, TipoRigaFase, CodFaseRiga.
     *
     * @return list<\stdClass>
     */
    public function getOrdiniDal(Carbon $dal): array;

    /**
     * Ordini di produzione di una singola commessa, senza filtro data.
     *
     * @return list<\stdClass>
     */
    public function getOrdiniPerCommessa(string $codCommessa): array;

    /**
     * Mappa {CodMacchina => OC_FogliScartoIniz} per macchine con scarti previsti > 0.
     *
     * @return array<string, int|float>
     */
    public function getScartiPrevistiPerMacchina(): array;

    /**
     * DDT a fornitore (TipoDocumento=7) ultimi N giorni — versione "headers".
     *
     * Ritorna: IdDoc, DataDocumento, IdAnagrafica, RagioneSociale, Descrizione, Qta, CodUnMis.
     *
     * @return list<\stdClass>
     */
    public function getDdtFornitoreUltimiGiorni(int $giorni = 30): array;

    /**
     * DDT a fornitore (TipoDocumento=7) ultimi N giorni — versione "lavorazioni"
     * (solo descrizione, usata per parsing keyword multi-fase).
     *
     * Ritorna: IdDoc, DataDocumento, IdAnagrafica, RagioneSociale, Descrizione.
     *
     * @return list<\stdClass>
     */
    public function getDdtFornitoreLavorazioniUltimiGiorni(int $giorni = 30): array;
}
