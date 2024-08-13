<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Contracts\SegmentCacheException;
use Remp\CampaignModule\Http\Requests\SegmentCacheRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SegmentCacheController extends Controller
{
    private $segmentAggregator;

    public function __construct(
        SegmentAggregator $segmentAggregator
    ) {
        $this->segmentAggregator = $segmentAggregator;
    }

    public function addUserToCache(SegmentCacheRequest $request): JsonResponse
    {
        $segmentCode = $request->segment_code;
        $segmentProvider = $request->segment_provider;
        $userId = $request->get('user_id');

        try {
            $campaignSegment = CampaignSegment::select()
                ->join('schedules', 'campaign_segments.campaign_id', '=', 'schedules.campaign_id')
                ->where(function (\Illuminate\Database\Eloquent\Builder $query) {
                    $query
                        ->whereNull('schedules.end_time')
                        ->orWhere('schedules.end_time', '>=', Carbon::now());
                })
                ->whereIn('schedules.status', [\Remp\CampaignModule\Schedule::STATUS_READY, \Remp\CampaignModule\Schedule::STATUS_EXECUTED, \Remp\CampaignModule\Schedule::STATUS_PAUSED])
                ->whereCode($segmentCode)
                ->whereProvider($segmentProvider)
                ->firstOrFail();
            $this->segmentAggregator->addUserToCache($campaignSegment, $userId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Segment with code [{$segmentCode}] from provider [$segmentProvider] is not actively used in the campaign.",
            ], Response::HTTP_NOT_FOUND);
        } catch (SegmentCacheException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Cache is not enabled for segment provider [$segmentProvider].",
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return response()->json([
                'status' => 'error',
                'message' => "Internal server error",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => 'ok',
        ], Response::HTTP_ACCEPTED);
    }

    public function removeUserFromCache(SegmentCacheRequest $request): JsonResponse
    {
        $segmentCode = $request->segment_code;
        $segmentProvider = $request->segment_provider;
        $userId = $request->get('user_id');

        try {
            $campaignSegment = CampaignSegment::select()
                ->join('schedules', 'campaign_segments.campaign_id', '=', 'schedules.campaign_id')
                ->where(function (\Illuminate\Database\Eloquent\Builder $query) {
                    $query
                        ->whereNull('schedules.end_time')
                        ->orWhere('schedules.end_time', '>=', Carbon::now());
                })
                ->whereIn('schedules.status', [\Remp\CampaignModule\Schedule::STATUS_READY, \Remp\CampaignModule\Schedule::STATUS_EXECUTED, \Remp\CampaignModule\Schedule::STATUS_PAUSED])
                ->whereCode($segmentCode)
                ->whereProvider($segmentProvider)
                ->firstOrFail();
            $this->segmentAggregator->removeUserFromCache($campaignSegment, $userId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Segment with code [{$segmentCode}] from provider [$segmentProvider] is not actively used in the campaign.",
            ], Response::HTTP_NOT_FOUND);
        } catch (SegmentCacheException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Cache is not enabled for segment provider [$segmentProvider].",
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return response()->json([
                'status' => 'error',
                'message' => "Internal server error",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => 'ok',
        ], Response::HTTP_ACCEPTED);
    }
}
