<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserNotificationInbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, UserNotificationInbox $inbox): View|RedirectResponse
    {
        $user = $request->user()->loadMissing('role');
        $isAdmin = $request->routeIs('master-admin.*');
        if (!$isAdmin && $user->role?->role_code === 'SUPER_ADMIN') {
            return redirect()->route('master-admin.profile');
        }

        return view('account.profile', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'layout' => $isAdmin ? 'master-admin.layout' : 'portal.layout',
            'roleName' => $user->role?->role_name ?? 'Account',
            'unreadNotificationCount' => $isAdmin ? 0 : $inbox->unreadCount($user),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('role');
        if (!$request->routeIs('master-admin.*') && $user->role?->role_code === 'SUPER_ADMIN') {
            return redirect()->route('master-admin.profile');
        }
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
            'mobile' => ['nullable', 'string', 'max:30'],
        ]);

        DB::table('users')->where('user_id', $user->user_id)->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'email_verified_at' => $validated['email'] === $user->email ? $user->email_verified_at : null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('role');
        if (!$request->routeIs('master-admin.*') && $user->role?->role_code === 'SUPER_ADMIN') {
            return redirect()->route('master-admin.profile');
        }
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password_hash ?? '')) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        DB::table('users')->where('user_id', $user->user_id)->update([
            'password_hash' => Hash::make($validated['new_password']),
            'updated_at' => now(),
        ]);
        $request->session()->regenerate();

        return back()->with('status', 'Password changed successfully.');
    }
}
