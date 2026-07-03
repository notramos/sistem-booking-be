<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignRolesRequest;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\UserResource;
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

        return $this->paginated(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $user = $this->userRepo->create(collect($validated)->except('role')->toArray());
        $user->assignRole($validated['role']);

        return $this->created(new UserResource($user->load('roles')), 'User berhasil dibuat');
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('roles.permissions')->findOrFail($id);

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();
        $role = $validated['role'] ?? null;
        unset($validated['role']);

        $user = $this->userRepo->update($id, $validated);

        if ($role) {
            $user->syncRoles([$role]);
        }

        return $this->success(new UserResource($user->load('roles')), 'User berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->userRepo->delete($id);

        return $this->success(null, 'User berhasil dinonaktifkan');
    }

    public function toggleActive(string $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        return $this->success(new UserResource($user), 'Status user berhasil diubah');
    }

    public function assignRoles(AssignRolesRequest $request, string $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id);
        $user->syncRoles($request->roles);

        return $this->success(new UserResource($user->load('roles')), 'Role berhasil ditetapkan');
    }
}
