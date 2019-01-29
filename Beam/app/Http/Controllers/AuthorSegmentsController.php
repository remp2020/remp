<?php

namespace App\Http\Controllers;

use App\Console\Commands\CreateAuthorsSegments;
use App\Http\Requests\AuthorSegmentsRequest;
use Illuminate\Support\Facades\Artisan;

/**
 * Controller for testing author segments conditions, not shown in menu
 * @package App\Http\Controllers
 */
class AuthorSegmentsController extends Controller
{
    public function test()
    {
        return view('authors.segments.test');
    }

    public function validateForm(AuthorSegmentsRequest $request)
    {
        return response()->json();
    }

    public function compute(AuthorSegmentsRequest $request)
    {
        $email = $request->get('email');

        Artisan::queue(CreateAuthorsSegments::COMMAND, [
            'email' => $email,
            'min_views' => $request->get('min_views'),
            'min_average_timespent' => $request->get('min_average_timespent'),
            'min_ratio' => $request->get('min_ratio'),
            'history' => (int) $request->get('history')
        ]);

        return view('authors.segments.test', [
            'results' => true,
            'email' => $email,
        ]);
    }
}
