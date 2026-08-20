<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuthorizationPermissionRequest;
use App\Http\Requests\StoreAuthorizationRoleRequest;
use App\Http\Requests\SyncStaffAuthorizationRolesRequest;
use App\Http\Requests\UpdateAuthorizationPermissionRequest;
use App\Http\Requests\UpdateAuthorizationRoleRequest;
use App\Http\Resources\AuthorizationPermissionResource;
use App\Http\Resources\AuthorizationRoleResource;
use App\Http\Resources\AuthorizationStaffResource;
use App\Models\AuthorizationPermission;
use App\Models\AuthorizationRole;
use App\Models\User;
use App\Services\AuthorizationManagementService;
use Illuminate\Http\Request;

class AuthorizationManagementController extends Controller
{
    public function __construct(private readonly AuthorizationManagementService $authorization)
    {
    }

    public function index()
    {
        $catalog = $this->authorization->catalog();

        return [
            'permissions' => AuthorizationPermissionResource::collection($catalog['permissions']),
            'roles' => AuthorizationRoleResource::collection($catalog['roles']),
            'staff' => AuthorizationStaffResource::collection($catalog['staff']),
        ];
    }

    public function storePermission(StoreAuthorizationPermissionRequest $request)
    {
        return (new AuthorizationPermissionResource($this->authorization->createPermission($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function updatePermission(UpdateAuthorizationPermissionRequest $request, AuthorizationPermission $permission)
    {
        return new AuthorizationPermissionResource($this->authorization->updatePermission($permission, $request->validated()));
    }

    public function destroyPermission(Request $request, AuthorizationPermission $permission)
    {
        abort_unless($request->user()?->can('authorization.manage'), 403);
        $this->authorization->deletePermission($permission);

        return response()->noContent();
    }

    public function storeRole(StoreAuthorizationRoleRequest $request)
    {
        /** @var User $actor */
        $actor = $request->user();

        return (new AuthorizationRoleResource($this->authorization->createRole($actor, $request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function updateRole(UpdateAuthorizationRoleRequest $request, AuthorizationRole $role)
    {
        /** @var User $actor */
        $actor = $request->user();

        return new AuthorizationRoleResource($this->authorization->updateRole($actor, $role, $request->validated()));
    }

    public function destroyRole(Request $request, AuthorizationRole $role)
    {
        abort_unless($request->user()?->can('authorization.manage'), 403);
        $this->authorization->deleteRole($role);

        return response()->noContent();
    }

    public function syncStaffRoles(SyncStaffAuthorizationRolesRequest $request, User $user)
    {
        /** @var User $actor */
        $actor = $request->user();

        return new AuthorizationStaffResource($this->authorization->syncStaffRoles($actor, $user, $request->validated('role_ids')));
    }
}
