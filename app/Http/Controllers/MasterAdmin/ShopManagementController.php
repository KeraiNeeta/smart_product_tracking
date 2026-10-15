<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShopManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $shops = DB::table('shops')
            ->leftJoin('companies', 'companies.company_id', '=', 'shops.company_id')
            ->join('users', 'users.user_id', '=', 'shops.customer_user_id')
            ->select('shops.*', 'companies.company_name', 'users.full_name as customer_name')
            ->selectSub(fn ($query) => $query->from('device_assignments')->whereColumn('device_assignments.shop_id', 'shops.shop_id')->selectRaw('count(*)'), 'assignment_count')
            ->selectSub(fn ($query) => $query->from('alerts')->whereColumn('alerts.shop_id', 'shops.shop_id')->selectRaw('count(*)'), 'alerts_count');
        TableSearch::apply($shops, $search, [
            'shops.shop_name', 'shops.shop_code', 'companies.company_name',
            'users.full_name', 'shops.city', 'shops.state', 'shops.postal_code',
            'shops.timezone', 'shops.status',
        ]);

        return view('master-admin.shops.index', [
            'shops' => $shops->orderBy('shops.shop_name')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('master-admin.shops.form-page', [
            ...$this->formOptions(),
            'shop' => null,
        ]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $this->validateCustomerCompany($data['customer_user_id'], $data['company_id'] ?? null);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $shopId = DB::table('shops')->insertGetId($data, 'shop_id');
        $audit->record($request, 'created', 'shop', $shopId, after: $data, companyId: $data['company_id'] ?? null);

        return redirect()->route('master-admin.shops.index')
            ->with('status', 'Shop created successfully.');
    }

    public function edit(int $shop): View
    {
        return view('master-admin.shops.form-page', [
            ...$this->formOptions(),
            'shop' => DB::table('shops')->where('shop_id', $shop)->firstOrFail(),
        ]);
    }

    public function update(Request $request, int $shop, AdminAuditLogger $audit): RedirectResponse
    {
        $current = DB::table('shops')->where('shop_id', $shop)->firstOrFail();
        $data = $this->validated($request, $shop);
        $this->validateCustomerCompany($data['customer_user_id'], $data['company_id'] ?? null);
        $data['updated_at'] = now();

        DB::table('shops')->where('shop_id', $shop)->update($data);
        $audit->record($request, 'updated', 'shop', $shop, (array) $current, $data, $data['company_id'] ?? null);

        return redirect()->route('master-admin.shops.index')
            ->with('status', 'Shop updated successfully.');
    }

    public function destroy(Request $request, int $shop, AdminAuditLogger $audit): RedirectResponse
    {
        $record = DB::table('shops')->where('shop_id', $shop)->firstOrFail();

        if (
            DB::table('device_assignments')->where('shop_id', $shop)->exists()
            || DB::table('alerts')->where('shop_id', $shop)->exists()
        ) {
            return back()->withErrors(['shop' => 'A shop referenced by device assignments or alerts cannot be deleted.']);
        }

        DB::table('shops')->where('shop_id', $shop)->delete();
        $audit->record($request, 'deleted', 'shop', $shop, (array) $record, companyId: $record->company_id);

        return redirect()->route('master-admin.shops.index')
            ->with('status', 'Shop deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'companies' => DB::table('companies')->orderBy('company_name')->get(),
            'customers' => DB::table('users')
                ->join('roles', 'roles.role_id', '=', 'users.role_id')
                ->where('roles.role_code', 'END_CUSTOMER')
                ->where('users.status', 'active')
                ->whereNull('users.deleted_at')
                ->orderBy('users.full_name')
                ->select('users.user_id', 'users.full_name', 'users.email', 'users.company_id')
                ->get(),
        ];
    }

    private function validated(Request $request, ?int $shopId = null): array
    {
        return $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,company_id'],
            'customer_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'user_id')->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')),
            ],
            'shop_name' => ['required', 'string', 'max:200'],
            'shop_code' => ['nullable', 'string', 'max:80', Rule::unique('shops', 'shop_code')->ignore($shopId, 'shop_id')],
            'address_line1' => ['nullable', 'string', 'max:250'],
            'address_line2' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'timezone'],
            'contact_name' => ['nullable', 'string', 'max:200'],
            'contact_mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function validateCustomerCompany(int $customerUserId, ?int $companyId): void
    {
        $customer = DB::table('users')
            ->join('roles', 'roles.role_id', '=', 'users.role_id')
            ->where('users.user_id', $customerUserId)
            ->where('roles.role_code', 'END_CUSTOMER')
            ->whereNull('users.deleted_at')
            ->select('users.company_id')
            ->first();

        if (!$customer) {
            throw ValidationException::withMessages([
                'customer_user_id' => 'Select an active user with the End Customer role.',
            ]);
        }

        if ($customer->company_id !== null && (int) $customer->company_id !== (int) $companyId) {
            throw ValidationException::withMessages([
                'company_id' => 'The selected company must match the customer account company.',
            ]);
        }
    }
}
