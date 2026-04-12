<?php

namespace App\Http\Controllers\Ems;

use App\Http\Controllers\Controller;
use App\Models\Ems\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('Events/Index');
    }

    /** Bootstrap Table endpoint – GET /api/events */
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
            'id'          => 'events.id',
            'name'        => 'events.name',
            'status_name' => 'global_statuses.name',
            'created_at'  => 'events.created_at',
        ];
        $sortCol = $sortMap[$sort] ?? 'events.id';

        $query = Event::query()
            ->leftJoin('global_statuses', 'events.active_flag', '=', 'global_statuses.id')
            ->select([
                'events.id',
                'events.name',
                'events.event_logo',
                'events.active_flag',
                'events.created_at',
                'global_statuses.name  as status_name',
                'global_statuses.color as status_color',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('events.name', 'like', "%{$search}%")
                  ->orWhere('global_statuses.name', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortCol, $order);

        $total = $query->count();
        $rows  = $query->offset($offset)->limit($limit)->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'name'         => $e->name,
                'event_logo'   => $e->event_logo,
                'logo_url'     => $e->event_logo ? asset('storage/event-logos/' . $e->event_logo) : null,
                'active_flag'  => $e->active_flag,
                'status_name'  => $e->status_name  ?? '',
                'status_color' => $e->status_color ?? '',
                'created_at'   => $e->created_at?->format('Y-m-d'),
            ])
            ->values();

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        Event::create($data);

        return back()->with('success', 'Event created successfully.');
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'active_flag' => ['required', 'exists:global_statuses,id'],
        ]);

        $event->update($data);

        return back()->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return back()->with('success', 'Event deleted successfully.');
    }
}
