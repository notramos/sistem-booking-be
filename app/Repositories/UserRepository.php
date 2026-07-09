<?php

namespace App\Repositories;

use App\Models\User;
use App\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function findOrFail(string $id): User
    {
        return User::findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(string $id, array $data): User
    {
        $user = $this->findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete(string $id): void
    {
        $user = $this->findOrFail($id);
        $user->update(['is_active' => false]);
    }

    public function paginatedList(array $filters = []): LengthAwarePaginator
    {
        $query = User::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('nip', 'ilike', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate(Pagination::perPage($filters['per_page'] ?? null, 15));
    }
}
