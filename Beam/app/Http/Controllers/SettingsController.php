<?php

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Resources\ConfigResource;
use App\Model\Config;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('settings.index', [
                'configs' => Config::unlocked()->get(),
            ]),
            'json' => ConfigResource::collection(Config::unlocked()->get()),
        ]);
    }

    public function update(Request $request)
    {
        $settings = $request->get('settings');
        foreach ($settings as $name => $value) {
            Config::where('locked', false)
                ->where('name', $name)
                ->update(['value' => $value]);
        }

        return response()->format([
            'html' => redirect(route('settings.index'))->with('success', 'Settings updated'),
            'json' => ConfigResource::collection(Config::unlocked()->get()),
        ]);
    }
}
