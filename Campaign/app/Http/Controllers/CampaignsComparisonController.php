<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\CampaignBanner;
use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentException;
use App\Contracts\StatsContract;
use App\Contracts\StatsHelper;
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

    private $statsHelper;

    public function __construct(StatsHelper $statsHelper)
    {
        $this->statsHelper = $statsHelper;
    }

    public function index()
    {
        return view('comparison.index', []);
    }

    public function json()
    {
        $campaignIds = session(self::SESSION_KEY_COMPARED_CAMPAIGNS, []);
        $campaigns = Campaign::whereIn('id', $campaignIds)->get();

        $addUrl = '';

        foreach ($campaigns as $campaign) {
            $campaign->stats = $this->statsHelper->campaignStats($campaign);
            $campaign->deleteUrl = route('comparison.delete', $campaign);
        }

        return response()->json(compact('campaigns', 'addUrl'));
    }

    public function add(Campaign $campaign)
    {
        $campaignIds = session(self::SESSION_KEY_COMPARED_CAMPAIGNS, []);
        if (!in_array($campaign->id, $campaignIds, true)) {
            $campaignIds[] = $campaign->id;
        }
        session([
            self::SESSION_KEY_COMPARED_CAMPAIGNS => $campaignIds
        ]);
        return response()->json();
    }

    public function delete(Campaign $campaign)
    {
        $campaignIds = session(self::SESSION_KEY_COMPARED_CAMPAIGNS, []);
        if (($key = array_search($campaign->id, $campaignIds)) !== false) {
            unset($campaignIds[$key]);
        }
        session([
            self::SESSION_KEY_COMPARED_CAMPAIGNS => $campaignIds
        ]);
        return response()->json();
    }
}
