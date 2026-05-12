<?php

declare(strict_types=1);

namespace App\Modules\Onda\Adapters;

use App\Modules\Onda\Contracts\OndaErpInterface;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;

/**
 * Implementazione concreta {@see OndaErpInterface} su SQL Server (connessione 'onda').
 *
 * Strangler Fig: query SPOSTATE LETTERALMENTE da {@see \App\Services\OndaSyncService}
 * (estratta solo l'I/O, la business logic resta nei *SyncService).
 *
 * Schema Onda invariato: STDAnagrafiche, ATTDocTeste/Righe/Coda, PRDDocTeste/Righe/Fasi,
 * OC_ATTDocRigheExt, PRDMacchinari.
 */
final class OndaErpAdapter implements OndaErpInterface
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @inheritDoc
     *
     * Query identica all'originale (SELECT ... FROM ATTDocTeste t INNER JOIN PRDDocTeste p ...
     * WHERE t.TipoDocumento = '2' AND t.DataRegistrazione >= ?).
     */
    public function getOrdiniDal(Carbon $dal): array
    {
        $dataSoglia = $dal->format('Ymd');

        return $this->db->connection('onda')->select("
            SELECT
                t.CodCommessa,
                p.IdDoc AS PrdIdDoc,
                p.CodArt,
                p.OC_Descrizione,
                attDesc.AttDescrizione,
                COALESCE(NULLIF(p.NCPRagioneSociale, ''), a.RagioneSociale) AS ClienteNome,
                p.QtaDaProdurre,
                p.DataPresConsegna,
                t.DataRegistrazione,
                carta.CodArt AS CodCarta,
                carta.Descrizione AS DescrizioneCarta,
                carta.Qta AS QtaCarta,
                carta.CodUnMis AS UMCarta,
                t.TotMerce,
                t.ncpcommentoprestampa AS NotePrestampa,
                t.ncprespocommessa AS Responsabile,
                t.OC_CommentoProduz AS CommentoProduzione,
                t.ncpordinecliente AS OrdineCliente,
                materiali.CostoMateriali,
                supporto.OC_SuppBaseCM AS SuppBaseCM,
                supporto.OC_SuppAltezzaCM AS SuppAltezzaCM,
                supporto.OC_Resa AS Resa,
                supporto.OC_TotSupporti AS TotSupporti,
                f.CodFase,
                f.CodMacchina,
                f.QtaDaLavorare,
                f.CodUnMis AS UMFase,
                f.TipoRiga AS TipoRigaFase,
                rigaAtt.CodArt AS CodFaseRiga
            FROM ATTDocTeste t
            INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt
                FROM ATTDocRighe r
                WHERE r.IdDoc = t.IdDoc
                  AND (r.CodArt = f.CodFase OR r.CodArt = SUBSTRING(f.CodFase, 4, LEN(f.CodFase)))
            ) rigaAtt
            OUTER APPLY (
                SELECT TOP 1 r.Descrizione AS AttDescrizione
                FROM ATTDocRighe r
                WHERE r.IdDoc = t.IdDoc
                  AND r.TipoRiga = 1
                  AND r.CodArt = p.CodArt
                ORDER BY r.NrRiga
            ) attDesc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
                FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
                ORDER BY r.Sequenza
            ) carta
            OUTER APPLY (
                SELECT SUM(r2.Totale) AS CostoMateriali
                FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
            ) materiali
            OUTER APPLY (
                SELECT TOP 1 e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_Resa, e.OC_TotSupporti
                FROM OC_ATTDocRigheExt e
                WHERE e.OC_IdDoc = t.IdDoc
                  AND e.OC_CodArtSupporto IS NOT NULL AND e.OC_CodArtSupporto != ''
            ) supporto
            WHERE t.TipoDocumento = '2'
              AND t.DataRegistrazione >= CAST(? AS datetime)
        ", [$dataSoglia]);
    }

    /**
     * @inheritDoc
     */
    public function getOrdiniPerCommessa(string $codCommessa): array
    {
        return $this->db->connection('onda')->select("
            SELECT
                t.CodCommessa,
                p.IdDoc AS PrdIdDoc,
                p.CodArt,
                p.OC_Descrizione,
                attDesc.AttDescrizione,
                COALESCE(NULLIF(p.NCPRagioneSociale, ''), a.RagioneSociale) AS ClienteNome,
                p.QtaDaProdurre,
                p.DataPresConsegna,
                t.DataRegistrazione,
                carta.CodArt AS CodCarta,
                carta.Descrizione AS DescrizioneCarta,
                carta.Qta AS QtaCarta,
                carta.CodUnMis AS UMCarta,
                t.TotMerce,
                t.ncpcommentoprestampa AS NotePrestampa,
                t.ncprespocommessa AS Responsabile,
                t.OC_CommentoProduz AS CommentoProduzione,
                t.ncpordinecliente AS OrdineCliente,
                materiali.CostoMateriali,
                supporto.OC_SuppBaseCM AS SuppBaseCM,
                supporto.OC_SuppAltezzaCM AS SuppAltezzaCM,
                supporto.OC_Resa AS Resa,
                supporto.OC_TotSupporti AS TotSupporti,
                f.CodFase,
                f.CodMacchina,
                f.QtaDaLavorare,
                f.CodUnMis AS UMFase,
                f.TipoRiga AS TipoRigaFase,
                rigaAtt.CodArt AS CodFaseRiga
            FROM ATTDocTeste t
            INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt
                FROM ATTDocRighe r
                WHERE r.IdDoc = t.IdDoc
                  AND (r.CodArt = f.CodFase OR r.CodArt = SUBSTRING(f.CodFase, 4, LEN(f.CodFase)))
            ) rigaAtt
            OUTER APPLY (
                SELECT TOP 1 r.Descrizione AS AttDescrizione
                FROM ATTDocRighe r
                WHERE r.IdDoc = t.IdDoc
                  AND r.TipoRiga = 1
                  AND r.CodArt = p.CodArt
                ORDER BY r.NrRiga
            ) attDesc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
                FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
                ORDER BY r.Sequenza
            ) carta
            OUTER APPLY (
                SELECT SUM(r2.Totale) AS CostoMateriali
                FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
            ) materiali
            OUTER APPLY (
                SELECT TOP 1 e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_Resa, e.OC_TotSupporti
                FROM OC_ATTDocRigheExt e
                WHERE e.OC_IdDoc = t.IdDoc
                  AND e.OC_CodArtSupporto IS NOT NULL AND e.OC_CodArtSupporto != ''
            ) supporto
            WHERE t.TipoDocumento = '2'
              AND t.CodCommessa = ?
        ", [$codCommessa]);
    }

    /**
     * @inheritDoc
     */
    public function getScartiPrevistiPerMacchina(): array
    {
        $righe = $this->db->connection('onda')->select(
            "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
        );

        $out = [];
        foreach ($righe as $r) {
            $out[$r->CodMacchina] = $r->OC_FogliScartoIniz;
        }
        return $out;
    }

    /**
     * @inheritDoc
     */
    public function getDdtFornitoreUltimiGiorni(int $giorni = 30): array
    {
        return $this->db->connection('onda')->select("
            SELECT t.IdDoc, t.DataDocumento, t.IdAnagrafica, a.RagioneSociale,
                   r.Descrizione, r.Qta, r.CodUnMis
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            WHERE t.TipoDocumento = 7
              AND t.DataRegistrazione >= DATEADD(day, ?, GETDATE())
        ", [-abs($giorni)]);
    }

    /**
     * @inheritDoc
     */
    public function getDdtFornitoreLavorazioniUltimiGiorni(int $giorni = 30): array
    {
        return $this->db->connection('onda')->select("
            SELECT t.IdDoc, t.DataDocumento, t.IdAnagrafica, a.RagioneSociale, r.Descrizione
            FROM ATTDocTeste t
            JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            WHERE t.TipoDocumento = 7
              AND t.DataRegistrazione >= DATEADD(day, ?, GETDATE())
        ", [-abs($giorni)]);
    }
}
