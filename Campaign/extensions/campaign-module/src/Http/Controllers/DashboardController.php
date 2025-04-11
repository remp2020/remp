<?php

namespace Remp\CampaignModule\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        return redirect()->route('campaigns.index');
    }
}
