<?php

namespace App\Http\Middleware;

use App\Services\PostgresTenantSchemaProvisioner;
use App\Services\TenantDomainService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseTenantPostgresSchema
{
    public function __construct(
        private readonly TenantDomainService $domains,
        private readonly PostgresTenantSchemaProvisioner $schemas,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->domains->resolve($request);
        if ($tenant === null) {
            return $next($request);
        }

        if (! $this->schemas->isReady($tenant) && (bool) config('tenancy.enabled')) {
            abort(503, 'يتم تجهيز مساحة بيانات المركز. حاول مرة أخرى بعد قليل.');
        }

        $request->attributes->set('tenant', $tenant);
        $centralConnection = $this->schemas->activateRequestSchema($tenant);

        try {
            return $next($request);
        } finally {
            $this->schemas->releaseRequestSchema($centralConnection);
        }
    }
}
