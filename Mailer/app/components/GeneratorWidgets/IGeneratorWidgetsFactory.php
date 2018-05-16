<?php

namespace Remp\MailerModule\Components;

interface IGeneratorWidgetsFactory
{
    /**
     * @param $sourceTemplateId
     *
     * @return GeneratorWidgets
     */
    public function create($sourceTemplateId);
}
