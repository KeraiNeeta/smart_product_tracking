<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEndCustomerApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->status !== 'active' || $user->role?->role_code !== 'END_CUSTOMER') {
            return response()->json([
                'success' => false,
                'message' => 'An active End Customer account is required.',
            ], 403);
        }

        return $next($request);
    }
}
