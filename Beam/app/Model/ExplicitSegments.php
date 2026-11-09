<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentGroup;
use RuntimeException;

class ExplicitSegments
{
    /**
     * Replaces all browsers of an existing segment in a single transaction.
     *
     * @param string[] $browserIds
     * @throws SegmentNotFoundException
     */
    public function replaceBrowsers(string $code, array $browserIds): Segment
    {
        $segmentGroup = SegmentGroup::getByCode(ExternalSegmentGroup::CODE);
        if (!$segmentGroup) {
            throw new RuntimeException(
                "Segment group '" . ExternalSegmentGroup::CODE . "' not found."
            );
        }

        $browserIds = array_values(array_unique($browserIds));

        return DB::transaction(function () use ($segmentGroup, $code, $browserIds) {
            $segment = Segment::where('code', $code)
                ->where('segment_group_id', $segmentGroup->id)
                ->lockForUpdate()
                ->first();

            if (!$segment) {
                throw new SegmentNotFoundException(
                    "Segment '{$code}' not found in segment group '" . ExternalSegmentGroup::CODE . "'."
                );
            }

            $segment->touch(); // bump updated_at

            SegmentBrowser::where('segment_id', $segment->id)->delete();

            $now = Carbon::now();
            foreach (array_chunk($browserIds, 1000) as $chunk) {
                $rows = array_map(fn ($browserId) => [
                    'segment_id' => $segment->id,
                    'browser_id' => $browserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk);
                DB::table(SegmentBrowser::getTableName())->insert($rows);
            }

            return $segment;
        });
    }
}
