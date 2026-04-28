<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Risolve tenant_id dalla richiesta:
 * - Subdomain: 4graph.mes.graficanappa.com → tenant_id = '4graph'
 * - Header X-Tenant-ID (per API/agente)
 * - Default: config('app.default_tenant_id', 'grafica_nappa')
 *
 * Salva in sessione per risoluzioni successive nello stesso ciclo.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $this->resolveTenantId($request);
        set_tenant($tenantId);
        return $next($request);
    }

    private function resolveTenantId(Request $request): string
    {
        // 1. Header per API
        $headerTenant = $request->header('X-Tenant-ID');
        if ($headerTenant) {
            return $headerTenant;
        }

        // 2. Subdomain (4graph.mes.graficanappa.com)
        $host = $request->getHost();
        $rootDomain = config('app.tenant_root_domain', 'mes.graficanappa.com');
        if (str_ends_with($host, ".{$rootDomain}")) {
            $subdomain = substr($host, 0, -strlen($rootDomain) - 1);
            if ($subdomain && $subdomain !== 'www') {
                return $subdomain;
            }
        }

        // 3. Default
        return config('app.default_tenant_id', 'grafica_nappa');
    }
}
