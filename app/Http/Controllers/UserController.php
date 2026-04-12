<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    // public function index(Request $request): Response
    // {

    //     logger()->info('users.index request', [
    //         'sort_field' => $request->input('sort_field'),
    //         'sort_order' => $request->input('sort_order'),
    //         'all' => $request->all(),
    //     ]);

    //     $perPage = (int) ($request->input('per_page', 10));
    //     $page = (int) ($request->input('page', 1));

    //     $sortField = $request->input('sort_field', 'id');
    //     $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

    //     $filters = json_decode($request->input('filters', '{}'), true) ?? [];

    //     $query = User::query()->select([
    //         'id',
    //         'name',
    //         'email',
    //         // 'status',
    //         // 'country',
    //         'created_at',
    //     ]);

    //     // global search
    //     $global = data_get($filters, 'global.value');
    //     if (!empty($global)) {
    //         $query->where(function ($q) use ($global) {
    //             $q->where('id', 'like', "%{$global}%")
    //                 ->orWhere('name', 'like', "%{$global}%")
    //                 ->orWhere('email', 'like', "%{$global}%");
    //         });
    //     }

    //     // column filters
    //     $idFilter = data_get($filters, 'id.constraints.0.value');
    //     if ($idFilter !== null && $idFilter !== '') {
    //         $query->where('id', $idFilter);
    //     }

    //     $nameFilter = data_get($filters, 'name.constraints.0.value');
    //     if (!empty($nameFilter)) {
    //         $query->where('name', 'like', "%{$nameFilter}%");
    //     }

    //     $emailFilter = data_get($filters, 'email.constraints.0.value');
    //     if (!empty($emailFilter)) {
    //         $query->where('email', 'like', "%{$emailFilter}%");
    //     }

    //     // $countryFilter = data_get($filters, 'country.constraints.0.value');
    //     // if (!empty($countryFilter)) {
    //     //     $query->where('country', 'like', "%{$countryFilter}%");
    //     // }

    //     $statusFilter = data_get($filters, 'status.constraints.0.value');
    //     if (is_array($statusFilter) && count($statusFilter)) {
    //         $query->whereIn('status', $statusFilter);
    //     } elseif (!empty($statusFilter)) {
    //         $query->where('status', $statusFilter);
    //     }

    //     // PrimeVue field protection
    //     $allowedSorts = ['id', 'name', 'email', 'status', 'created_at'];
    //     if (!in_array($sortField, $allowedSorts, true)) {
    //         Log::warning("Invalid sort field: {$sortField}. Defaulting to 'id'.");
    //         $sortField = 'id';
    //     }

    //     Log::debug('Sorting users', ['field' => $sortField, 'order' => $sortOrder]);

    //     $query->orderBy($sortField, $sortOrder);

    //     $users = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();


    //     logger()->info('users.index response', [
    //         'sortField' => $sortField,
    //         'sortOrder' => $sortOrder,
    //     ]);


    //     return Inertia::render('Users/Index', [
    //         'users' => $users->items(),
    //         'totalRecords' => $users->total(),
    //         'lazyParams' => [
    //             'page' => $users->currentPage(),
    //             'rows' => $users->perPage(),
    //             'sortField' => $sortField,
    //             'sortOrder' => $sortOrder === 'asc' ? 1 : -1,
    //             'filters' => $filters,
    //         ],
    //     ]);
    // }

    public function index()
    {
        // $users = User::select('id', 'name', 'email', 'status_id', 'created_at')->with('status')->orderBy('id', 'desc')->get();

        $users = User::with('status')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status_id' => $user->status_id,
                    'status_name' => optional($user->status)->name,
                    'status_color' => optional($user->status)->color,
                    'is_active' => optional($user->status)->is_active,
                    'created_at' => $user->created_at->toDateTimeString(),
                ];
            });

        // Log::info('Fetched users for index', json_decode($users->toJson(), true));

        return Inertia::render('Users/Index', [
            'users' => $users,
            'totalRecords' => $users->count(),
            'lazyParams' => [
                'page' => 1,
                'rows' => 10,
                'sortField' => 'id',
                'sortOrder' => -1,
                'filters' => [],
            ],
        ]);
    }

    public function table(Request $request)
    {
        Log::info('users.table request', [
            'sort_field' => $request->input('sort_field'),
            'sort_order' => $request->input('sort_order'),
            'all' => $request->all(),
        ]);

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);

        $sortField = $request->input('sort_field', 'id');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $filters = json_decode($request->input('filters', '{}'), true) ?? [];

        // $query = User::query()->select([
        //     'id',
        //     'name',
        //     'email',
        //     'status_id',
        // ]);

        $query = User::query()
            ->leftJoin('global_statuses', 'users.status_id', '=', 'global_statuses.id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.status_id',
                'global_statuses.name as status_name',
                'global_statuses.color as status_color',
            ]);

        // 🔍 Global search
        $global = data_get($filters, 'global.value');
        if (!empty($global)) {
$query->where(function ($q) use ($global) {
    $q->where('users.id', 'like', "%{$global}%")
      ->orWhere('users.name', 'like', "%{$global}%")
      ->orWhere('users.email', 'like', "%{$global}%")
      ->orWhere('users.status_id', 'like', "%{$global}%")
      ->orWhere('global_statuses.name', 'like', "%{$global}%");
});
        }

        // 📌 Column filters
        if ($name = data_get($filters, 'name.constraints.0.value')) {
            $query->where('users.name', 'like', "%{$name}%");
        }

        if ($email = data_get($filters, 'email.constraints.0.value')) {
            $query->where('users.email', 'like', "%{$email}%");
        }

        $statusFilter = data_get($filters, 'status_id.constraints.0.value');
        if (is_array($statusFilter) && count($statusFilter)) {
            $query->whereIn('users.status_id', $statusFilter);
        } elseif ($statusFilter !== null && $statusFilter !== '') {
            $query->where('users.status_id', $statusFilter);
        }

        // 🛡️ Safe sorting — map frontend field names to qualified column names
        $sortFieldMap = [
            'id'        => 'users.id',
            'name'      => 'users.name',
            'email'     => 'users.email',
            'status_id' => 'users.status_id',
        ];

        $sortField = $sortFieldMap[$sortField] ?? 'users.id';

        $query->orderBy($sortField, $sortOrder);

        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $users->items(),
            'total' => $users->total(),
            'lazyParams' => [
                'page' => $users->currentPage(),
                'rows' => $users->perPage(),
                'sortField' => $sortField,
                'sortOrder' => $sortOrder === 'asc' ? 1 : -1,
                'filters' => $filters,
            ],
        ]);
    }

    /** Bootstrap Table endpoint – GET /api/users */
    public function data(Request $request)
    {
        $limit  = max(1, min((int) $request->input('limit', 10), 100));
        $offset = (int) $request->input('offset', 0);
        $order  = in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc'])
                    ? strtolower($request->input('order', 'desc'))
                    : 'desc';
        $sort   = $request->input('sort', 'id');
        $search = $request->input('search');

        $sortMap = [
            'id'          => 'users.id',
            'name'        => 'users.name',
            'email'       => 'users.email',
            'status_name' => 'global_statuses.name',
        ];
        $sortCol = $sortMap[$sort] ?? 'users.id';

        $query = User::query()
            ->leftJoin('global_statuses', 'users.status_id', '=', 'global_statuses.id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.status_id',
                'global_statuses.name  as status_name',
                'global_statuses.color as status_color',
            ])
            ->with('roles:id,name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name',  'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('global_statuses.name', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortCol, $order);

        $total = $query->count();
        $rows  = $query->offset($offset)->limit($limit)->get()
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'status_id'    => $u->status_id,
                'status_name'  => $u->status_name  ?? '',
                'status_color' => $u->status_color ?? '',
                'roles'        => $u->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values(),
            ])
            ->values();

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function roles(User $user)
    {
        return response()->json($user->roles->pluck('id'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'status_id' => ['required', 'exists:global_statuses,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $data['password'] = bcrypt($data['password']);
        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        $user = User::create($data);
        $user->syncRoles($roleIds);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        Log::info("Updating user ID {$user->id}", ['request' => $request->all()]);
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'status_id' => ['required', 'exists:global_statuses,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        $user->update($data);
        $user->syncRoles($roleIds);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
