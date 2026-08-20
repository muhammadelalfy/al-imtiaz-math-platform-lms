# Multi-Guard Authentication and Staff Authorization

## Purpose

The platform uses **separate Sanctum login entry points** for administrators, teachers, parents, and students. Each issued token carries a guard ability matching the authenticated account type. This makes a teacher credential unusable at an administrator entry point and allows staff-only APIs to verify both the account role and the token guard.

| Account type  | Login endpoint                 | Token ability   | Primary access boundary                                       |
| ------------- | ------------------------------ | --------------- | ------------------------------------------------------------- |
| Administrator | `POST /api/auth/admin/login`   | `guard:admin`   | System administration and custom-role assignment              |
| Teacher       | `POST /api/auth/teacher/login` | `guard:teacher` | Educational operations and delegated authorization management |
| Parent        | `POST /api/auth/parent/login`  | `guard:parent`  | Linked student records only                                   |
| Student       | `POST /api/auth/student/login` | `guard:student` | Own learning and assessment records only                      |

The application retains its `users.role` column as the **account type** because that value controls account login and portal separation. Database-backed roles and permissions are a separate authorization layer for staff capabilities. The `User` model intentionally resolves this layer through one `web` permission namespace. The guards isolate authentication routes and tokens, while the permission namespace avoids duplicating identical staff permissions across four guards. This follows the documented pattern for a user model that can authenticate through more than one guard.[1]

## Authorization-management API

All endpoints below require a valid Sanctum token for an `admin` or `teacher` account, the matching `guard:*` token ability, and the `authorization.manage` permission. Parent and student tokens are rejected before the management service is invoked.

| Endpoint                                               | Operation                                             | Server-side safeguard                                                                                     |
| ------------------------------------------------------ | ----------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `GET /api/staff/authorization/catalog`                 | Read permissions, custom roles, and staff assignments | Permission gate plus staff guard middleware                                                               |
| `POST/PUT/DELETE /api/staff/authorization/permissions` | Manage a custom permission                            | System permissions cannot be changed or removed                                                           |
| `POST/PUT/DELETE /api/staff/authorization/roles`       | Manage a custom role and its permission set           | Teachers cannot embed protected system permissions                                                        |
| `PUT /api/staff/authorization/staff/{user}/roles`      | Assign custom roles to staff                          | Available to administrators only; system roles are preserved and cannot be assigned through this endpoint |

> **Privilege-escalation boundary:** A teacher delegated `authorization.manage` may maintain custom roles and permissions, but cannot modify protected system capabilities, assign roles to staff, or change an administrator account. The administrator account remains the only implicit super-administrator through Laravel’s authorization gate.

## Seed baseline and local verification

`ArabicDemoSeeder` creates the protected `authorization.manage` permission and a `staff-permission-manager` system role. The local teacher account receives that system role so the teacher portal can exercise the management UI. The seeder is deliberately restricted to the `local` and `testing` environments.

Run migrations and test the feature with:

```bash
php artisan migrate
php artisan test --compact tests/Feature/AuthorizationManagementTest.php
pnpm check
pnpm test:frontend
```

The feature tests cover the teacher-specific login, protected staff catalog, custom permission/role CRUD, rejection of protected permission edits, and administrator-only custom-role assignment. The client test ensures the teacher login and all authorization-management endpoint mappings remain stable.

## References

[1]: https://spatie.be/docs/laravel-permission/v8/basic-usage/multiple-guards "Spatie Laravel Permission — Using multiple guards"
