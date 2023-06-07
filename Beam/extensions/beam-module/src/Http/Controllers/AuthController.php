<?php

namespace Remp\BeamModule\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function logout()
    {
        Auth::logout();
        return redirect()->back();
    }

    public function error(Request $request)
    {
        $message = $request->get('error');
        return response()->format([
            'html' => view('beam::auth.error', [
                'message' => $message,
            ]),
            'json' => [
                'message' => $message,
            ],
        ]);
    }
}
