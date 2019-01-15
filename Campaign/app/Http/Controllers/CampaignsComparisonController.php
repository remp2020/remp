<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\CampaignBanner;
use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentException;
use App\Country;
use App\Http\Request;
use App\Http\Requests\CampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Schedule;
use Cache;
use Carbon\Carbon;
use GeoIp2;
use HTML;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Tracy\Debugger;
use View;
use Yajra\Datatables\Datatables;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;
use DeviceDetector\DeviceDetector;
use App\Contracts\Remp\Stats;

class CampaignsComparisonController extends Controller
{
    private const SESSION_KEY_COMPARED_CAMPAIGNS = 'compared_campaigns';

    public function index()
    {
        return view('comparison.index', []);
    }

    public function add(Campaign $campaign)
    {
        $campaigns = session(self::SESSION_KEY_COMPARED_CAMPAIGNS, []);
        if (!in_array($campaign->id, $campaigns, true)) {
            $campaigns[] = $campaign->id;
        }
        session([
            self::SESSION_KEY_COMPARED_CAMPAIGNS => $campaigns
        ]);
        return response()->json();
    }
}
