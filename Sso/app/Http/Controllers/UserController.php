<?php

namespace App\Http\Controllers;

use App\Models\User;
use Tymon\JWTAuth\JWTAuth;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('users.index'),
        ]);
    }

    public function json(Datatables $dataTables)
    {
        $users = User::select(['id', 'email', 'name', 'created_at', 'updated_at'])->get();

        return $dataTables->of($users)
            ->addColumn('actions', function (User $user) {
                return [
                    'destroy' => auth()->id() !== $user->id ? route('users.destroy', $user) : null,
                ];
            })
            ->addColumn('action_methods', function (User $user) {
                return [
                    'destroy' => 'DELETE',
                ];
            })
            ->rawColumns(['actions'])
            ->setRowId('id')
            ->make(true);
    }

    public function destroy(JWTAuth $auth, User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->format([
                'html' => redirect(route('users.index'))->with('error', 'You cannot remove yourself'),
            ]);
        }

        $user->delete();

        return response()->format([
            'html' => redirect(route('users.index'))->with('success', sprintf('User [%s] was removed', $user->email)),
        ]);
    }
}
