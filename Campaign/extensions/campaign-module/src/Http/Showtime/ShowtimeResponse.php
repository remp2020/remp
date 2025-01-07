<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;

interface ShowtimeResponse
{
    public function error($callback, int $statusCode, array $errors);

    public function success(string $callback, $data, $activeCampaigns, $providerData, $suppressedBanners, array $evaluationMessages = []);

    public function renderBanner(Banner $banner, array $alignments, array $dimensions, array $positions, array $colorSchemes, array $snippets): string;

    /**
     * render is responsible for rendering JS to be executed on client.
     *
     * @param CampaignBanner $variant
     * @param Campaign $campaign
     * @param array $alignments
     * @param array $dimensions
     * @param array $positions
     * @param array $colorSchemes
     * @param array $snippets
     * @param $userData
     * @return string
     */
    public function renderCampaign(CampaignBanner $variant, Campaign $campaign, array $alignments, array $dimensions, array $positions, array $colorSchemes, array $snippets, mixed $userData): string;
}
