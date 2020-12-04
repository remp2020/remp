<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Segment;

class Aggregator implements ISegment
{
    /** @var ISegment[] */
    private $providers = [];

    private $errors = [];

    public function register(ISegment $provider): void
    {
        $this->providers = array_merge($this->providers, [$provider->provider() => $provider]);
    }

    /**
     * @return string
     * @throws SegmentException
     */
    public function provider(): string
    {
        throw new SegmentException("Aggregator cannot return provider value");
    }

    public function list(): array
    {
        $segments = [];
        foreach ($this->providers as $provider) {
            try {
                $segments = array_merge($segments, $provider->list());
            } catch (\Exception $e) {
                $this->errors[] = sprintf("%s: %s", $provider->provider(), $e->getMessage());
            }
        }
        return $segments;
    }

    public function users(array $segment): array
    {
        return $this->providers[$segment['provider']]->users($segment);
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
