<?php

namespace App\Http\Controllers;

use Auth;
use Request;

class AuthController extends Controller
{
    public function logout()
    {
        Auth::logout();
        return redirect()->back();
    }

    public function error(Request $request)
    {
        return 'error during login: ' . $request->get('error');
    }

}
