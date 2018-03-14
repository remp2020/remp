<?php

namespace App\Http\Controllers;

use Auth;

class AuthController extends Controller
{
    public function logout()
    {
        Auth::logout();
        return redirect()->back();
    }
}
