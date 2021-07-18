<?php

namespace Crater\Http\Controllers;

use Crater\Models\Setting;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $version = Setting::getSetting('version');

        return response()->json([
            'version' => $version,
        ]);
    }

    public function copyrights() {
        return response()->json([
            'languages' => config('crater.languages'),
            'app_name' => env('APP_NAME'),
            'year' => date('Y')
        ]);
    }
}
