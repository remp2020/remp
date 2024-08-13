<?php

namespace Remp\CampaignModule\Http\Controllers;

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
            'html' => view('campaign::auth.error', [
                'message' => $message,
            ]),
            'json' => [
                'message' => $message,
            ],
        ]);
    }
}
