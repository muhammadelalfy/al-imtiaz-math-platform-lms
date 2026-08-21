<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tenant */
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'slug' => $this->slug,
            'login_domain' => $this->login_domain, 'domain_status' => $this->domain_status,
            'database_schema' => $this->database_schema,
            'schema_status' => $this->schema_status,
            'schema_version' => $this->schema_version,
            'schema_provisioned_at' => $this->schema_provisioned_at
                ? CarbonImmutable::parse((string) $this->schema_provisioned_at)->toIso8601String()
                : null,
        ];
    }
}
