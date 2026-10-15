<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MasterAdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->destinationFor(Auth::user());
        }

        return view('auth.master-admin-login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return $this->destinationFor(Auth::user());
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('role')
            ->where('email', $credentials['email'])
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        $storedPasswordHash = $user?->password_hash;
        $isLegacyBcrypt = is_string($storedPasswordHash)
            && str_starts_with($storedPasswordHash, '$2a$')
            && password_get_info($storedPasswordHash)['algoName'] === 'unknown';
        $passwordMatches = $user
            && ($isLegacyBcrypt
                ? password_verify($credentials['password'], $storedPasswordHash)
                : Hash::check($credentials['password'], $storedPasswordHash ?? ''));

        if (!$user || !$user->role || !$passwordMatches) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($isLegacyBcrypt) {
            $user->password_hash = Hash::make($credentials['password']);
        }

        $user->last_login_at = now();
        $user->save();

        return $this->destinationFor($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('master-admin.login');
    }

    private function destinationFor(User $user)
    {
        return $user->role?->role_code === 'SUPER_ADMIN'
            ? redirect()->route('master-admin.dashboard')
            : redirect()->route('portal.dashboard');
    }
}