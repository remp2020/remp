<?php
namespace Remp\MailerModule\Segment;


class Aggregator implements ISegment
{
    /** @var ISegment[] */
    private $providers = [];

    public function register(ISegment $provider)
    {
        $this->providers = array_merge($this->providers, $provider->provider());
    }

    public function provider()
    {
        return $this->providers;
    }

    public function list()
    {
        $segments = [];
        foreach ($this->providers as $provider) {
            $segments = array_merge($segments, $provider->list());
        }

        return $segments;
    }

    public function users($segment)
    {
        return $this->providers[$segment['provider']]->users($segment);
    }
}
