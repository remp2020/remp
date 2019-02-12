<?php

namespace App\Http\Controllers;

use App\Console\Commands\ComputeAuthorsSegments;
use App\Console\Commands\CreateAuthorsSegments;
use App\Http\Requests\AuthorSegmentsConfigurationRequest;
use App\Http\Requests\AuthorSegmentsTestRequest;
use App\Http\Resources\ConfigResource;
use App\Model\Config;
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

    public function configuration()
    {
        return view('authors.segments.configuration', [
            'daysInPast' => Config::loadByName(ComputeAuthorsSegments::CONFIG_DAYS_IN_PAST),
            'minViews' => Config::loadByName(ComputeAuthorsSegments::CONFIG_MIN_VIEWS),
            'minRatio' => Config::loadByName(ComputeAuthorsSegments::CONFIG_MIN_RATIO),
            'minAverageTimespent' => Config::loadByName(ComputeAuthorsSegments::CONFIG_MIN_AVERAGE_TIMESPENT),
        ]);
    }

    public function saveConfiguration(AuthorSegmentsConfigurationRequest $request)
    {
        $toUpdate = [
            ComputeAuthorsSegments::CONFIG_DAYS_IN_PAST => 'days_in_past',
            ComputeAuthorsSegments::CONFIG_MIN_VIEWS => 'min_views',
            ComputeAuthorsSegments::CONFIG_MIN_AVERAGE_TIMESPENT => 'min_average_timespent',
            ComputeAuthorsSegments::CONFIG_MIN_RATIO => 'min_ratio',
        ];

        foreach ($toUpdate as $configName => $value) {
            Config::where('name', $configName)->first()->update(['value' => $request->get($value)]);
        }

        return response()->format([
            'html' => redirect(route('authorSegments.configuration'))->with('success', 'Configuration saved'),
            'json' => ConfigResource::collection(Config::whereIn('name', ComputeAuthorsSegments::ALL_CONFIGS)->get()),
        ]);
    }

    public function validateConfiguration(AuthorSegmentsConfigurationRequest $request)
    {
        return response()->json();
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
