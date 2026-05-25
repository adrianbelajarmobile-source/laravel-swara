<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isAdmin = $user !== null && (string) optional($user->role)->name === 'admin';

        if (!$isAdmin) {
            return response()->json([
                'message' => 'Unauthorized',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
