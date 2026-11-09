<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReplaceSegmentBrowsersRequest;
use App\Model\ExplicitSegments;
use App\Model\SegmentNotFoundException;
use Illuminate\Http\JsonResponse;

class SegmentBrowsersController extends Controller
{
    public function replace(
        ReplaceSegmentBrowsersRequest $request,
        string $code,
        ExplicitSegments $explicitSegments,
    ): JsonResponse {
        try {
            $segment = $explicitSegments->replaceBrowsers($code, $request->validated('browser_ids'));
        } catch (SegmentNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'code' => $segment->code,
            'name' => $segment->name,
            'active' => $segment->active,
            'browsers_count' => $segment->browsers()->count(),
        ]);
    }
}
