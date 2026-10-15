<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = DB::table('users')
            ->where('email', $request->email)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'user_id' => $user->user_id,
                'company_id' => $user->company_id,
                'role_id' => $user->role_id,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ],
        ]);
    }
}