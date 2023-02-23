<?php

namespace App\Http\Showtime;

use App\Banner;
use App\Campaign;
use App\CampaignBanner;

interface ShowtimeResponse
{
    public function error($callback, int $statusCode, array $errors);

    public function success(string $callback, $data, $activeCampaigns, $providerData);

    public function renderBanner(Banner $banner, array $alignments, array $dimensions, array $positions, array $variables): string;

    /**
     * render is responsible for rendering JS to be executed on client.
     *
     * @param CampaignBanner $variant
     * @param Campaign $campaign
     * @param $alignments
     * @param $dimensions
     * @param $positions
     * @param $variables
     * @param $userData
     * @return string
     */
    public function renderCampaign(CampaignBanner $variant, Campaign $campaign, array $alignments, array $dimensions, array $positions, array $variables, mixed $userData): string;
}
