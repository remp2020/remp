<?php

namespace App\Http\Controllers;

use App\Console\Commands\ComputeAuthorsSegments;
use App\Console\Commands\CreateAuthorsSegments;
use App\Http\Requests\AuthorSegmentsRequest;
use App\Model\Config;
use App\Segment;
use App\SegmentGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Controller for testing author segments conditions, not shown in menu
 * @package App\Http\Controllers
 */
class AuthorSegmentsController extends Controller
{

    public function index()
    {
        return view('authors.segments.index');
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'code', 'created_at', 'updated_at',
            DB::raw('(SELECT COUNT(*) FROM segment_users WHERE segment_users.segment_id=segments.id) as users_count'),
            DB::raw('(SELECT COUNT(*) FROM segment_browsers WHERE segment_browsers.segment_id=segments.id) as browsers_count'),
            ];
        $segments = Segment::select($columns)
            ->where('segment_group_id', SegmentGroup::getByCode(SegmentGroup::CODE_AUTHORS_SEGMENTS)->id);

        return $datatables->of($segments)
            ->make(true);
    }

    public function test()
    {
        return view('authors.segments.test', [
            'default_min_views' => Config::loadByName(ComputeAuthorsSegments::CONFIG_MIN_VIEWS),
            'default_min_ratio' => Config::loadByName(ComputeAuthorsSegments::CONFIG_MIN_RATIO),
            'default_min_average_timespent' => Config::loadByName(ComputeAuthorsSegments::CONFIG_MIN_AVERAGE_TIMESPENT),
        ]);
    }

    public function validateForm(AuthorSegmentsRequest $request)
    {
        return response()->json();
    }

    public function compute(AuthorSegmentsRequest $request)
    {
        $email = $request->get('email');

        Artisan::queue(ComputeAuthorsSegments::COMMAND, [
            '--email' => $email,
            '--history' => (int) $request->get('history'),
            '--min_views' => $request->get('min_views'),
            '--min_average_timespent' => $request->get('min_average_timespent'),
            '--min_ratio' => $request->get('min_ratio'),
        ]);

        return view('authors.segments.test', [
            'results' => true,
            'email' => $email,
        ]);
    }
}
