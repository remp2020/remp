<?php
namespace Remp\MailerModule\Segment;

class Aggregator implements ISegment
{
    /** @var ISegment[] */
    private $providers = [];

    private $errors = [];

    public function register(ISegment $provider)
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

    public function list()
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

    public function users($segment)
    {
        return $this->providers[$segment['provider']]->users($segment);
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
