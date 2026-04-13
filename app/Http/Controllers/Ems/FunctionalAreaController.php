<?php

namespace App\Http\Controllers\Ems;

use App\Http\Controllers\Controller;
use App\Models\Ems\FunctionalArea;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FunctionalAreaController extends Controller
{
    public function index()
    {
        return Inertia::render('Fa/Index');
    }

    /** Bootstrap Table endpoint – GET /api/functional-areas */
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
            'id'          => 'functional_areas.id',
            'title'        => 'functional_areas.title',
            'status_name' => 'global_statuses.name',
            'created_at'  => 'functional_areas.created_at',
            'updated_at'  => 'functional_areas.updated_at',
        ];
        $sortCol = $sortMap[$sort] ?? 'functional_areas.id';

        $query = FunctionalArea::query()
            ->leftJoin('global_statuses', 'functional_areas.active_flag', '=', 'global_statuses.id')
            ->select([
                'functional_areas.id',
                'functional_areas.title',
                'functional_areas.active_flag',
                'functional_areas.created_at',
                'functional_areas.updated_at',
                'global_statuses.name  as status_name',
                'global_statuses.color as status_color',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('functional_areas.title', 'like', "%{$search}%")
                  ->orWhere('global_statuses.name', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortCol, $order);

        $total = $query->count();
        $rows  = $query->offset($offset)->limit($limit)->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'active_flag'  => $e->active_flag,
                'status_name'  => $e->status_name  ?? '',
                'status_color' => $e->status_color ?? '',
                'created_at'   => $e->created_at?->format('Y-m-d'),
                'updated_at'   => $e->updated_at?->format('Y-m-d'),
            ])
            ->values();

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /** GET /api/functional-areas/all – flat list for dropdowns */
    public function all()
    {
        return response()->json(
            FunctionalArea::orderBy('title')->get(['id', 'title'])
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        FunctionalArea::create($data);

        return back()->with('success', 'Functional Area created successfully.');
    }

    public function update(Request $request, FunctionalArea $functionalArea)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        $functionalArea->update($data);

        return back()->with('success', 'Functional Area updated successfully.');
    }

    public function destroy(FunctionalArea $functionalArea)
    {
        $functionalArea->delete();

        return response()->json(['message' => 'Functional Area deleted successfully.']);
    }
}
