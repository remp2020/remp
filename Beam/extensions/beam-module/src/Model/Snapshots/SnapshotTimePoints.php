<?php
namespace Remp\BeamModule\Model\Snapshots;

class SnapshotTimePoints
{
    private $includedPoints;

    private $excludedPoints;

    private $includedPointsMapping;

    /**
     * Helper structure to store which time points in article_views_snapshots table we use when displaying points in Dashboard
     * or compressing the table.
     *
     * @param array $includedPoints string array of datetimes
     * @param array $excludedPoints string array of datetimes
     * @param array $includedPointsMapping included time points mapped to time points we want them to show at
     */
    public function __construct(array $includedPoints, array $excludedPoints, array $includedPointsMapping = [])
    {
        $this->includedPoints = $includedPoints;
        $this->excludedPoints = $excludedPoints;
        $this->includedPointsMapping = $includedPointsMapping;
    }

    public function getIncludedPoints(): array
    {
        return $this->includedPoints;
    }

    public function getExcludedPoints(): array
    {
        return $this->excludedPoints;
    }

    public function getIncludedPointsMapping(): array
    {
        return $this->includedPointsMapping;
    }
}
