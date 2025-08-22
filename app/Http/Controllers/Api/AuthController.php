<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validate the incoming request data
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Find the user by email (equivalent to usersService.findOneByEmail)
        $user = User::where('email', $request->email)->first();

        // 3. Validate the user and password (equivalent to authService.validateUser)
        // The Hash::check method is Laravel's equivalent of bcrypt.compare [1, 2, 3]
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Throw an exception that Laravel will automatically convert
            // to a 422 Unprocessable Entity response.
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        // 4. Generate the token (equivalent to authService.login)
        // Sanctum allows you to create tokens for a user. [7, 14]
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Return the exact same response structure
        return response()->json([
            'access_token' => $token,
        ]);
    }
}
