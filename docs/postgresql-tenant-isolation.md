# PostgreSQL Tenant Isolation and Domain Provisioning

## Purpose

Each subscribed teaching centre is represented by a **central tenant record**, a deterministic login domain, and a dedicated PostgreSQL schema. The implementation uses schemas rather than creating a separate physical database for every teacher. PostgreSQL schemas provide isolated object namespaces inside a single connected database, while Laravel supports named database connections and runtime database configuration. [1](https://www.postgresql.org/docs/current/ddl-schemas.html) [2](https://laravel.com/docs/13.x/database)

The control plane remains in the `public` schema. It owns platform users, tenants, packages, subscriptions, provisioning state, and domain state. The tenant plane is created only after a subscription is both **active** and **paid**. It contains the centre-owned educational records such as students, groups, attendance, worksheets, exams, payments, and notifications.

| Boundary | Storage location | Responsibility |
| --- | --- | --- |
| Platform control plane | `public` schema | Super administrators, tenant identity, packages, billing, domains, and provisioning audit state. |
| Tenant data plane | `tenant_<tenant_id>` schema | Centre-owned LMS data and tenant-local migration history. |
| Domain routing | Central tenant record | Resolves only a verified active domain to its tenant and rejects accounts from another tenant. |

## Invariants

The schema name is generated only by the server from the immutable tenant identifier, using the form `tenant_<id>`. It is never taken from a user-submitted slug or host name. The domain is generated as `<tenant-slug>.<TENANT_DOMAIN_BASE>` and is held in `pending_dns` state until it passes validation. A schema must exist and be at the current tenant migration version before a tenant domain is marked active.

The provisioning database role requires `CREATE` on the control database in order to create a schema. The runtime role receives `USAGE` on the tenant schema and only the required table privileges; it does not receive `CREATE` on arbitrary schemas. PostgreSQL documents that schema creation requires database `CREATE` privilege and that schema access is controlled through `USAGE` and `CREATE` privileges. [3](https://www.postgresql.org/docs/current/sql-createschema.html) [1](https://www.postgresql.org/docs/current/ddl-schemas.html)

> The active request connection must set `search_path` to the resolved tenant schema and `public`, never to an untrusted schema. PostgreSQL warns that including a schema writable by an untrusted party in `search_path` effectively trusts that party’s objects. [1](https://www.postgresql.org/docs/current/ddl-schemas.html)

## Provisioning Lifecycle

| Step | Trigger | Result | Failure response |
| --- | --- | --- | --- |
| 1. Register | Teacher selects a package | Creates central tenant, teacher, and pending subscription. | Roll back central transaction. No schema or domain is active. |
| 2. Activate | Super administrator records an active, paid subscription | Acquires a tenant row lock, derives schema and domain, and creates a provisioning audit record. | Leaves subscription unchanged and records a safe failure reason. |
| 3. Provision | Provisioning service uses the direct PostgreSQL connection | Creates the schema, locks down privileges, and applies tenant migrations idempotently. | Drops a newly created empty schema only when safe; otherwise marks provisioning failed for retry. |
| 4. Verify | Domain verifier confirms the requested wildcard/domain configuration | Marks schema and domain active, then allows tenant login. | Keeps the domain pending and blocks tenant-host access. |
| 5. Request handling | Any tenant-host request | Resolves host, validates active subscription and schema readiness, then applies the tenant `search_path` for the request only. | Rejects tenant data access with a safe unavailable response. |

Laravel’s direct PostgreSQL connection support is used for schema operations because managed transaction poolers may not support migrations or maintenance tasks; Laravel documents a separate direct connection for this purpose. [2](https://laravel.com/docs/13.x/database)

## Tenant Migration Contract

Tenant migrations live in `database/migrations/tenant` and are tracked per schema in that schema’s own `migrations` table. The migrator runs only the approved tenant migration directory with a generated, schema-specific PostgreSQL connection. Each migration must be idempotent, avoid unqualified references to control-plane tables, and use deterministic names for constraints and indexes.

The first migration creates the tenant-owned LMS tables. Subsequent work migrates existing tenant-aware models gradually; no request may read data from both a legacy global table and its tenant schema without an explicit transition strategy. All provisioning attempts are serialised by a central row lock and are retriable after a recoverable connection failure.

## Sass Contract

The existing `resources/js/styles/theme.scss` is the canonical hosted-LMS token entry point, and `frontend/src/lms/styles/theme.scss` is the canonical standalone Next.js entry point. Subscription components may not introduce new page-level `.css` files. Their palette, spacing, state, and reduced-motion rules are moved into Sass partials imported by those theme entry points, preserving Arabic RTL direction and the current public landing-page layout.

## Required Runtime Configuration

| Environment variable | Purpose | Required |
| --- | --- | --- |
| `TENANCY_DATABASE_URL` | Runtime PostgreSQL connection for control-plane and tenant reads/writes. | Yes |
| `TENANCY_PROVISIONING_DATABASE_URL` | Direct privileged PostgreSQL connection for schema creation and migrations. | Yes |
| `TENANT_DOMAIN_BASE` | Owned wildcard base domain used to derive tenant login domains. | Yes |
| `TENANCY_DATABASE_SSLMODE` | PostgreSQL SSL mode, normally `require` or `verify-full` for a managed provider. | Yes |

The current environment is SQLite-only and has no PostgreSQL PDO driver, so the connection cannot be switched safely until the production image includes `pdo_pgsql` and the above values are supplied through managed secrets.
