<?php

namespace Database\Seeders;

use App\Services\AlertNotificationQueue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ShopGuardDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'ShopGuardDemo!2026';

    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new RuntimeException('Shop Guard demo data can only be seeded in the local environment.');
        }

        DB::transaction(function (): void {
            $roles = DB::table('roles')->pluck('role_id', 'role_code');
            foreach (['SUPER_ADMIN', 'COMPANY_ADMIN', 'DEALER', 'SUB_DEALER', 'END_CUSTOMER', 'SUPPORT_OPERATOR'] as $roleCode) {
                if (!$roles->has($roleCode)) {
                    throw new RuntimeException('Required system role is missing: '.$roleCode);
                }
            }

            $now = now();
            DB::table('companies')->updateOrInsert(
                ['company_code' => 'SG_DEMO'],
                [
                    'company_name' => 'Shop Guard Demo Company',
                    'legal_name' => 'Shop Guard Demo Company',
                    'email' => 'company@demo.shopguard.local',
                    'phone' => '+15550001000',
                    'status' => 'active',
                    'updated_at' => $now,
                ],
            );
            $companyId = (int) DB::table('companies')->where('company_code', 'SG_DEMO')->value('company_id');

            $userIds = [];
            $demoMobiles = [
                'superadmin' => '+15550001001',
                'company-admin' => '+15550001002',
                'dealer' => '+15550001003',
                'sub-dealer' => '+15550001004',
                'shop-user' => '+15550001005',
                'support' => '+15550001006',
                'sub-dealer-2' => '+15550001007',
                'shop-user-2' => '+15550001008',
                'shop-user-direct' => '+15550001009',
            ];
            $users = [
                'superadmin' => ['Shop Guard Demo Super Admin', 'SUPER_ADMIN', null, null],
                'company-admin' => ['Shop Guard Demo Company Admin', 'COMPANY_ADMIN', $companyId, null],
                'dealer' => ['Shop Guard Demo Dealer', 'DEALER', $companyId, 'company-admin'],
                'sub-dealer' => ['Shop Guard Demo Sub-Dealer', 'SUB_DEALER', $companyId, 'dealer'],
                'shop-user' => ['Shop Guard Demo Shop User', 'END_CUSTOMER', $companyId, 'sub-dealer'],
                'sub-dealer-2' => ['Shop Guard Demo Sub-Dealer 2', 'SUB_DEALER', $companyId, 'dealer'],
                'shop-user-2' => ['Shop Guard Demo Shop User 2', 'END_CUSTOMER', $companyId, 'sub-dealer-2'],
                'shop-user-direct' => ['Shop Guard Demo Direct Shop User', 'END_CUSTOMER', $companyId, 'dealer'],
                'support' => ['Shop Guard Demo Support', 'SUPPORT_OPERATOR', null, null],
            ];

            foreach ($users as $key => [$name, $roleCode, $userCompanyId, $parentKey]) {
                $email = $key.'@demo.shopguard.local';
                DB::table('users')->updateOrInsert(
                    ['email' => $email],
                    [
                        'company_id' => $userCompanyId,
                        'parent_user_id' => $parentKey ? ($userIds[$parentKey] ?? null) : null,
                        'role_id' => $roles[$roleCode],
                        'full_name' => $name,
                        'mobile' => $demoMobiles[$key],
                        'password_hash' => Hash::make(self::DEMO_PASSWORD),
                        'status' => 'active',
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ],
                );
                $userIds[$key] = (int) DB::table('users')->where('email', $email)->value('user_id');
            }

            DB::table('shops')->updateOrInsert(
                ['shop_code' => 'SG_DEMO_SHOP'],
                [
                    'company_id' => $companyId,
                    'customer_user_id' => $userIds['shop-user'],
                    'shop_name' => 'Shop Guard Demo Store',
                    'address_line1' => '100 Demo Market Road',
                    'city' => 'Demo City',
                    'state' => 'Demo State',
                    'postal_code' => '000001',
                    'timezone' => 'Asia/Kolkata',
                    'contact_name' => 'Shop Guard Demo Shop User',
                    'contact_mobile' => '+15550001005',
                    'status' => 'active',
                    'updated_at' => $now,
                ],
            );
            $shopId = (int) DB::table('shops')->where('shop_code', 'SG_DEMO_SHOP')->value('shop_id');
            $branchShopIds = ['shop-user' => $shopId];
            foreach (['shop-user-2' => 'SG_DEMO_SHOP_SUB2', 'shop-user-direct' => 'SG_DEMO_SHOP_DIRECT'] as $userKey => $shopCode) {
                DB::table('shops')->updateOrInsert(
                    ['shop_code' => $shopCode],
                    [
                        'company_id' => $companyId,
                        'customer_user_id' => $userIds[$userKey],
                        'shop_name' => $userKey === 'shop-user-2' ? 'Shop Guard Demo Branch Two' : 'Shop Guard Demo Direct Store',
                        'address_line1' => $userKey === 'shop-user-2' ? '200 Demo Market Road' : '300 Demo Market Road',
                        'city' => 'Demo City',
                        'state' => 'Demo State',
                        'postal_code' => '000002',
                        'timezone' => 'Asia/Kolkata',
                        'contact_name' => $userIds[$userKey] ? DB::table('users')->where('user_id', $userIds[$userKey])->value('full_name') : null,
                        'contact_mobile' => DB::table('users')->where('user_id', $userIds[$userKey])->value('mobile'),
                        'status' => 'active',
                        'updated_at' => $now,
                    ],
                );
                $branchShopIds[$userKey] = (int) DB::table('shops')->where('shop_code', $shopCode)->value('shop_id');
            }

            DB::table('devices')->updateOrInsert(
                ['device_uid' => 'SG-DEMO-SHUTTER-01'],
                [
                    'serial_number' => 'SG-DEMO-SERIAL-001',
                    'model' => 'SG Shutter Sensor',
                    'firmware_version' => 'demo-1.0',
                    'company_id' => $companyId,
                    'manufacturing_batch' => 'SG-DEMO-BATCH',
                    'lifecycle_status' => 'active',
                    'connectivity_status' => 'online',
                    'last_seen_at' => $now,
                    'installed_at' => $now,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ],
            );
            $assignedDeviceId = (int) DB::table('devices')->where('device_uid', 'SG-DEMO-SHUTTER-01')->value('device_id');

            DB::table('devices')->updateOrInsert(
                ['device_uid' => 'SG-DEMO-SHUTTER-STOCK'],
                [
                    'serial_number' => 'SG-DEMO-SERIAL-002',
                    'model' => 'SG Shutter Sensor',
                    'firmware_version' => 'demo-1.0',
                    'company_id' => $companyId,
                    'manufacturing_batch' => 'SG-DEMO-BATCH',
                    'lifecycle_status' => 'in_stock',
                    'connectivity_status' => 'unknown',
                    'deleted_at' => null,
                    'updated_at' => $now,
                ],
            );

            DB::table('device_assignments')->updateOrInsert(
                ['device_id' => $assignedDeviceId, 'is_current' => true],
                [
                    'owner_user_id' => $userIds['shop-user'],
                    'shop_id' => $shopId,
                    'assigned_by' => $userIds['company-admin'],
                    'assigned_at' => $now,
                    'unassigned_at' => null,
                    'notes' => 'Shop Guard demo assignment',
                ],
            );

            if (!DB::table('device_inventory_transactions')->where('reference_no', 'SG-DEMO-TRANSFER-001')->exists()) {
                DB::table('device_inventory_transactions')->insert([
                    'device_id' => $assignedDeviceId,
                    'transaction_type' => 'TRANSFER',
                    'to_user_id' => $userIds['shop-user'],
                    'to_company_id' => $companyId,
                    'reference_no' => 'SG-DEMO-TRANSFER-001',
                    'notes' => 'Demo device assigned to demo shop',
                    'performed_by' => $userIds['company-admin'],
                    'performed_at' => $now,
                    'metadata' => json_encode(['shop_id' => $shopId, 'demo' => true], JSON_THROW_ON_ERROR),
                ]);
            }

            DB::table('device_configurations')->updateOrInsert(
                ['device_id' => $assignedDeviceId, 'config_version' => 1],
                [
                    'monitoring_mode' => 'DISARMED',
                    'manual_override' => null,
                    'schedule_enabled' => false,
                    'timezone' => 'Asia/Kolkata',
                    'hooter_enabled' => true,
                    'alert_on_open' => true,
                    'alert_on_close' => true,
                    'effective_from' => $now,
                    'updated_by' => $userIds['company-admin'],
                    'updated_at' => $now,
                    'config_payload' => json_encode(['demo' => true], JSON_THROW_ON_ERROR),
                ],
            );

            DB::table('device_schedules')->updateOrInsert(
                ['device_id' => $assignedDeviceId, 'schedule_name' => 'SG Demo Night Schedule'],
                [
                    'is_enabled' => true,
                    'start_time' => '22:00',
                    'end_time' => '06:00',
                    'weekdays' => '{1,2,3,4,5,6,7}',
                    'timezone' => 'Asia/Kolkata',
                    'priority' => 100,
                    'created_by' => $userIds['company-admin'],
                    'updated_by' => $userIds['company-admin'],
                    'updated_at' => $now,
                ],
            );

            if (!DB::table('device_heartbeats')->where('device_id', $assignedDeviceId)->where('firmware_version', 'demo-1.0')->exists()) {
                DB::table('device_heartbeats')->insert([
                    'device_id' => $assignedDeviceId,
                    'reported_at' => $now,
                    'received_at' => $now,
                    'battery_percent' => 96,
                    'signal_strength' => -48,
                    'firmware_version' => 'demo-1.0',
                    'connectivity_details' => json_encode(['demo' => true], JSON_THROW_ON_ERROR),
                ]);
            }

            if (!DB::table('device_commands')->where('device_id', $assignedDeviceId)->whereRaw("requested_payload->>'demo_key' = ?", ['SG-DEMO-SYNC'])->exists()) {
                DB::table('device_commands')->insert([
                    'device_id' => $assignedDeviceId,
                    'command_type' => 'SYNC_CONFIG',
                    'requested_payload' => json_encode(['demo_key' => 'SG-DEMO-SYNC', 'config_version' => 1], JSON_THROW_ON_ERROR),
                    'status' => 'pending',
                    'requested_by' => $userIds['company-admin'],
                    'requested_at' => $now,
                ]);
            }

            foreach (['ARMED', 'DISARMED', 'SHUTTER_OPEN'] as $eventType) {
                foreach (['EMAIL', 'PUSH'] as $channel) {
                    DB::table('notification_rules')->updateOrInsert(
                        [
                            'company_id' => $companyId,
                            'user_id' => null,
                            'device_id' => $assignedDeviceId,
                            'alert_type' => $eventType,
                            'channel' => $channel,
                        ],
                        [
                            'is_enabled' => true,
                            'recipient_value' => null,
                            'priority' => 10,
                            'created_by' => $userIds['company-admin'],
                            'updated_at' => $now,
                        ],
                    );
                }
            }

            foreach (['company-admin', 'dealer', 'sub-dealer', 'shop-user'] as $key) {
                DB::table('push_tokens')->updateOrInsert(
                    ['token' => 'SG-DEMO-PUSH-TOKEN-'.$userIds[$key]],
                    [
                        'user_id' => $userIds[$key],
                        'platform' => 'ANDROID',
                        'is_active' => true,
                        'last_seen_at' => $now,
                    ],
                );
            }

            $notificationQueue = app(AlertNotificationQueue::class);
            foreach (['ARMED', 'DISARMED', 'SHUTTER_OPEN'] as $eventType) {
                $eventId = DB::table('device_events')
                    ->where('device_id', $assignedDeviceId)
                    ->where('event_type', $eventType)
                    ->whereRaw("payload->>'demo_key' = ?", ['SG-DEMO-'.$eventType])
                    ->value('event_id');
                if (!$eventId) {
                    $eventId = DB::table('device_events')->insertGetId([
                        'device_id' => $assignedDeviceId,
                        'event_type' => $eventType,
                        'event_occurred_at' => $now,
                        'server_received_at' => $now,
                        'shutter_state' => $eventType === 'SHUTTER_OPEN' ? 'OPEN' : null,
                        'monitoring_state' => in_array($eventType, ['ARMED', 'DISARMED'], true) ? $eventType : 'ARMED',
                        'severity' => $eventType === 'SHUTTER_OPEN' ? 'warning' : 'info',
                        'payload' => json_encode(['demo_key' => 'SG-DEMO-'.$eventType], JSON_THROW_ON_ERROR),
                        'processing_status' => 'processed',
                    ], 'event_id');
                }

                $alertId = DB::table('alerts')
                    ->where('source_event_id', $eventId)
                    ->value('alert_id');
                if (!$alertId) {
                    $alertId = DB::table('alerts')->insertGetId([
                        'device_id' => $assignedDeviceId,
                        'shop_id' => $shopId,
                        'source_event_id' => $eventId,
                        'alert_type' => $eventType,
                        'severity' => $eventType === 'SHUTTER_OPEN' ? 'warning' : 'info',
                        'title' => match ($eventType) {
                            'ARMED' => 'Shutter armed',
                            'DISARMED' => 'Shutter disarmed',
                            default => 'Demo shutter opened',
                        },
                        'message' => 'Demo event for '.$eventType.' on SG-DEMO-SHUTTER-01.',
                        'status' => $eventType === 'SHUTTER_OPEN' ? 'open' : 'resolved',
                        'triggered_at' => $now,
                        'context' => json_encode(['demo' => true], JSON_THROW_ON_ERROR),
                    ], 'alert_id');
                }

                if (!DB::table('notification_jobs')->where('alert_id', $alertId)->exists()) {
                    $notificationQueue->queueForCompanyUsers(
                        (int) $alertId,
                        $assignedDeviceId,
                        $companyId,
                        $eventType,
                        [
                            'title' => match ($eventType) {
                                'ARMED' => 'Shutter armed',
                                'DISARMED' => 'Shutter disarmed',
                                default => 'Shutter open',
                            },
                            'message' => 'Demo shutter event '.$eventType.' for SG-DEMO-SHUTTER-01.',
                        ],
                    );
                }

                $demoEmailJob = DB::table('notification_jobs')
                    ->where('alert_id', $alertId)
                    ->where('channel', 'EMAIL')
                    ->first();
                if (
                    $demoEmailJob
                    && !DB::table('notification_logs')
                        ->where('notification_job_id', $demoEmailJob->notification_job_id)
                        ->where('provider_name', 'DEMO')
                        ->exists()
                ) {
                    DB::table('notification_logs')->insert([
                        'notification_job_id' => $demoEmailJob->notification_job_id,
                        'alert_id' => $alertId,
                        'channel' => 'EMAIL',
                        'recipient_masked' => 'demo-recipient@example.test',
                        'provider_name' => 'DEMO',
                        'status' => 'failed',
                        'attempt_no' => 1,
                        'request_summary' => json_encode([
                            'demo_simulation' => true,
                            'provider_called' => false,
                        ], JSON_THROW_ON_ERROR),
                        'response_summary' => json_encode([
                            'message' => 'Simulation only; no notification was sent.',
                        ], JSON_THROW_ON_ERROR),
                        'error_code' => 'DEMO_PROVIDER_DISABLED',
                        'error_message' => 'Demo log entry only. Configure a delivery provider to send notifications.',
                        'attempted_at' => $now,
                    ]);
                }
            }

            $this->seedAdditionalDevices(
                $companyId,
                $branchShopIds,
                $userIds,
                $now,
                $notificationQueue,
            );

            if (!DB::table('audit_logs')
                ->where('actor_user_id', $userIds['company-admin'])
                ->where('entity_type', 'demo')
                ->where('entity_id', 'SG-DEMO')
                ->exists()) {
                DB::table('audit_logs')->insert([
                    'actor_user_id' => $userIds['company-admin'],
                    'company_id' => $companyId,
                    'action' => 'demo_data_seeded',
                    'entity_type' => 'demo',
                    'entity_id' => 'SG-DEMO',
                    'after_data' => json_encode(['device_uid' => 'SG-DEMO-SHUTTER-01'], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                ]);
            }
        });

        $this->command?->info('Shop Guard demo data is ready. Login password for all demo accounts: '.self::DEMO_PASSWORD);
        $this->command?->table(
            ['Role', 'Login email'],
            [
                ['Super Admin', 'superadmin@demo.shopguard.local'],
                ['Company Admin', 'company-admin@demo.shopguard.local'],
                ['Dealer', 'dealer@demo.shopguard.local'],
                ['Sub-Dealer', 'sub-dealer@demo.shopguard.local'],
                ['Shop User / End Customer', 'shop-user@demo.shopguard.local'],
                ['Second Sub-Dealer', 'sub-dealer-2@demo.shopguard.local'],
                ['Second Sub-Dealer Shop User', 'shop-user-2@demo.shopguard.local'],
                ['Direct Dealer Shop User', 'shop-user-direct@demo.shopguard.local'],
                ['Support Operator', 'support@demo.shopguard.local'],
            ],
        );
    }

    private function seedAdditionalDevices(
        int $companyId,
        array $shopIds,
        array $userIds,
        \Illuminate\Support\Carbon $now,
        AlertNotificationQueue $notificationQueue,
    ): void {
        $eventTypes = ['ARMED', 'DISARMED', 'SHUTTER_OPEN', 'TAMPER', 'LOW_BATTERY', 'DEVICE_OFFLINE'];
        $assignments = [
            ['sub-dealer', null],
            ['shop-user', 'shop-user'],
            ['sub-dealer-2', null],
            ['shop-user-2', 'shop-user-2'],
            ['shop-user-direct', 'shop-user-direct'],
            ['sub-dealer-2', null],
            ['shop-user-2', 'shop-user-2'],
            ['shop-user-direct', 'shop-user-direct'],
        ];

        for ($number = 2; $number <= 11; $number++) {
            $uid = sprintf('SG-DEMO-SHUTTER-%02d', $number);
            $deviceId = (int) DB::table('devices')->where('device_uid', $uid)->value('device_id');
            $inStock = $number >= 10;
            $connectivity = match ($number % 4) {
                0 => 'offline',
                1 => 'unknown',
                default => 'online',
            };
            $deviceValues = [
                'serial_number' => sprintf('SG-DEMO-SERIAL-%03d', $number + 1),
                'model' => 'SG Shutter Sensor',
                'firmware_version' => 'demo-1.0',
                'company_id' => $companyId,
                'manufacturing_batch' => 'SG-DEMO-BATCH',
                'lifecycle_status' => $inStock ? 'in_stock' : 'active',
                'connectivity_status' => $inStock ? 'unknown' : $connectivity,
                'last_seen_at' => !$inStock && $connectivity === 'online' ? $now->copy()->subMinutes($number * 3) : null,
                'installed_at' => $inStock ? null : $now->copy()->subDays($number),
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            DB::table('devices')->updateOrInsert(['device_uid' => $uid], $deviceValues);
            $deviceId = $deviceId ?: (int) DB::table('devices')->where('device_uid', $uid)->value('device_id');

            DB::table('device_configurations')->updateOrInsert(
                ['device_id' => $deviceId, 'config_version' => 1],
                [
                    'monitoring_mode' => $number % 2 === 0 ? 'ARMED' : 'DISARMED',
                    'manual_override' => null,
                    'schedule_enabled' => false,
                    'timezone' => 'Asia/Kolkata',
                    'hooter_enabled' => true,
                    'alert_on_open' => true,
                    'alert_on_close' => true,
                    'effective_from' => $now->copy()->subDays($number),
                    'updated_by' => $userIds['company-admin'],
                    'updated_at' => $now,
                    'config_payload' => json_encode(['demo' => true], JSON_THROW_ON_ERROR),
                ],
            );

            if ($inStock) {
                continue;
            }

            [$ownerKey, $shopKey] = $assignments[($number - 2) % count($assignments)];
            DB::table('device_assignments')->updateOrInsert(
                ['device_id' => $deviceId, 'is_current' => true],
                [
                    'owner_user_id' => $userIds[$ownerKey],
                    'shop_id' => $shopKey === null ? null : $shopIds[$shopKey],
                    'assigned_by' => $userIds['company-admin'],
                    'assigned_at' => $now->copy()->subDays($number),
                    'unassigned_at' => null,
                    'notes' => 'Shop Guard multi-device demo assignment',
                ],
            );

            if (!DB::table('device_heartbeats')->where('device_id', $deviceId)->where('firmware_version', 'demo-1.0')->exists()) {
                DB::table('device_heartbeats')->insert([
                    'device_id' => $deviceId,
                    'reported_at' => $now->copy()->subMinutes($number * 3),
                    'received_at' => $now->copy()->subMinutes($number * 3),
                    'battery_percent' => max(15, 100 - ($number * 9)),
                    'signal_strength' => -40 - ($number * 4),
                    'firmware_version' => 'demo-1.0',
                    'connectivity_details' => json_encode(['demo' => true], JSON_THROW_ON_ERROR),
                ]);
            }

            foreach ($eventTypes as $eventType) {
                foreach (['EMAIL', 'PUSH'] as $channel) {
                    DB::table('notification_rules')->updateOrInsert(
                        [
                            'company_id' => $companyId,
                            'user_id' => null,
                            'device_id' => $deviceId,
                            'alert_type' => $eventType,
                            'channel' => $channel,
                        ],
                        [
                            'is_enabled' => true,
                            'recipient_value' => null,
                            'priority' => 10,
                            'created_by' => $userIds['company-admin'],
                            'updated_at' => $now,
                        ],
                    );
                }
            }

            $scenarioTypes = [
                $number % 2 === 0 ? 'ARMED' : 'DISARMED',
                $eventTypes[2 + (($number - 2) % 4)],
            ];

            foreach ($scenarioTypes as $eventIndex => $eventType) {
                $demoKey = sprintf('SG-DEMO-%02d-%s', $number, $eventType);
                $occurredAt = $now->copy()->subDays(($number + $eventIndex) % 7);
                $eventId = DB::table('device_events')
                    ->where('device_id', $deviceId)
                    ->whereRaw("payload->>'demo_key' = ?", [$demoKey])
                    ->value('event_id');
                $title = match ($eventType) {
                    'ARMED' => 'Shutter armed',
                    'DISARMED' => 'Shutter disarmed',
                    'SHUTTER_OPEN' => 'Shutter opened',
                    'TAMPER' => 'Tamper detected',
                    'LOW_BATTERY' => 'Device battery low',
                    default => 'Device offline',
                };

                if (!$eventId) {
                    $eventId = DB::table('device_events')->insertGetId([
                        'device_id' => $deviceId,
                        'event_type' => $eventType,
                        'event_occurred_at' => $occurredAt,
                        'server_received_at' => $occurredAt,
                        'shutter_state' => match ($eventType) {
                            'SHUTTER_OPEN', 'TAMPER' => 'OPEN',
                            default => 'CLOSED',
                        },
                        'monitoring_state' => in_array($eventType, ['ARMED', 'DISARMED'], true) ? $eventType : 'ARMED',
                        'severity' => in_array($eventType, ['ARMED', 'DISARMED'], true) ? 'info' : 'warning',
                        'payload' => json_encode(['demo_key' => $demoKey, 'demo' => true], JSON_THROW_ON_ERROR),
                        'processing_status' => 'processed',
                    ], 'event_id');
                }

                $alertId = DB::table('alerts')->where('source_event_id', $eventId)->value('alert_id');
                if (!$alertId) {
                    $alertId = DB::table('alerts')->insertGetId([
                        'device_id' => $deviceId,
                        'shop_id' => $shopId,
                        'source_event_id' => $eventId,
                        'alert_type' => $eventType,
                        'severity' => in_array($eventType, ['ARMED', 'DISARMED'], true) ? 'info' : 'warning',
                        'title' => $title,
                        'message' => $title.' scenario for '.$uid.'.',
                        'status' => in_array($eventType, ['ARMED', 'DISARMED'], true) ? 'resolved' : 'open',
                        'triggered_at' => $occurredAt,
                        'context' => json_encode(['demo' => true, 'demo_key' => $demoKey], JSON_THROW_ON_ERROR),
                    ], 'alert_id');
                }

                if (!DB::table('notification_jobs')->where('alert_id', $alertId)->exists()) {
                    $notificationQueue->queueForCompanyUsers(
                        (int) $alertId,
                        $deviceId,
                        $companyId,
                        $eventType,
                        [
                            'title' => $title,
                            'message' => 'Demo '.$eventType.' scenario for '.$uid.'.',
                        ],
                    );
                }
            }
        }
    }
}
