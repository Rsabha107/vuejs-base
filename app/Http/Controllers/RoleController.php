<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    //
    public function index(): Response
    {
        // $roles = Role::select('id', 'name', 'created_at', 'updated_at')->get();

        return Inertia::render('Security/Roles/Index');
    }

    public function data(Request $request)
    {
        $limit  = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);
        $order = $request->input('order', 'asc');
        $sort = $request->input('sort', 'id');
        $search = $request->input('search');

        $limit = max(1, min($limit, 100)); // min=1, max=100

        $query = Role::query();

        // search
        if ($search = $request->search) {
            $query->where('name', 'like', "%$search%");
        }

        $query->orderBy($sort, $order);

        Log::debug('RoleController@data', [
            'limit' => $limit,
            'offset' => $offset,
            'order' => $order,
            'sort' => $sort,
            'search' => $search,
        ]);

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
}
