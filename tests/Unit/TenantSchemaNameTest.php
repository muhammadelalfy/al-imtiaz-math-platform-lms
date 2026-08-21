<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\TenantSchemaName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TenantSchemaNameTest extends TestCase
{
    public function test_schema_name_is_derived_only_from_a_persisted_tenant_identifier(): void
    {
        $tenant = new Tenant();
        $tenant->setAttribute('id', 42);

        $this->assertSame('tenant_42', TenantSchemaName::for($tenant));
        $this->assertSame('"tenant_42"', TenantSchemaName::quoteIdentifier('tenant_42'));
    }

    public function test_untrusted_schema_identifiers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TenantSchemaName::quoteIdentifier('tenant_1; DROP SCHEMA public;');
    }
}
