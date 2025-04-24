<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\Contracts\StatsHelper;

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
        return view('campaign::comparison.index');
    }

    public function json()
    {
        $campaignIds = session(self::SESSION_KEY_COMPARED_CAMPAIGNS, []);
        $campaigns = collect();
        $campaignsNotCompared = collect();

        foreach (Campaign::all() as $campaign) {
            if (in_array($campaign->id, $campaignIds)) {
                [$campaignData, $variantsData] = $this->statsHelper->cachedCampaignAndVariantsStats($campaign);

                $record = $campaign->toArray();
                $record['stats'] = $campaignData;
                $campaigns->push($record);
            } else {
                $campaignsNotCompared->push($campaign->toArray());
            }
        }

        return response()->json(compact('campaigns', 'campaignsNotCompared'));
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

    public function remove(Campaign $campaign)
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

    public function removeAll()
    {
        session([
            self::SESSION_KEY_COMPARED_CAMPAIGNS => []
        ]);
        return response()->json();
    }

    public function addAll()
    {
        session([
            self::SESSION_KEY_COMPARED_CAMPAIGNS => Campaign::query()->pluck('id')->toArray()
        ]);
        return response()->json();
    }
}
