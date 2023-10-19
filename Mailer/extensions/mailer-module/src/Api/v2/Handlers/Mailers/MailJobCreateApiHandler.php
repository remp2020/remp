<?php

namespace Remp\MailerModule\Api\v2\Handlers\Mailers;

use Nette\Http\IResponse;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\JsonInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailJobCreateApiHandler extends BaseHandler
{
    use JsonValidationTrait;

    public function __construct(
        private JobsRepository $jobsRepository,
        private BatchesRepository $batchesRepository,
        private TemplatesRepository $templatesRepository,
        private Aggregator $aggregator,
        private ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct();
    }

    public function params(): array
    {
        return [
            (new JsonInputParam('raw', file_get_contents(__DIR__ . '/mail-job.schema.json')))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $params['raw'];

        $templateCode = $payload['template_code'];
        $template = $this->templatesRepository->getByCode($templateCode);
        if (!$template) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'code' => 'template_not_found',
                'message' => 'No such template with code:' . $payload['template_code'],
            ]);
        }

        $segments = [];
        $segmentList = $this->aggregator->list();
        array_walk($segmentList, function ($segment) use (&$segments) {
            $segments[$segment['provider']][$segment['code']] = true;
        });

        $jobSegmentsManager = new JobSegmentsManager();

        $includeSegments = $payload['include_segments'];
        foreach ($includeSegments as $includeSegment) {
            if (!isset($segments[$includeSegment['provider']][$includeSegment['code']])) {
                return new JsonApiResponse(IResponse::S404_NotFound, [
                    'status' => 'error',
                    'code' => 'segment_not_found',
                    'message' => "No such include segment {$includeSegment['provider']}::{$includeSegment['code']} was found"
                ]);
            }

            $jobSegmentsManager->includeSegment($includeSegment['code'], $includeSegment['provider']);
        }

        $excludeSegments = $payload['exclude_segments'] ?? [];
        foreach ($excludeSegments as $excludeSegment) {
            if (!isset($segments[$excludeSegment['provider']][$excludeSegment['code']])) {
                return new JsonApiResponse(IResponse::S404_NotFound, [
                    'status' => 'error',
                    'code' => 'segment_not_found',
                    'message' => "No such exclude segment {$excludeSegment['provider']}::{$excludeSegment['code']} was found"
                ]);
            }

            $jobSegmentsManager->excludeSegment($excludeSegment['code'], $excludeSegment['provider']);
        }

        $mailTypeVariant = null;
        if (isset($payload['mail_type_variant_code'])) {
            $mailTypeVariant = $this->listVariantsRepository->findByCode($payload['mail_type_variant_code']);
            if (!$mailTypeVariant) {
                return new JsonApiResponse(IResponse::S404_NotFound, [
                    'status' => 'error',
                    'code' => 'mail_type_variant_not_found',
                    'message' => 'No such mail type variant with code:' . $payload['mail_type_variant_code'],
                ]);
            }
        }

        $startAt = $payload['start_at'] ?? null;

        $mailJob = $this->jobsRepository->add($jobSegmentsManager, $payload['context'] ?? null, $mailTypeVariant);
        $batch = $this->batchesRepository->add($mailJob->id, null, $startAt, BatchesRepository::METHOD_RANDOM);
        $this->batchesRepository->addTemplate($batch, $template);
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND);

        return new JsonApiResponse(200, ['status' => 'ok', 'id' => $mailJob->id]);
    }
}
