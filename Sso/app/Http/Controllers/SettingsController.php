<?php

namespace App\Http\Controllers;

class SettingsController extends Controller
{
    public function jwtwhitelist()
    {
        return response()->format([
            'html' => view('settings.jwtwhitelist', [
                'jwtwhitelist' => config('domain_whitelist')
            ])
        ]);
    }
}
