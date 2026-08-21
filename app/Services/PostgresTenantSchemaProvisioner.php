<?php

namespace App\Services;

use App\Contracts\Services\TenantSchemaProvisionerInterface;
use App\Exceptions\SubscriptionStorageException;
use App\Models\Tenant;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostgresTenantSchemaProvisioner implements TenantSchemaProvisionerInterface
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly Filesystem $files,
        private readonly Dispatcher $events,
    ) {
    }

    public function provision(Tenant $tenant): Tenant
    {
        if (! $this->isProvisioningEnabled()) {
            return $tenant;
        }

        $tenant = DB::transaction(function () use ($tenant): Tenant {
            $locked = Tenant::query()->lockForUpdate()->findOrFail($tenant->getKey());
            if ($this->isReady($locked)) {
                return $locked;
            }

            $locked->forceFill([
                'database_schema' => TenantSchemaName::for($locked),
                'schema_status' => 'provisioning',
                'provisioning_error' => null,
            ])->save();

            return $locked;
        }, 3);

        try {
            $connection = $this->connect((string) $tenant->database_schema);
            $quotedSchema = TenantSchemaName::quoteIdentifier((string) $tenant->database_schema);
            $connection->statement("CREATE SCHEMA IF NOT EXISTS {$quotedSchema}");
            $connection->statement("REVOKE ALL ON SCHEMA {$quotedSchema} FROM PUBLIC");
            $this->runMigrations();
            $this->grantRuntimeAccess($connection, $quotedSchema);

            $tenant->forceFill([
                'schema_status' => 'ready',
                'schema_version' => 'initial',
                'schema_provisioned_at' => now(),
                'provisioning_error' => null,
            ])->save();

            return $tenant->refresh();
        } catch (Throwable $exception) {
            $tenant->forceFill([
                'schema_status' => 'failed',
                'provisioning_error' => str($exception->getMessage())->limit(900)->toString(),
            ])->save();

            throw new SubscriptionStorageException('تعذر تجهيز مساحة بيانات المركز. لم يتم تفعيل نطاق الدخول.', previous: $exception);
        } finally {
            $this->database->purge('tenant_provisioning');
        }
    }

    public function isReady(Tenant $tenant): bool
    {
        return $tenant->schema_status === 'ready'
            && is_string($tenant->database_schema)
            && $tenant->database_schema !== '';
    }

    public function activateRequestSchema(Tenant $tenant): string
    {
        $centralConnection = $this->database->getDefaultConnection();
        if (! $this->isRuntimeEnabled() || ! $this->isReady($tenant)) {
            return $centralConnection;
        }

        $this->connectionFor(
            'tenant_runtime',
            (string) config('tenancy.database_url'),
            (string) $tenant->database_schema,
        );
        $this->database->setDefaultConnection('tenant_runtime');

        return $centralConnection;
    }

    public function releaseRequestSchema(string $centralConnection): void
    {
        $this->database->setDefaultConnection($centralConnection);
        $this->database->purge('tenant_runtime');
    }

    private function isProvisioningEnabled(): bool
    {
        return $this->isRuntimeEnabled()
            && is_string(config('tenancy.provisioning_database_url'))
            && config('tenancy.provisioning_database_url') !== '';
    }

    private function isRuntimeEnabled(): bool
    {
        return (bool) config('tenancy.enabled')
            && is_string(config('tenancy.database_url'))
            && config('tenancy.database_url') !== '';
    }

    private function connect(string $schema): \Illuminate\Database\Connection
    {
        return $this->connectionFor('tenant_provisioning', (string) config('tenancy.provisioning_database_url'), $schema);
    }

    private function connectionFor(string $name, string $url, string $schema): \Illuminate\Database\Connection
    {
        $connection = config('database.connections.pgsql');
        $connection['url'] = $url;
        $connection['search_path'] = "{$schema},public";
        $connection['sslmode'] = config('tenancy.sslmode', 'require');

        Config::set("database.connections.{$name}", $connection);
        $this->database->purge($name);

        return $this->database->connection($name);
    }

    private function runMigrations(): void
    {
        $repository = new DatabaseMigrationRepository($this->database, 'migrations');
        $repository->setSource('tenant_provisioning');
        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $migrator = new Migrator($repository, $this->database, $this->files, $this->events);
        $migrator->setConnection('tenant_provisioning');
        $migrator->run([database_path('migrations/tenant')], ['force' => true]);
    }

    private function grantRuntimeAccess(\Illuminate\Database\Connection $connection, string $quotedSchema): void
    {
        $role = config('tenancy.runtime_role');
        if (! is_string($role) || $role === '') {
            return;
        }

        $quotedRole = TenantSchemaName::quoteRole($role);
        $connection->statement("GRANT USAGE ON SCHEMA {$quotedSchema} TO {$quotedRole}");
        $connection->statement("GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA {$quotedSchema} TO {$quotedRole}");
        $connection->statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA {$quotedSchema} TO {$quotedRole}");
    }
}
