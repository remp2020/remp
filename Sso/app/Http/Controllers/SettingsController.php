<?php

namespace App\Http\Controllers;

class SettingsController extends Controller
{
    public function jwtwhitelist()
    {
        return response()->format([
            'html' => view('settings.jwtwhitelist', [
                'jwtwhitelist' => env('JWT_EMAIL_PATTERN_WHITELIST')
            ])
        ]);
    }
}
