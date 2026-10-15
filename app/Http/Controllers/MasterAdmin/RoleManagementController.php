<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AdminAuditLogger;
use App\Support\TableSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = TableSearch::term($request);
        $roles = Role::withCount([
            'users' => fn ($query) => $query->withTrashed(),
        ])->orderBy('role_name');
        TableSearch::apply($roles, $search, ['role_name', 'role_code', 'description']);

        return view('master-admin.roles.index', [
            'roles' => $roles->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('master-admin.roles.create');
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'role_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z][A-Z0-9_]*$/', 'unique:roles,role_code'],
            'role_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = Role::create($data + ['is_system_role' => false]);
        $audit->record($request, 'created', 'role', $role->role_id, after: $data);

        return redirect()->route('master-admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        return view('master-admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $role->attributesToArray();
        $role->update($data);
        $audit->record($request, 'updated', 'role', $role->role_id, $before, $data);

        return redirect()->route('master-admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role, AdminAuditLogger $audit): RedirectResponse
    {
        if ($role->is_system_role) {
            return back()->withErrors(['role' => 'System roles cannot be deleted.']);
        }

        if ($role->users()->withTrashed()->exists()) {
            return back()->withErrors(['role' => 'A role assigned to users cannot be deleted.']);
        }

        $before = $role->attributesToArray();
        $role->delete();
        $audit->record($request, 'deleted', 'role', $role->role_id, $before);

        return redirect()->route('master-admin.roles.index')
            ->with('status', 'Role deleted successfully.');
    }
}
