<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Support\TableSearch;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class OperationsController extends Controller
{
    private const MODULES = [
        'inventory' => [
            'title' => 'Inventory ledger',
            'description' => 'Immutable device ownership and transfer history.',
            'query' => 'inventory',
            'columns' => ['device_uid', 'transaction_type', 'from_name', 'to_name', 'reference_no', 'performed_at'],
            'labels' => ['device_uid' => 'Device', 'transaction_type' => 'Transaction', 'from_name' => 'From', 'to_name' => 'To', 'reference_no' => 'Reference', 'performed_at' => 'Performed'],
        ],
        'events' => [
            'title' => 'Device events',
            'description' => 'Received device events and processing results.',
            'query' => 'events',
            'columns' => ['device_uid', 'event_type', 'event_occurred_at', 'server_received_at', 'severity', 'processing_status'],
            'labels' => ['device_uid' => 'Device', 'event_type' => 'Event', 'event_occurred_at' => 'Occurred', 'server_received_at' => 'Received', 'severity' => 'Severity', 'processing_status' => 'Processing'],
        ],
        'heartbeats' => [
            'title' => 'Device heartbeats',
            'description' => 'Latest connectivity and device health signals.',
            'query' => 'heartbeats',
            'columns' => ['device_uid', 'reported_at', 'received_at', 'battery_percent', 'signal_strength', 'firmware_version'],
            'labels' => ['device_uid' => 'Device', 'reported_at' => 'Reported', 'received_at' => 'Received', 'battery_percent' => 'Battery %', 'signal_strength' => 'Signal', 'firmware_version' => 'Firmware'],
        ],
        'commands' => [
            'title' => 'Device commands',
            'description' => 'Configuration and command delivery status.',
            'query' => 'commands',
            'columns' => ['device_uid', 'command_type', 'status', 'requested_at', 'sent_at', 'acknowledged_at', 'error_message'],
            'labels' => ['device_uid' => 'Device', 'command_type' => 'Command', 'status' => 'Status', 'requested_at' => 'Requested', 'sent_at' => 'Sent', 'acknowledged_at' => 'Acknowledged', 'error_message' => 'Error'],
        ],
        'notification-jobs' => [
            'title' => 'Notification queue',
            'description' => 'Queued deliveries, attempts, and retry times.',
            'query' => 'notificationJobs',
            'columns' => ['notification_job_id', 'alert_id', 'channel', 'recipient_masked', 'status', 'attempt_count', 'next_attempt_at'],
            'labels' => ['notification_job_id' => 'Job', 'alert_id' => 'Alert', 'channel' => 'Channel', 'recipient_masked' => 'Recipient', 'status' => 'Status', 'attempt_count' => 'Attempts', 'next_attempt_at' => 'Next attempt'],
        ],
        'notification-logs' => [
            'title' => 'Notification delivery logs',
            'description' => 'Provider delivery outcomes and sanitized response summaries.',
            'query' => 'notificationLogs',
            'columns' => ['notification_log_id', 'alert_id', 'channel', 'recipient_masked', 'provider_name', 'status', 'attempt_no', 'attempted_at', 'delivered_at', 'error_code'],
            'labels' => ['notification_log_id' => 'Log', 'alert_id' => 'Alert', 'channel' => 'Channel', 'recipient_masked' => 'Recipient', 'provider_name' => 'Provider', 'status' => 'Status', 'attempt_no' => 'Attempt', 'attempted_at' => 'Attempted', 'delivered_at' => 'Delivered', 'error_code' => 'Error code'],
        ],
        'push-tokens' => [
            'title' => 'Push registrations',
            'description' => 'Mobile push registration status. Token values are never displayed.',
            'query' => 'pushTokens',
            'columns' => ['push_token_id', 'full_name', 'platform', 'is_active', 'last_seen_at', 'created_at'],
            'labels' => ['push_token_id' => 'Registration', 'full_name' => 'User', 'platform' => 'Platform', 'is_active' => 'Active', 'last_seen_at' => 'Last seen', 'created_at' => 'Created'],
        ],
        'audit' => [
            'title' => 'Audit trail',
            'description' => 'Recorded administrative changes and device actions.',
            'query' => 'audit',
            'columns' => ['audit_log_id', 'actor_name', 'company_name', 'action', 'entity_type', 'entity_id', 'created_at'],
            'labels' => ['audit_log_id' => 'Audit #', 'actor_name' => 'Actor', 'company_name' => 'Company', 'action' => 'Action', 'entity_type' => 'Entity', 'entity_id' => 'Record', 'created_at' => 'When'],
        ],
    ];

    public function index(string $module): View
    {
        $definition = self::MODULES[$module] ?? throw new InvalidArgumentException('Unknown operations module.');
        $search = TableSearch::term(request());
        $records = DB::query()
            ->fromSub($this->query($definition['query']), 'search_rows')
            ->select('search_rows.*');
        TableSearch::apply(
            $records,
            $search,
            array_map(fn (string $column): string => 'search_rows.'.$column, $definition['columns']),
        );
        $sortColumn = match ($module) {
            'inventory' => 'performed_at',
            'events' => 'server_received_at',
            'heartbeats' => 'received_at',
            'commands' => 'requested_at',
            'notification-jobs' => 'created_at',
            'notification-logs' => 'attempted_at',
            'push-tokens' => 'last_seen_at',
            'audit' => 'created_at',
        };

        return view('master-admin.operations.index', [
            'module' => $module,
            'definition' => $definition,
            'records' => $records->orderBy('search_rows.'.$sortColumn, 'desc')->paginate(20)->withQueryString(),
        ]);
    }

    private function query(string $query)
    {
        return match ($query) {
            'inventory' => DB::table('device_inventory_transactions')
                ->join('devices', 'devices.device_id', '=', 'device_inventory_transactions.device_id')
                ->leftJoin('users as from_user', 'from_user.user_id', '=', 'device_inventory_transactions.from_user_id')
                ->leftJoin('users as to_user', 'to_user.user_id', '=', 'device_inventory_transactions.to_user_id')
                ->select('devices.device_uid', 'device_inventory_transactions.transaction_type', 'from_user.full_name as from_name', 'to_user.full_name as to_name', 'device_inventory_transactions.reference_no', 'device_inventory_transactions.performed_at')
                ->orderByDesc('device_inventory_transactions.performed_at'),
            'events' => DB::table('device_events')
                ->join('devices', 'devices.device_id', '=', 'device_events.device_id')
                ->select('devices.device_uid', 'device_events.event_type', 'device_events.event_occurred_at', 'device_events.server_received_at', 'device_events.severity', 'device_events.processing_status')
                ->orderByDesc('device_events.server_received_at'),
            'heartbeats' => DB::table('device_heartbeats')
                ->join('devices', 'devices.device_id', '=', 'device_heartbeats.device_id')
                ->select('devices.device_uid', 'device_heartbeats.reported_at', 'device_heartbeats.received_at', 'device_heartbeats.battery_percent', 'device_heartbeats.signal_strength', 'device_heartbeats.firmware_version')
                ->orderByDesc('device_heartbeats.received_at'),
            'commands' => DB::table('device_commands')
                ->join('devices', 'devices.device_id', '=', 'device_commands.device_id')
                ->select('devices.device_uid', 'device_commands.command_type', 'device_commands.status', 'device_commands.requested_at', 'device_commands.sent_at', 'device_commands.acknowledged_at', 'device_commands.error_message')
                ->orderByDesc('device_commands.requested_at'),
            'notificationJobs' => DB::table('notification_jobs')
                ->selectRaw("notification_job_id, alert_id, channel, CASE WHEN strpos(recipient, '@') > 0 THEN left(recipient, 1) || '***@' || split_part(recipient, '@', 2) WHEN length(recipient) > 4 THEN '***' || right(recipient, 4) ELSE '***' END as recipient_masked, status, attempt_count, next_attempt_at, created_at")
                ->orderByDesc('created_at'),
            'notificationLogs' => DB::table('notification_logs')
                ->select('notification_log_id', 'alert_id', 'channel', 'recipient_masked', 'provider_name', 'status', 'attempt_no', 'attempted_at', 'delivered_at', 'error_code')
                ->orderByDesc('attempted_at'),
            'pushTokens' => DB::table('push_tokens')
                ->join('users', 'users.user_id', '=', 'push_tokens.user_id')
                ->select('push_tokens.push_token_id', 'users.full_name', 'push_tokens.platform', 'push_tokens.is_active', 'push_tokens.last_seen_at', 'push_tokens.created_at')
                ->orderByDesc('push_tokens.last_seen_at'),
            'audit' => DB::table('audit_logs')
                ->leftJoin('users', 'users.user_id', '=', 'audit_logs.actor_user_id')
                ->leftJoin('companies', 'companies.company_id', '=', 'audit_logs.company_id')
                ->select('audit_logs.audit_log_id', 'users.full_name as actor_name', 'companies.company_name', 'audit_logs.action', 'audit_logs.entity_type', 'audit_logs.entity_id', 'audit_logs.created_at')
                ->orderByDesc('audit_logs.created_at'),
            default => throw new InvalidArgumentException('Unknown operations query.'),
        };
    }
}
