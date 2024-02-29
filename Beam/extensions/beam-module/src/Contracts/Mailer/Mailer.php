<?php

namespace Remp\BeamModule\Contracts\Mailer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Mailer implements MailerContract
{
    const ENDPOINT_GENERATOR_TEMPLATES = 'api/v1/mailers/generator-templates';

    const ENDPOINT_SEGMENTS = 'api/v1/segments/list';

    const ENDPOINT_MAIL_TYPES = 'api/v1/mailers/mail-types';

    const ENDPOINT_GENERATE_EMAIL = 'api/v1/mailers/generate-mail';

    const ENDPOINT_CREATE_TEMPLATE = 'api/v1/mailers/templates';

    const ENDPOINT_CREATE_JOB = 'api/v1/mailers/jobs';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function segments(): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_SEGMENTS);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return collect(json_decode($response->getBody())->data);
    }

    public function generatorTemplates($generator = null): Collection
    {
        $params = [];
        if ($generator) {
            $params['query'] = ['generator' => $generator];
        }
        try {
            $response = $this->client->get(self::ENDPOINT_GENERATOR_TEMPLATES, $params);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return collect(json_decode($response->getBody())->data);
    }

    public function generateEmail($sourceTemplateId, array $generatorParameters): Collection
    {
        $postParams = [
            'source_template_id' => $sourceTemplateId
        ];
        $postParams = array_merge($postParams, $generatorParameters);

        try {
            $response = $this->client->post(self::ENDPOINT_GENERATE_EMAIL, [
                'form_params' => $postParams
            ]);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return collect(json_decode($response->getBody())->data);
    }

    public function mailTypes(): Collection
    {
        try {
            $response = $this->client->get(self::ENDPOINT_MAIL_TYPES);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return collect(json_decode($response->getBody())->data);
    }

    public function createTemplate(
        $name,
        $code,
        $description,
        $from,
        $subject,
        $templateText,
        $templateHtml,
        $mailTypeCode,
        $extras = null
    ): int {
        $multipart = [
            [
                'name' => 'name',
                'contents' => $name
            ],
            [
                'name' => 'code',
                'contents' => $code
            ],
            [
                'name' => 'description',
                'contents' => $description
            ],
            [
                'name' => 'from',
                'contents' => $from
            ],
            [
                'name' => 'subject',
                'contents' => $subject
            ],
            [
                'name' => 'template_text',
                'contents' => $templateText
            ],
            [
                'name' => 'template_html',
                'contents' => $templateHtml
            ],
            [
                'name' => 'mail_type_code',
                'contents' => $mailTypeCode
            ]
        ];

        if ($extras) {
            $multipart[] = [
                'name' => 'extras',
                'contents' => $extras
            ];
        }

        try {
            $response = $this->client->post(self::ENDPOINT_CREATE_TEMPLATE, [
                'multipart' => $multipart
            ]);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        } catch (ClientException $e) {
            Log::error('Unable to create Mailer template: ' . self::ENDPOINT_CREATE_TEMPLATE . ': ' . json_encode($multipart));
            throw $e;
        }

        $output = json_decode($response->getBody());
        if ($output->status === 'error') {
            throw new MailerException("Error while creating template: {$output->message}");
        }

        return $output->id;
    }

    public function createJob($segmentCode, $segmentProvider, $templateId): int
    {
        $postParams = [
            'segment_code' => $segmentCode,
            'segment_provider' => $segmentProvider,
            'template_id' => $templateId
        ];

        try {
            $response = $this->client->post(self::ENDPOINT_CREATE_JOB, [
                'form_params' => $postParams
            ]);
        } catch (ConnectException $e) {
            throw new MailerException("Could not connect to Mailer endpoint: {$e->getMessage()}");
        }

        return json_decode($response->getBody())->id;
    }
}
