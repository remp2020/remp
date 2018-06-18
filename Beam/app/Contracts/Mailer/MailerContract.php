<?php

namespace App\Contracts\Mailer;

use Illuminate\Support\Collection;

interface MailerContract
{
    public function segments(): Collection;

    public function generatorTemplates($generator = null): Collection;

    public function mailTypes(): Collection;

    public function generateEmail($sourceTemplateId, array $generatorParameters): Collection;

    public function createTemplate(
        $name,
        $code,
        $description,
        $from,
        $subject,
        $templateText,
        $templateHtml,
        $mailTypeCode
    ): int;

    public function createJob($segmentCode, $segmentProvider, $templateId): int;
}
