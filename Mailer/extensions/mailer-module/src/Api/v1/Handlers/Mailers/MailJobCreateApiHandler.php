<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Http\IResponse;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailJobCreateApiHandler extends BaseHandler
{
    private $jobsRepository;

    private $batchesRepository;

    private $templatesRepository;

    private $aggregator;

    private $listVariantsRepository;

    public function __construct(
        JobsRepository $jobsRepository,
        BatchesRepository $batchesRepository,
        TemplatesRepository $templatesRepository,
        Aggregator $aggregator,
        ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct();
        $this->jobsRepository = $jobsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->aggregator = $aggregator;
        $this->listVariantsRepository = $listVariantsRepository;
    }

    public function params(): array
    {
        return [
            (new PostInputParam('segment_code'))->setRequired(),
            (new PostInputParam('segment_provider'))->setRequired(),
            (new PostInputParam('template_id'))->setRequired(),
            (new PostInputParam('context')),
            (new PostInputParam('mail_type_variant_code')),
            (new PostInputParam('start_at')),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $templateId = $params['template_id'];
        $template = $this->templatesRepository->find($templateId);
        if (!$template) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'message' => 'No such template with id:' . $params['template_id'],
            ]);
        }

        $segmentCode = $params['segment_code'];
        $segmentProvider = $params['segment_provider'];
        $segmentFound = false;
        foreach ($this->aggregator->list() as $segment) {
            if ($segmentCode === $segment['code'] && $segmentProvider === $segment['provider']) {
                $segmentFound = true;
                break;
            }
        }
        if (!$segmentFound) {
            return new JsonApiResponse(IResponse::S404_NotFound, [
                'status' => 'error',
                'message' => 'No such segment was found'
            ]);
        }

        $mailTypeVariant = null;
        if (isset($params['mail_type_variant_code'])) {
            $mailTypeVariant = $this->listVariantsRepository->findByCode($params['mail_type_variant_code']);
            if (!$mailTypeVariant) {
                return new JsonApiResponse(IResponse::S404_NotFound, [
                    'status' => 'error',
                    'message' => 'No such mail type variant with code:' . $params['mail_type_variant_code'],
                ]);
            }
        }

        $startAt = null;
        if (isset($params['start_at'])) {
            $dateTime = \DateTime::createFromFormat(DATE_RFC3339, $params['start_at']);
            if ($dateTime === false) {
                return new JsonApiResponse(IResponse::S400_BadRequest, [
                    'status' => 'error',
                    'message' => 'Wrong datetime format used (RFC 3339 required)',
                ]);
            }

            $startAt = $params['start_at'];
        }

        $mailJob = $this->jobsRepository->add((new JobSegmentsManager())->includeSegment($segmentCode, $segmentProvider), $params['context'] ?? null, $mailTypeVariant);
        $batch = $this->batchesRepository->add($mailJob->id, null, $startAt, BatchesRepository::METHOD_RANDOM);
        $this->batchesRepository->addTemplate($batch, $template);
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND);

        return new JsonApiResponse(200, ['status' => 'ok', 'id' => $mailJob->id]);
    }
}
