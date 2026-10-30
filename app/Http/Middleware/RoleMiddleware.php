<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more roles as pipe-separated arguments, e.g.:
     *   ->middleware('role:organizer')
     *   ->middleware('role:organizer|admin')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Allowed roles (varargs, each may be a pipe-separated list)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Must be authenticated first
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Flatten any pipe-separated role lists into a single array
        $allowed = collect($roles)
            ->flatMap(fn (string $role) => explode('|', $role))
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->all();

        if (! in_array($user->role, $allowed, strict: true)) {
            abort(403, 'Unauthorized. Insufficient role.');
        }

        return $next($request);
    }
}
