<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\UserService;
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
    public function getProfile(Request $request)
    {
        // The authenticated user is available directly from the request.
        return UserResource::make($request->user()->load('manager'));
    }

    /**
     * List Users
     *
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = $this->userService->getAllUsers($request);

        return $this->paginatedResponse($users, UserResource::class);
    }

    /**
     * Create User
     *
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());
        $user->load('manager');
        return UserResource::make($user)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show User
     *
     * Display the specified user.
     */
    public function show(User $user)
    {
        return UserResource::make($user->load('manager'));
    }

    /**
     * Update User
     *
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());
        return UserResource::make($user->fresh()->load('manager'));
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
