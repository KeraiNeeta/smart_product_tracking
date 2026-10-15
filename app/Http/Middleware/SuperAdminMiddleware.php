<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('master-admin.login');
        }

        if (
            $user->status !== 'active' ||
            $user->deleted_at !== null
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('master-admin.login')
                ->withErrors([
                    'email' => 'You are not authorized to access Master Admin.',
                ]);
        }

        if ($user->role?->role_code !== 'SUPER_ADMIN') {
            return redirect()->route('portal.dashboard')
                ->withErrors(['access' => 'Master Admin access is restricted to Super Admins.']);
        }

        return $next($request);
    }
}