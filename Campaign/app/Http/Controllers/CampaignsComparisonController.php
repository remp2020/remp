<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\Contracts\StatsHelper;

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
        return view('comparison.index');
    }

    public function json()
    {
        $campaignIds = session(self::SESSION_KEY_COMPARED_CAMPAIGNS, []);
        $campaigns = collect();
        $campaignsNotCompared = collect();

        foreach (Campaign::all() as $campaign) {
            if (in_array($campaign->id, $campaignIds)) {
                $campaign->stats = $this->statsHelper->campaignStats($campaign);
                $campaigns->push($campaign);
            } else {
                $campaignsNotCompared->push($campaign);
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
            self::SESSION_KEY_COMPARED_CAMPAIGNS => Campaign::all()->pluck('id')->toArray()
        ]);
        return response()->json();
    }
}
