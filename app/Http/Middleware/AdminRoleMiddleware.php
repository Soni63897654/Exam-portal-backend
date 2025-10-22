<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'JWT Token missing or invalid.'
            ], 401);
        }

        $user = auth()->user();
        if ($user->role && $user->role->name === 'admin') {
            return $next($request);
        }
        return response()->json([
            'error' => 'Unauthorized.',
            'message' => ''
        ], 403);
    }
}
