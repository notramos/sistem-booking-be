<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private UserRepository $userRepo) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userRepo->paginatedList($request->all());
        return $this->paginated($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'nip' => 'nullable|string|max:50|unique:users,nip',
            'role' => 'required|in:admin,sekretariat,jemaat',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = $this->userRepo->create(collect($validated)->except('role')->toArray());
        $user->assignRole($validated['role']);

        return $this->created($user->load('roles'), 'User berhasil dibuat');
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('roles.permissions')->findOrFail($id);
        return $this->success($user);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'nip' => 'nullable|string|max:50|unique:users,nip,' . $id,
            'is_active' => 'boolean',
        ]);

        $user = $this->userRepo->update($id, $validated);

        if ($request->has('role')) {
            $request->validate(['role' => 'in:admin,sekretariat,jemaat']);
            $user->syncRoles([$request->role]);
        }

        return $this->success($user->load('roles'), 'User berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->userRepo->delete($id);
        return $this->success(null, 'User berhasil dinonaktifkan');
    }

    public function toggleActive(string $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return $this->success($user, 'Status user berhasil diubah');
    }

    public function assignRoles(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = $this->userRepo->findOrFail($id);
        $user->syncRoles($request->roles);

        return $this->success($user->load('roles'), 'Role berhasil ditetapkan');
    }
}
