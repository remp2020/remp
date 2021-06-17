<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\GeneratorWidgets;

class GeneratorWidgetsManager
{
    private $widgets = [];

    public function registerWidget($generator, $widget)
    {
        $this->widgets[$generator][] = $widget;
    }

    public function getWidgets($generator)
    {
        return $this->widgets[$generator];
    }

    public function getAllWidgets(): array
    {
        return $this->widgets;
    }
}
