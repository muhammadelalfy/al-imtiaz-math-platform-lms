<?php

namespace App\Services;

use App\Models\Tenant;
use InvalidArgumentException;

final class TenantSchemaName
{
    public static function for(Tenant $tenant): string
    {
        $id = (int) $tenant->getKey();
        if ($id < 1) {
            throw new InvalidArgumentException('A persisted tenant is required to derive a schema name.');
        }

        return "tenant_{$id}";
    }

    public static function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/\Atenant_[1-9][0-9]*\z/', $identifier)) {
            throw new InvalidArgumentException('The tenant schema identifier is invalid.');
        }

        return '"'.$identifier.'"';
    }

    public static function quoteRole(string $role): string
    {
        if (! preg_match('/\A[a-z_][a-z0-9_]{0,62}\z/', $role)) {
            throw new InvalidArgumentException('The tenant runtime role identifier is invalid.');
        }

        return '"'.$role.'"';
    }
}
