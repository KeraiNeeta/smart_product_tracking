<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $companies = DB::table('companies')
            ->select('companies.*')
            ->selectSub(fn ($query) => $query->from('users')->whereColumn('users.company_id', 'companies.company_id')->whereNull('users.deleted_at')->selectRaw('count(*)'), 'users_count')
            ->selectSub(fn ($query) => $query->from('devices')->whereColumn('devices.company_id', 'companies.company_id')->whereNull('devices.deleted_at')->selectRaw('count(*)'), 'devices_count')
            ->selectSub(fn ($query) => $query->from('shops')->whereColumn('shops.company_id', 'companies.company_id')->selectRaw('count(*)'), 'shops_count')
            ->selectSub(fn ($query) => $query->from('device_inventory_transactions')->whereColumn('device_inventory_transactions.from_company_id', 'companies.company_id')->orWhereColumn('device_inventory_transactions.to_company_id', 'companies.company_id')->selectRaw('count(*)'), 'inventory_count')
            ->selectSub(fn ($query) => $query->from('notification_rules')->whereColumn('notification_rules.company_id', 'companies.company_id')->selectRaw('count(*)'), 'notification_rules_count')
            ->selectSub(fn ($query) => $query->from('audit_logs')->whereColumn('audit_logs.company_id', 'companies.company_id')->selectRaw('count(*)'), 'audit_logs_count');
        TableSearch::apply($companies, $search, [
            'companies.company_code', 'companies.company_name', 'companies.legal_name',
            'companies.email', 'companies.phone', 'companies.status',
        ]);

        return view('master-admin.companies.index', [
            'companies' => $companies->orderBy('company_name')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('master-admin.companies.form-page', ['company' => null]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $companyId = DB::table('companies')->insertGetId($data, 'company_id');
        $audit->record($request, 'created', 'company', $companyId, after: $data);

        return redirect()->route('master-admin.companies.index')
            ->with('status', 'Company created successfully.');
    }

    public function edit(int $company): View
    {
        return view('master-admin.companies.form-page', [
            'company' => DB::table('companies')->where('company_id', $company)->firstOrFail(),
        ]);
    }

    public function update(Request $request, int $company, AdminAuditLogger $audit): RedirectResponse
    {
        $current = DB::table('companies')->where('company_id', $company)->firstOrFail();
        $data = $this->validated($request, $company);
        $data['updated_at'] = now();

        DB::table('companies')->where('company_id', $company)->update($data);
        $audit->record($request, 'updated', 'company', $company, (array) $current, $data, $company);

        return redirect()->route('master-admin.companies.index')
            ->with('status', 'Company updated successfully.');
    }

    public function destroy(Request $request, int $company, AdminAuditLogger $audit): RedirectResponse
    {
        $record = DB::table('companies')->where('company_id', $company)->firstOrFail();
        $inUse = DB::table('users')->where('company_id', $company)->exists()
            || DB::table('devices')->where('company_id', $company)->exists()
            || DB::table('shops')->where('company_id', $company)->exists()
            || DB::table('device_inventory_transactions')->where('from_company_id', $company)->orWhere('to_company_id', $company)->exists()
            || DB::table('notification_rules')->where('company_id', $company)->exists()
            || DB::table('audit_logs')->where('company_id', $company)->exists();

        if ($inUse) {
            return back()->withErrors(['company' => 'A company with users, devices or shops cannot be deleted.']);
        }

        DB::table('companies')->where('company_id', $company)->delete();
        $audit->record($request, 'deleted', 'company', $company, (array) $record);

        return redirect()->route('master-admin.companies.index')
            ->with('status', 'Company deleted successfully.');
    }

    private function validated(Request $request, ?int $companyId = null): array
    {
        return $request->validate([
            'company_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'company_code')->ignore($companyId, 'company_id'),
            ],
            'company_name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:250'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
    }
}
