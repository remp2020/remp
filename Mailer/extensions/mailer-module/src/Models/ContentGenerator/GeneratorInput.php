<?php

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Database\IRow;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Repositories\SnippetsRepository;

class GeneratorInput
{
    private $snippetsRepository;

    private $mailTemplate;

    private $params;

    private $batchId;

    private $locale;

    public function __construct(
        SnippetsRepository $snippetsRepository,
        IRow $mailTemplate,
        array $params = [],
        ?int $batchId = null,
        string $locale = null
    ) {
        $this->snippetsRepository = $snippetsRepository;
        $this->mailTemplate = $mailTemplate;
        $this->params = $params;
        $this->batchId = $batchId;
        $this->locale = $locale;
    }

    public function template(): IRow
    {
        return $this->mailTemplate;
    }

    public function layout(): ActiveRow
    {
        return $this->mailTemplate->mail_layout;
    }

    public function batchId(): ?int // what about returning 0?
    {
        return $this->batchId;
    }

    public function params(): array
    {
        $additionalParams = [
            'snippets' => $this->snippetsRepository
                ->getSnippetsForMailType($this->mailTemplate->mail_type_id)->fetchPairs('code', 'html'),
        ];

        if ($this->mailTemplate->params) {
            $additionalParams['template_params'] = Json::decode($this->mailTemplate->params, Json::FORCE_ARRAY);
        }

        return array_merge($this->params, $additionalParams);
    }

    public function locale(): ?string
    {
        return $this->locale;
    }
}
