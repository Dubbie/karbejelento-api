<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Constructor
     *
     * Inject the user service dependency.
     */
    public function __construct(private readonly UserService $userService) {}

    /**
     * User Profile
     *
     * Display the authenticated user's profile.
     */
    public function getProfile(Request $request): User
    {
        // The authenticated user is available directly from the request.
        return $request->user();
    }

    /**
     * List Users
     *
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->userService->getAllUsers($request);
    }

    /**
     * Create User
     *
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        return response()->json($user, Response::HTTP_CREATED);
    }

    /**
     * Show User
     *
     * Display the specified user.
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Update User
     *
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->userService->updateUser($user, $request->validated());
        return response()->json($user->fresh());
    }

    /**
     * Delete User
     *
     * Remove the specified user from storage.
     */
    public function destroy(User $user): Response
    {
        $this->userService->deleteUser($user);
        return response()->noContent(); // Returns a 204 No Content response
    }
}
