<?php

namespace App\Http\Middleware;

use App\Constants\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles A list of roles passed from the route definition.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // TEMPORARY DEBUGGING:
        // if ($user && $user->role === UserRole::MANAGER) {
        //     dd([
        //         'message' => 'Debugging Manager Role',
        //         'user_role' => $user->role,
        //         'allowed_roles' => $roles,
        //         'is_in_array' => in_array($user->role, $roles),
        //     ]);
        // }


        // If user is not logged in or has no role, deny access.
        if (! $user || ! $user->role) {
            abort(401, 'Unauthorized: No user found.');
        }

        // Check if the user's role is in the list of allowed roles for this route.
        if (! in_array($user->role, $roles)) {
            abort(403, 'You do not have the required role for this action.');
        }

        return $next($request);
    }
}
