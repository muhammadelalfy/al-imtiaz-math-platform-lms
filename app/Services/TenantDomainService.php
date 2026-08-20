<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantDomainService
{
    public function resolve(Request $request): ?Tenant
    {
        $host = strtolower((string) $request->getHost());
        if (in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.manus.computer')) {
            return null;
        }

        return Tenant::query()->where('login_domain', $host)->where('domain_status', 'active')->first();
    }

    public function assertLoginMatchesTenant(Request $request, User $user): void
    {
        $tenant = $this->resolve($request);
        if ($tenant && $user->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages(['email' => 'هذا الحساب لا يتبع نطاق المركز الحالي.']);
        }
    }

    public function updateDomain(Tenant $tenant, ?string $domain): Tenant
    {
        if ($domain && ! TenantSubscription::query()->where('tenant_id', $tenant->id)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['login_domain' => 'يلزم اشتراك نشط قبل تفعيل نطاق دخول للمركز.']);
        }

        $tenant->update([
            'login_domain' => $domain ? strtolower($domain) : null,
            'domain_status' => $domain ? 'active' : 'pending',
        ]);

        return $tenant->refresh();
    }
}
