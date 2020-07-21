<?php

namespace App\Http\Controllers;

use App\Console\Commands\ComputeAuthorsSegments;
use App\Http\Requests\AuthorSegmentsTestRequest;
use App\Model\Config\ConfigCategory;
use App\Segment;
use App\SegmentGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class AuthorSegmentsController extends Controller
{
    public function index()
    {
        return view('authors.segments.index', [
            'authorSegmentsSettingsUrl' => ConfigCategory::getSettingsTabUrl(ConfigCategory::CODE_AUTHOR_SEGMENTS)
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'code', 'created_at',
            DB::raw('(SELECT COUNT(*) FROM segment_users WHERE segment_users.segment_id=segments.id) as users_count'),
            DB::raw('(SELECT COUNT(*) FROM segment_browsers WHERE segment_browsers.segment_id=segments.id) as browsers_count'),
            ];
        $segments = Segment::select($columns)
            ->where('segment_group_id', SegmentGroup::getByCode(SegmentGroup::CODE_AUTHORS_SEGMENTS)->id);

        return $datatables->of($segments)
            ->make(true);
    }

    public function testingConfiguration()
    {
        return view('authors.segments.configuration');
    }

    public function validateTest(AuthorSegmentsTestRequest $request)
    {
        return response()->json();
    }

    public function compute(AuthorSegmentsTestRequest $request)
    {
        $email = $request->get('email');

        Artisan::queue(ComputeAuthorsSegments::COMMAND, [
            '--email' => $email,
            '--history' => (int) $request->get('history'),
            '--min_views' => $request->get('min_views'),
            '--min_average_timespent' => $request->get('min_average_timespent'),
            '--min_ratio' => $request->get('min_ratio'),
        ]);

        return view('authors.segments.compute', compact('email'));
    }
}
