<?php

namespace Remp\CampaignModule\Contracts;

use Predis\ClientInterface;

/**
 * Provides a way to create instance of SegmentContract without Laravel by splitting config from the instantiation.
 */
interface SegmentContractFactory
{
    /**
     * Reads the configuration of the segment contract. Expected to be called from within Laravel so that values
     * are can be read via Laravel's config() function.
     */
    public function config(): array;

    /**
     * Creates the segment contract from the result of config(). Excpeted to be also called outside of Laravel,
     * so no container config() or facades are available.
     */
    public function create(array $config, ClientInterface|\Redis $redis): SegmentContract;
}
