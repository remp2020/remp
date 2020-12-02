<?php

namespace App\Http\Controllers;

use App\Console\Commands\ComputeSectionSegments;
use App\Http\Requests\SectionSegmentsTestRequest;
use App\Model\Config\ConfigCategory;
use App\Segment;
use App\SegmentGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SectionSegmentsController extends Controller
{
    public function index()
    {
        return view('sections.segments.index', [
            'sectionSegmentsSettingsUrl' => ConfigCategory::getSettingsTabUrl(ConfigCategory::CODE_SECTION_SEGMENTS)
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'code', 'created_at',
            DB::raw('(SELECT COUNT(*) FROM segment_users WHERE segment_users.segment_id=segments.id) as users_count'),
            DB::raw('(SELECT COUNT(*) FROM segment_browsers WHERE segment_browsers.segment_id=segments.id) as browsers_count'),
            ];
        $segments = Segment::select($columns)
            ->where('segment_group_id', SegmentGroup::getByCode(SegmentGroup::CODE_SECTIONS_SEGMENTS)->id);

        return $datatables->of($segments)
            ->make(true);
    }

    public function testingConfiguration()
    {
        return view('sections.segments.configuration');
    }

    public function validateTest(SectionSegmentsTestRequest $request)
    {
        return response()->json();
    }

    public function compute(SectionSegmentsTestRequest $request)
    {
        $email = $request->get('email');

        Artisan::queue(ComputeSectionSegments::COMMAND, [
            '--email' => $email,
            '--history' => (int) $request->get('history'),
            '--min_views' => $request->get('min_views'),
            '--min_average_timespent' => $request->get('min_average_timespent'),
            '--min_ratio' => $request->get('min_ratio'),
        ]);

        return view('sections.segments.compute', compact('email'));
    }
}
