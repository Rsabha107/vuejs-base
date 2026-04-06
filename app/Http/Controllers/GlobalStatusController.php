<?php

namespace App\Http\Controllers;

use App\Models\GlobalStatus;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GlobalStatusController extends Controller
{
    public function getStatuses()
    {
        $statuses = GlobalStatus::where('is_active', 1)->get();
        // Log::info('Retrieved active statuses', ['statuses' => $statuses]);
        return response()->json($statuses);
    }
}
