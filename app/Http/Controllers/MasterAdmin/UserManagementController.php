<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $users = User::with('role')->orderBy('full_name');
        if ($search !== '') {
            $users->where(function ($query) use ($search): void {
                TableSearch::apply($query, $search, ['full_name', 'email', 'mobile', 'status']);
                $query->orWhereHas('role', fn ($roleQuery) => TableSearch::apply($roleQuery, $search, ['role_name', 'role_code']));
            });
        }

        return view('master-admin.users.index', [
            'users' => $users->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('master-admin.users.create', $this->formOptions());
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', 'exists:roles,role_id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,company_id'],
            'parent_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at'))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive', 'blocked'])],
        ]);

        $this->validateParentCompany($data['parent_user_id'] ?? null, $data['company_id'] ?? null);
        $password = $data['password'];
        unset($data['password']);

        $user = User::create([
            ...$data,
            'password_hash' => Hash::make($password),
            'created_by' => $request->user()->getAuthIdentifier(),
            'updated_by' => $request->user()->getAuthIdentifier(),
        ]);
        $after = $data;
        unset($after['password_hash']);
        $audit->record($request, 'created', 'user', $user->user_id, after: $after, companyId: $user->company_id);

        return redirect()->route('master-admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('master-admin.users.edit', [
            ...$this->formOptions($user->user_id),
            'managedUser' => $user,
        ]);
    }

    public function update(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'mobile' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', 'exists:roles,role_id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,company_id'],
            'parent_user_id' => ['nullable', 'integer', Rule::exists('users', 'user_id')->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at'))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive', 'blocked'])],
        ]);

        if (isset($data['parent_user_id']) && (int) $data['parent_user_id'] === (int) $user->user_id) {
            throw ValidationException::withMessages([
                'parent_user_id' => 'A user cannot be their own parent.',
            ]);
        }
        if (!empty($data['parent_user_id'])) {
            $createsCycle = DB::selectOne(
                'WITH RECURSIVE descendants AS (
                    SELECT user_id FROM users WHERE parent_user_id = ? AND deleted_at IS NULL
                    UNION
                    SELECT users.user_id FROM users
                    INNER JOIN descendants ON users.parent_user_id = descendants.user_id
                    WHERE users.deleted_at IS NULL
                )
                SELECT EXISTS (SELECT 1 FROM descendants WHERE user_id = ?) AS creates_cycle',
                [$user->user_id, $data['parent_user_id']],
            )->creates_cycle;

            if ($createsCycle) {
                throw ValidationException::withMessages([
                    'parent_user_id' => 'This parent assignment would create a user hierarchy cycle.',
                ]);
            }
        }
        $this->validateParentCompany($data['parent_user_id'] ?? null, $data['company_id'] ?? null);
        $before = $user->attributesToArray();
        unset($before['password_hash']);
        $newRole = Role::findOrFail($data['role_id']);
        $isActiveSuperAdmin = $user->status === 'active'
            && $user->role?->role_code === 'SUPER_ADMIN';
        $willRemainActiveSuperAdmin = $data['status'] === 'active'
            && $newRole->role_code === 'SUPER_ADMIN';

        if (
            $user->user_id === $request->user()->getAuthIdentifier()
            && !$willRemainActiveSuperAdmin
        ) {
            return back()->withInput()->withErrors([
                'role_id' => 'You cannot deactivate or change your own Super Admin access.',
            ]);
        }

        if ($isActiveSuperAdmin && !$willRemainActiveSuperAdmin) {
            $this->ensureAnotherActiveSuperAdminExists($user);
        }

        unset($data['password']);
        $user->fill([
            ...$data,
            'updated_by' => $request->user()->getAuthIdentifier(),
        ]);

        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->string('password')->toString());
        }

        $user->save();
        $after = $user->attributesToArray();
        unset($after['password_hash']);
        $audit->record($request, 'updated', 'user', $user->user_id, $before, $after, $user->company_id);

        return redirect()->route('master-admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        if ($user->user_id === $request->user()->getAuthIdentifier()) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->status === 'active' && $user->role?->role_code === 'SUPER_ADMIN') {
            $this->ensureAnotherActiveSuperAdminExists($user);
        }

        $before = $user->attributesToArray();
        unset($before['password_hash']);
        $user->delete();
        $audit->record($request, 'deleted', 'user', $user->user_id, $before, companyId: $user->company_id);

        return redirect()->route('master-admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    private function formOptions(?int $excludeUserId = null): array
    {
        return [
            'roles' => Role::orderBy('role_name')->get(),
            'companies' => DB::table('companies')->orderBy('company_name')->get(),
            'parents' => DB::table('users')
                ->join('roles', 'roles.role_id', '=', 'users.role_id')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->whereIn('roles.role_code', ['COMPANY_ADMIN', 'DEALER', 'SUB_DEALER'])
                ->when($excludeUserId, fn ($query) => $query->where('user_id', '!=', $excludeUserId))
                ->orderBy('full_name')
                ->get(['users.user_id', 'users.full_name', 'users.company_id', 'roles.role_name']),
        ];
    }

    private function validateParentCompany(?int $parentUserId, ?int $companyId): void
    {
        if (!$parentUserId) {
            return;
        }

        $parent = User::with('role')->findOrFail($parentUserId);
        if (!in_array($parent->role?->role_code, ['COMPANY_ADMIN', 'DEALER', 'SUB_DEALER'], true)) {
            throw ValidationException::withMessages([
                'parent_user_id' => 'Choose an active company administrator, dealer, or sub-dealer as the parent.',
            ]);
        }
        if ($parent->company_id !== null && (int) $parent->company_id !== (int) $companyId) {
            throw ValidationException::withMessages([
                'parent_user_id' => 'The parent user must belong to the same company.',
            ]);
        }
    }

    private function ensureAnotherActiveSuperAdminExists(User $user): void
    {
        $otherActiveAdmins = User::where('status', 'active')
            ->where('user_id', '!=', $user->user_id)
            ->whereHas('role', fn ($query) => $query->where('role_code', 'SUPER_ADMIN'))
            ->count();

        if ($otherActiveAdmins === 0) {
            throw ValidationException::withMessages([
                'user' => 'At least one other active Super Admin must remain.',
            ]);
        }
    }
}
