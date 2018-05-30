<?php

namespace Remp\MailerModule\Api\v1\Handlers\Segments;

use Remp\MailerModule\Segment\Aggregator;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;

class SegmentsListingHandler extends BaseHandler
{
    private $aggregator;

    public function __construct(Aggregator $aggregator)
    {
        parent::__construct();
        $this->aggregator = $aggregator;
    }

    public function handle($params)
    {
        $output = $this->aggregator->list();

        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }
}
