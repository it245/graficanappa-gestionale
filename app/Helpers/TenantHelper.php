<?php

if (!function_exists('tenant_id')) {
    /**
     * Restituisce il tenant_id corrente per la richiesta.
     * Risoluzione (in ordine):
     * 1. Sessione (set_tenant impostato)
     * 2. Subdomain (es. 4graph.mes.graficanappa.com → 4graph)
     * 3. Default 'grafica_nappa'
     */
    function tenant_id(): string
    {
        $sessionTenant = session('tenant_id');
        if ($sessionTenant) {
            return $sessionTenant;
        }
        return config('app.default_tenant_id', 'grafica_nappa');
    }
}

if (!function_exists('set_tenant')) {
    function set_tenant(string $tenantId): void
    {
        session(['tenant_id' => $tenantId]);
    }
}

if (!function_exists('tenant_config')) {
    /**
     * Recupera config tenant corrente (cached 5min).
     * @param string|null $key se passato, ritorna solo quel campo
     */
    function tenant_config(?string $key = null)
    {
        $tenantId = tenant_id();
        $config = \Illuminate\Support\Facades\Cache::remember(
            "tenant_config_{$tenantId}",
            300,
            fn() => \DB::table('tenant_config')->where('tenant_id', $tenantId)->first()
        );
        if (!$config) {
            return $key ? null : null;
        }
        if ($key === null) {
            return $config;
        }
        // Auto-decode JSON columns
        if (in_array($key, ['mossa37_pesi', 'feature_flags']) && is_string($config->{$key} ?? null)) {
            return json_decode($config->{$key}, true);
        }
        return $config->{$key} ?? null;
    }
}
