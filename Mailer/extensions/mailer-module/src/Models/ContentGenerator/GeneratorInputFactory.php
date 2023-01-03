<?php

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repositories\SnippetsRepository;

class GeneratorInputFactory
{
    private SnippetsRepository $snippetsRepository;

    public function __construct(SnippetsRepository $snippetsRepository)
    {
        $this->snippetsRepository = $snippetsRepository;
    }

    public function create(
        ActiveRow $mailTemplate,
        array $params = [],
        ?int $batchId = null,
        string $locale = null
    ): GeneratorInput {
        return new GeneratorInput(
            $this->snippetsRepository,
            $mailTemplate,
            $params,
            $batchId,
            $locale
        );
    }
}
