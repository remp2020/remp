<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use View;

class ControllerShowtimeResponse implements ShowtimeResponse
{
    public function error($callback, int $statusCode, array $errors)
    {
        return response()
            ->jsonp($callback, [
                'success' => false,
                'errors' => $errors,
            ]);
    }

    public function success(string $callback, $data, $activeCampaigns, $providerData, $suppressedBanners, array $evaluationMessages = [])
    {
        $responseData = [
            'success' => true,
            'errors' => [],
            'data' => empty($data) ? [] : $data,
            'activeCampaignIds' => array_column($activeCampaigns, 'uuid'),
            'activeCampaigns' => $activeCampaigns,
            'providerData' => $providerData,
            'suppressedBanners' => $suppressedBanners
        ];

        if ($evaluationMessages) {
            $responseData['evaluationMessages'] = $evaluationMessages;
        }

        return response()->jsonp($callback, $responseData);
    }

    public function renderBanner(
        Banner $banner,
        array $alignments,
        array $dimensions,
        array $positions,
        array $colorSchemes,
        array $snippets
    ): string {
        return View::make('banners.preview', [
            'banner' => $banner,
            'variantUuid' => '',
            'campaignUuid' => '',
            'positions' => $positions,
            'dimensions' => $dimensions,
            'alignments' => $alignments,
            'colorSchemes' => $colorSchemes,
            'snippets' => $snippets,
            'controlGroup' => 0
        ])->render();
    }

    public function renderCampaign(
        CampaignBanner $variant,
        Campaign $campaign,
        array $alignments,
        array $dimensions,
        array $positions,
        array $colorSchemes,
        array $snippets,
        mixed $userData,
    ): string {
        return View::make('banners.preview', [
            'banner' => $variant->banner,
            'variantUuid' => $variant->uuid,
            'variantPublicId' => $variant->public_id,
            'campaignUuid' => $campaign->uuid,
            'campaignPublicId' => $campaign->public_id,
            'positions' => $positions,
            'dimensions' => $dimensions,
            'alignments' => $alignments,
            'colorSchemes' => $colorSchemes,
            'snippets' => $snippets,
            'controlGroup' => $variant->control_group,
            'userData' => $userData,
        ])->render();
    }
}
