<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults(), 'confirmed'],
            'role' => 'sometimes|in:parent,student',
        ]);

        $user = User::create($data);

        return response()->json($this->tokenResponse($user), 201);
    }

    public function login(Request $request)
    {
        return $this->loginForRole($request, 'general');
    }

    public function loginAsRole(Request $request, string $role)
    {
        abort_unless(in_array($role, ['admin', 'teacher', 'parent', 'student'], true), 404);

        return $this->loginForRole($request, $role);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['studentAccount.student', 'roles.permissions', 'permissions']);
        $user->setAttribute('can_manage_authorization', $user->can('authorization.manage'));

        return $user;
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ['success' => true];
    }

    private function loginForRole(Request $request, string $role): array
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $query = User::where('email', $data['email']);

        if ($role !== 'general') {
            $query->where('role', $role);
        }

        $user = $query->first();
        abort_unless($user && Hash::check($data['password'], $user->password), 422, 'بيانات الدخول غير صحيحة لهذا النوع من الحسابات.');

        return $this->tokenResponse($user, $role === 'general' ? $user->role : $role);
    }

    private function tokenResponse(User $user, ?string $loginType = null): array
    {
        return [
            'user' => $user->load(['studentAccount.student', 'roles.permissions', 'permissions']),
            'token' => $user->createToken('lms-'.$loginType, ['guard:'.$loginType])->plainTextToken,
            'login_type' => $loginType ?? $user->role,
        ];
    }
}
