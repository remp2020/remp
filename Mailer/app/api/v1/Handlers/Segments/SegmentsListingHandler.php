<?php
declare(strict_types=1);

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
        $output = [];
        foreach ($this->aggregator->list() as $segment) {
            $item = [];
            $item['name'] = $segment['name'];
            $item['code'] = $segment['code'];
            $item['provider'] = $segment['provider'];
            $output[] = $item;
        }
        return new JsonApiResponse(200, ['status' => 'ok', 'data' => $output]);
    }
}
