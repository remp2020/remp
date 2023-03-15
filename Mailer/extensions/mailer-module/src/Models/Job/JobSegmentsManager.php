<?php

namespace Remp\MailerModule\Models\Job;

use Exception;
use Nette\Utils\Json;

class JobSegmentsManager
{
    private array $includeSegments = [];
    private array $excludeSegments = [];

    public function __construct($job = null)
    {
        if (isset($job)) {
            $segments = $this->getJobSegments($job);
            $this->includeSegments = $segments['include'];
            $this->excludeSegments = $segments['exclude'];
        }
    }

    public function includeSegment($segmentCode, $segmentProvider): self
    {
        $this->includeSegments[] = ['code' => $segmentCode, 'provider' => $segmentProvider];

        return $this;
    }

    public function excludeSegment($segmentCode, $segmentProvider): self
    {
        $this->excludeSegments[] = ['code' => $segmentCode, 'provider' => $segmentProvider];

        return $this;
    }

    public function getIncludeSegments(): array
    {
        return $this->includeSegments;
    }

    public function getExcludeSegments(): array
    {
        return $this->excludeSegments;
    }

    private function getJobSegments($job): array
    {
        return Json::decode($job->segments, Json::FORCE_ARRAY);
    }

    public function toJson()
    {
        if (empty($this->includeSegments)) {
            throw new Exception("You have to add at least one include segment.");
        }

        $segments = [
            'include' => $this->includeSegments,
            'exclude' => $this->excludeSegments,
        ];

        return Json::encode($segments);
    }
}
