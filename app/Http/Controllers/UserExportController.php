<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;

class UserExportController extends Controller
{
    public function export(Request $request)
    {
        $filters = json_decode($request->input('filters', '{}'), true) ?: [];
        $columns = json_decode($request->input('columns', '[]'), true) ?: [];

        return Excel::download(
            new UsersExport(
                filters: $filters,
                sortField: $request->input('sort_field', 'id'),
                sortOrder: $request->input('sort_order', 'desc'),
                columns: $columns
            ),
            'users.xlsx'
        );
    }
}