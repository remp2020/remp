<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\GeneratorWidgets;

interface IGeneratorWidgetsFactory
{
    /**
     * @param int $sourceTemplateId
     *
     * @return GeneratorWidgets
     */
    public function create(int $sourceTemplateId): GeneratorWidgets;
}
