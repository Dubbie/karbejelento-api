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
    // DI via constructor
    public function __construct(private readonly UserService $userService) {}

    /**
     * Display the authenticated user's profile.
     */
    public function getProfile(Request $request): User
    {
        // The authenticated user is available directly from the request.
        return $request->user();
    }

    /**
     * Display a listing of the resource.
     * Corresponds to `@Get()`
     */
    public function index(Request $request)
    {
        return $this->userService->getAllUsers($request);
    }

    /**
     * Store a newly created resource in storage.
     * Corresponds to `@Post()`
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        return response()->json($user, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->userService->updateUser($user, $request->validated());
        return response()->json($user->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        $this->userService->deleteUser($user);
        return response()->noContent(); // Returns a 204 No Content response
    }
}
