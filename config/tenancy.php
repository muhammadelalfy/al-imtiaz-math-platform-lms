<?php

return [
    'mode' => env('TENANCY_MODE', env('DB_CONNECTION') === 'pgsql' ? 'postgres_schema' : 'shared_development'),
    'enabled' => env('TENANCY_POSTGRES_ENABLED', env('DB_CONNECTION') === 'pgsql'),
    'database_url' => env('TENANCY_DATABASE_URL', env('DB_URL')),
    'provisioning_database_url' => env('TENANCY_PROVISIONING_DATABASE_URL'),
    'runtime_role' => env('TENANCY_RUNTIME_ROLE'),
    'domain_base' => env('TENANT_DOMAIN_BASE'),
    'sslmode' => env('DB_SSLMODE', 'require'),
];
