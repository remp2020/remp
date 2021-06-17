<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\GeneratorWidgets\Widgets;

interface IGeneratorWidget
{
    public function identifier(): string;
}
