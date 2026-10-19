<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceMonitoringControl;
use App\Services\EndCustomerRegistrar;
use App\Services\PortalDeviceScope;
use App\Services\UserNotificationInbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EndUserController extends Controller
{
    public function register(Request $request, EndCustomerRegistrar $registrar): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:30', 'unique:users,mobile'],
            'serial_number' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $registrar->register($data);
        $token = $this->issueToken($user, $request);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => $this->userData($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::with('role')
            ->where('email', $data['email'])
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();
        if (!$user || $user->role?->role_code !== 'END_CUSTOMER' || !$this->passwordMatches($user, $data['password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($this->isLegacyBcrypt($user->password_hash)) {
            $user->password_hash = Hash::make($data['password']);
        }
        $user->last_login_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $this->userData($user),
                'token' => $this->issueToken($user, $request),
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    public function dashboard(Request $request, PortalDeviceScope $scope): JsonResponse
    {
        $user = $request->user();
        $device = $this->deviceQuery($scope, $user)->first();
        $alerts = DB::table('alerts')
            ->join('devices', 'devices.device_id', '=', 'alerts.device_id')
            ->whereIn('alerts.device_id', $scope->visibleDeviceIds($user))
            ->orderByDesc('alerts.triggered_at')
            ->limit(10)
            ->get([
                'alerts.alert_id',
                'devices.device_uid',
                'alerts.title',
                'alerts.severity',
                'alerts.status',
                'alerts.triggered_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->userData($user),
                'device' => $device,
                'open_alert_count' => DB::table('alerts')
                    ->whereIn('device_id', $scope->visibleDeviceIds($user))
                    ->whereIn('status', ['open', 'acknowledged'])
                    ->count(),
                'recent_alerts' => $alerts,
            ],
        ]);
    }

    public function device(Request $request, PortalDeviceScope $scope): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['device' => $this->deviceQuery($scope, $request->user())->first()],
        ]);
    }

    public function setMonitoringState(
        Request $request,
        PortalDeviceScope $scope,
        DeviceMonitoringControl $control,
    ): JsonResponse {
        $data = $request->validate([
            'state' => ['required', Rule::in(['ARMED', 'DISARMED'])],
        ]);
        $device = $this->deviceQuery($scope, $request->user())->first();
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device is assigned to this account.',
            ], 404);
        }

        $result = $control->requestState($request, $request->user(), (int) $device->device_id, $data['state']);

        return response()->json([
            'success' => true,
            'message' => $result['changed']
                ? 'The command has been queued. The device state updates after it confirms the change.'
                : 'This device is already '.$result['state'].'.',
            'data' => [
                'device_id' => (int) $device->device_id,
                'requested_state' => $result['state'],
                'command_status' => $result['changed'] ? 'pending' : null,
            ],
        ], $result['changed'] ? 202 : 200);
    }

    public function notifications(Request $request, UserNotificationInbox $inbox): JsonResponse
    {
        $user = $request->user();
        $notifications = $inbox->query($user)
            ->leftJoin('user_notification_reads as notification_reads', function ($join) use ($user): void {
                $join->on('notification_reads.notification_job_id', '=', 'notification_jobs.notification_job_id')
                    ->where('notification_reads.user_id', '=', $user->user_id);
            })
            ->orderByDesc('notification_jobs.created_at')
            ->limit(50)
            ->get([
                'notification_jobs.notification_job_id',
                'notification_jobs.channel',
                'notification_jobs.status as delivery_status',
                'notification_jobs.created_at',
                'notification_jobs.payload',
                'devices.device_uid',
                'notification_reads.read_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $inbox->unreadCount($user),
                'notifications' => $notifications,
            ],
        ]);
    }

    public function markNotificationsRead(UserNotificationInbox $inbox, Request $request): JsonResponse
    {
        $count = $inbox->markAllRead($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
            'data' => ['marked_read' => $count],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['user' => $this->userData($request->user())],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'mobile' => ['nullable', 'string', 'max:30'],
        ]);
        DB::table('users')->where('user_id', $user->user_id)->update([
            ...$data,
            'email_verified_at' => $data['email'] === $user->email ? $user->email_verified_at : null,
            'updated_at' => now(),
        ]);
        $user->refresh()->load('role');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => ['user' => $this->userData($user)],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if (!Hash::check($data['current_password'], $user->password_hash ?? '')) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect.',
                'errors' => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        DB::table('users')->where('user_id', $user->user_id)->update([
            'password_hash' => Hash::make($data['new_password']),
            'updated_at' => now(),
        ]);
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed. Please sign in again.',
        ]);
    }

    public function registerPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['ANDROID', 'IOS'])],
            'token' => ['required', 'string', 'max:4096'],
        ]);
        DB::table('push_tokens')->updateOrInsert(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->user_id,
                'platform' => $data['platform'],
                'is_active' => true,
                'last_seen_at' => now(),
                'created_at' => now(),
            ],
        );

        return response()->json(['success' => true, 'message' => 'Push token registered.'], 201);
    }

    public function deletePushToken(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:4096']]);
        DB::table('push_tokens')
            ->where('user_id', $request->user()->user_id)
            ->where('token', $data['token'])
            ->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Push token deactivated.']);
    }

    private function deviceQuery(PortalDeviceScope $scope, User $user)
    {
        $latestConfigurations = DB::table('device_configurations')
            ->select('device_id')
            ->selectRaw('MAX(config_version) as latest_config_version')
            ->groupBy('device_id');

        return $scope->visibleDevices($user)
            ->leftJoinSub($latestConfigurations, 'latest_configurations', function ($join): void {
                $join->on('latest_configurations.device_id', '=', 'devices.device_id');
            })
            ->leftJoin('device_configurations as current_configuration', function ($join): void {
                $join->on('current_configuration.device_id', '=', 'devices.device_id')
                    ->on('current_configuration.config_version', '=', 'latest_configurations.latest_config_version');
            })
            ->addSelect(DB::raw("COALESCE(current_configuration.manual_override, current_configuration.monitoring_mode, 'DISARMED') as monitoring_state"))
            ->addSelect([
                'command_pending' => DB::table('device_commands')
                    ->selectRaw('1')
                    ->whereColumn('device_commands.device_id', 'devices.device_id')
                    ->whereIn('device_commands.status', ['pending', 'sent'])
                    ->where(function ($query): void {
                        $query->where('device_commands.expires_at', '>', now())
                            ->orWhere(function ($legacyQuery): void {
                                $legacyQuery->whereNull('device_commands.expires_at')
                                    ->where('device_commands.requested_at', '>', now()->subMinutes(5));
                            });
                    })
                    ->limit(1),
            ])
            ->orderBy('devices.device_uid');
    }

    private function issueToken(User $user, Request $request): string
    {
        $deviceName = $request->string('device_name')->trim()->limit(120)->toString();
        $token = $user->createToken(
            $deviceName !== '' ? $deviceName : 'android-app',
            ['end-user'],
            now()->addDays(30),
        );

        return $token->plainTextToken;
    }

    private function userData(User $user): array
    {
        $user->loadMissing('role');

        return [
            'user_id' => (int) $user->user_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role' => $user->role?->role_code,
        ];
    }

    private function passwordMatches(User $user, string $password): bool
    {
        return $this->isLegacyBcrypt($user->password_hash)
            ? password_verify($password, $user->password_hash)
            : Hash::check($password, $user->password_hash ?? '');
    }

    private function isLegacyBcrypt(?string $passwordHash): bool
    {
        return is_string($passwordHash)
            && str_starts_with($passwordHash, '$2a$')
            && password_get_info($passwordHash)['algoName'] === 'unknown';
    }
}
