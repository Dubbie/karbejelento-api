<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Fetch a paginated list of users.
     * Corresponds to `findAll`.
     */
    public function getAllUsers(Request $request): PaginatedResult
    {
        // Define which fields can be sorted and filtered for the User model
        $options = [
            'sortableFields' => ['name', 'email', 'created_at'],
            'filterableFields' => ['name', 'email', 'role'],
        ];

        return User::query()->advancedPaginate($request, $options);
    }

    /**
     * Find a single user by their UUID.
     */
    public function getUserByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return User::create([
            'uuid' => Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // The 'hashed' cast on the model handles hashing
            'role' => $data['role'],
            'manager_id' => $data['manager_id'] ?? null,
        ]);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): bool
    {
        // Handle password update separately if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $user->update($data);
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }
}
