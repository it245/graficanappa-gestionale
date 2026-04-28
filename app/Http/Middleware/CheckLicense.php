<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TenantConfig;
use Illuminate\Support\Facades\Cache;

/**
 * Verifica licenza tenant. Se scaduta → blocca accesso.
 * Tenant 'grafica_nappa' nativo bypassa (license = "GRAFICA-NAPPA-NATIVE", scadenza 50 anni).
 */
class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = tenant_id();

        $config = Cache::remember("tenant_license_{$tenantId}", 300, function () use ($tenantId) {
            return TenantConfig::find($tenantId);
        });

        if (!$config) {
            abort(404, "Tenant '{$tenantId}' non trovato");
        }

        if (!$config->isLicenseValid()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'license_expired'], 403);
            }
            return response()->view('errors.license_expired', ['config' => $config], 403);
        }

        return $next($request);
    }
}
