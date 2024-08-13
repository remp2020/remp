<?php

namespace Remp\CampaignModule\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect()->route('campaigns.index');
    }
}
