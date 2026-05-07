<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\TipoAzione;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servizio di lettura sopra la tabella `audit_logs`.
 *
 * Ritorna Collection di stdClass row per evitare tight coupling con un
 * model Eloquent specifico (lo schema legacy usa colonne snake_case).
 *
 * NB: i metodi di scrittura *non* sono qui — vedi {@see AuditLogService}.
 */
final class AuditQueryService
{
    public function __construct(
        private readonly string $tabella = 'audit_logs',
    ) {}

    /**
     * Tutti i log per una specifica entità (ordine, fase, articolo).
     *
     * @return Collection<int,object>
     */
    public function perEntita(EntitaAudit $entita, int|string $id): Collection
    {
        $q = DB::table($this->tabella)
            ->where('model', $entita->value);

        if (is_int($id)) {
            $q->where('model_id', $id);
        } else {
            // ID stringa (es. cod_commessa "0067164-26") cercato nel campo extra
            $q->where('extra', 'like', '%eid=' . $id . '%');
        }

        /** @var Collection<int,object> $rows */
        $rows = $q->orderByDesc('created_at')->get();
        return $rows;
    }

    /**
     * Log di uno specifico utente in un range temporale.
     *
     * @return Collection<int,object>
     */
    public function perUtente(
        int $userId,
        ?CarbonInterface $da = null,
        ?CarbonInterface $a = null,
    ): Collection {
        $q = DB::table($this->tabella)
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($da !== null) {
            $q->where('created_at', '>=', $da->toDateTimeString());
        }
        if ($a !== null) {
            $q->where('created_at', '<=', $a->toDateTimeString());
        }

        /** @var Collection<int,object> $rows */
        $rows = $q->get();
        return $rows;
    }

    /**
     * Log filtrati per tipo azione (es. tutti i login, tutti i delete).
     *
     * @return Collection<int,object>
     */
    public function perAzione(TipoAzione $azione, int $limit = 500): Collection
    {
        /** @var Collection<int,object> $rows */
        $rows = DB::table($this->tabella)
            ->where('action', $azione->value)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
        return $rows;
    }
}
