<?php

namespace Remp\MailerModule\Models\ContentGenerator;

use Nette\Database\IRow;
use Remp\MailerModule\Repositories\SnippetsRepository;

class GeneratorInputFactory
{
    private $snippetsRepository;

    public function __construct(SnippetsRepository $snippetsRepository)
    {
        $this->snippetsRepository = $snippetsRepository;
    }

    public function create(
        IRow $mailTemplate,
        array $params = [],
        ?int $batchId = null
    ): GeneratorInput {
        return new GeneratorInput(
            $this->snippetsRepository,
            $mailTemplate,
            $params,
            $batchId
        );
    }
}
