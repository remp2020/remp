<?php

namespace App\Http\Controllers;

use App\Console\Commands\CreateAuthorsSegments;
use App\Mail\TestMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Class TestController
 * Temporary controller for testing author segments conditions, not visible in menu
 * TODO: delete after autor segment rules are specified
 * @package App\Http\Controllers
 */
class TestController extends Controller
{
    public function authorSegmentsTest()
    {
        return view('test.form');
    }

    public function showResults(Request $request)
    {
        $minimalViews = $request->get('min_views');
        $minimalAverageTimespent = $request->get('min_average_timespent');
        $minimalRatio = $request->get('min_ratio');
        $email = $request->get('email');
        $history = (int) $request->get('history');

        Artisan::queue(CreateAuthorsSegments::COMMAND, [
            'email' => $email,
            'min_views' => $minimalViews,
            'min_average_timespent' => $minimalAverageTimespent,
            'min_ratio' => $minimalRatio,
            'history' => $history
        ]);

        return view('test.form', [
            'results' => true
        ]);
    }
}
