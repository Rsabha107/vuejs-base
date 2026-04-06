<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    //
    public function index(): Response
    {
        // $permissions = Permission::select('id', 'name', 'created_at', 'updated_at')->get();

        return Inertia::render('Security/Permissions/Index');
    }

    public function data(Request $request)
    {
        $limit  = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);
        $order = $request->input('order', 'asc');
        $sort = $request->input('sort', 'id');
        $search = $request->input('search');

        $limit = max(1, min($limit, 100)); // min=1, max=100


        $query = Permission::query();

        // search
        if ($search = $request->search) {
            $query->where('name', 'like', "%$search%");
        }

        $query->orderBy($sort, $order);

        // Log::debug('PermissionController@data', [
        //     'limit' => $limit,
        //     'offset' => $offset,
        //     'order' => $order,
        //     'sort' => $sort,
        //     'search' => $search,
        // ]);

        $total = $query->count();

        // $rows = $query
        //     ->offset($request->offset ?? 0)
        //     ->limit($request->limit ?? 10)
        //     ->get(['id', 'name', 'created_at', 'updated_at']);
        $rows = $query
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($op) {
                return [
                    'id' => $op->id,
                    'name' => $op->name,
                    'created_at' => $op->created_at?->toDateTimeString(),
                    'updated_at' => $op->updated_at?->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }

    public function destroy(Permission $permission)
    {
        try {
            $permission->delete();

            return back()->with('success', 'Permission updated successfully.');
            
            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed'
            ], 500);
        }
    }

    public function show(Permission $permission)
    {
        return response()->json([
            'id' => $permission->id,
            'name' => $permission->name,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
        ]);

        $permission->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Permission updated successfully.');

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Permission updated successfully.',
        //     'data' => $permission->fresh(),
        // ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return back()->with('success', 'Permission created successfully.');
    }
}
