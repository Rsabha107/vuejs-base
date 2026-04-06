<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    //
    public function index(): Response
    {
        $users = User::select('id', 'name', 'email', 'created_at')->get();


        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);  
    }

    public function data(Request $request)
    {
        $query = User::query();

        // search
        if ($search = $request->search) {
            $query->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%");
        }

        $total = $query->count();

        $rows = $query
            ->offset($request->offset ?? 0)
            ->limit($request->limit ?? 10)
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }

    public function Login(Request $request): Response
    {
        return Inertia::render('MyAuth/Login');
    }
}
