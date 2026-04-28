<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configurazione per tenant SaaS (multi-cliente).
 * NON usa BelongsToTenant: la tabella stessa ha tenant_id come PK.
 */
class TenantConfig extends Model
{
    protected $table = 'tenant_config';
    protected $primaryKey = 'tenant_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'nome_azienda', 'logo_url',
        'color_primary', 'color_secondary',
        'erp_type', 'macchine_offset_brand', 'macchine_digitali_brand',
        'mossa37_pesi', 'feature_flags',
        'license_key', 'license_expires_at', 'attivo',
    ];

    protected $casts = [
        'mossa37_pesi' => 'array',
        'feature_flags' => 'array',
        'license_expires_at' => 'datetime',
        'attivo' => 'boolean',
    ];

    public function isLicenseValid(): bool
    {
        if (!$this->attivo) return false;
        if (!$this->license_expires_at) return true; // nessuna scadenza
        return $this->license_expires_at->isFuture();
    }

    public function feature(string $key, $default = false)
    {
        return $this->feature_flags[$key] ?? $default;
    }

    public function peso(string $criterio, int $default = 0): int
    {
        return $this->mossa37_pesi[$criterio] ?? $default;
    }
}
