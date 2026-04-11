<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Security/RolesPermissions/Index');
    }

    /** Paginated list of roles with their permissions – used by Bootstrap Table */
    public function data(Request $request)
    {
        $limit  = max(1, min((int) $request->input('limit', 10), 100));
        $offset = (int) $request->input('offset', 0);
        $order  = $request->input('order', 'asc');
        $sort   = $request->input('sort', 'id');
        $search = $request->input('search');

        $query = Role::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy($sort, $order);

        $total = $query->count();

        $rows = $query
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn($role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                'created_at'  => $role->created_at?->toDateTimeString(),
                'updated_at'  => $role->updated_at?->toDateTimeString(),
            ])
            ->values();

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /** All permissions (flat list) – used to populate the assign modal */
    public function allPermissions()
    {
        return response()->json(
            Permission::orderBy('name')->get(['id', 'name'])
        );
    }

    /** All roles (flat list) – used for the role selector in the assign modal */
    public function allRoles()
    {
        return response()->json(
            Role::orderBy('name')->get(['id', 'name'])
        );
    }

    /** Sync (replace) permissions for a role */
    public function syncPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids'   => 'array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role->syncPermissions($validated['permission_ids'] ?? []);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'role'    => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->fresh()->permissions->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
            ],
        ]);
    }

    /** Delete a role */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }
}
