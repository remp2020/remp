<?php

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Remp\MailerModule\Repositories\SnippetsRepository;

class GeneratorInput
{
    public function __construct(
        private SnippetsRepository $snippetsRepository,
        private ActiveRow $mailTemplate,
        private array $params = [],
        private ?int $batchId = null,
        private ?string $locale = null
    ) {
    }

    public function template(): ActiveRow
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
            'snippets_text' => $this->snippetsRepository
                ->getSnippetsForMailType($this->mailTemplate->mail_type_id)->fetchPairs('code', 'text'),
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
