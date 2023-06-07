<?php

namespace Remp\BeamModule\Http\Controllers;

use Illuminate\Http\Request;
use Remp\BeamModule\Http\Resources\ConfigResource;
use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigCategory;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('beam::settings.index', [
                'configsByCategories' => Config::global()->with('configCategory')->get()->groupBy('configCategory.display_name'),
            ]),
            'json' => ConfigResource::collection(Config::get()),
        ]);
    }

    public function update(ConfigCategory $configCategory, Request $request)
    {
        $settings = $request->get('settings');

        $pairedRequest = $configCategory->getPairedRequestType($request);
        if ($pairedRequest !== $request) {
            $validator = Validator::make($settings, $pairedRequest->rules(), $pairedRequest->messages() ?? []);
            if ($validator->fails()) {
                return redirect($request->get('redirect_url') ?? route('settings.index'))
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        foreach ($settings as $name => $value) {
            Config::global()
                ->where('name', $name)
                ->update(['value' => $value]);
        }

        return response()->format([
            'html' => redirect($request->get('redirect_url') ?? route('settings.index'))->with('success', 'Settings updated'),
            'json' => ConfigResource::collection(Config::global()->get()),
        ]);
    }
}
