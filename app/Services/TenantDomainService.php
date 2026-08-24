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

    public function assignSubscriptionDomain(Tenant $tenant): Tenant
    {
        $baseDomain = strtolower(trim((string) config('tenancy.domain_base')));
        if ($baseDomain === '' || $tenant->login_domain || $tenant->schema_status !== 'ready') {
            return $tenant;
        }

        $domain = strtolower("{$tenant->slug}.{$baseDomain}");
        if (! preg_match('/\A[a-z0-9][a-z0-9-]{0,62}(?:\.[a-z0-9][a-z0-9-]{0,62})+\z/', $domain)) {
            throw ValidationException::withMessages(['login_domain' => 'يتعذر إنشاء نطاق دخول صالح لهذا المركز.']);
        }

        $tenant->forceFill([
            'login_domain' => $domain,
            'domain_status' => 'pending_dns',
        ])->save();

        return $tenant->refresh();
    }
}
