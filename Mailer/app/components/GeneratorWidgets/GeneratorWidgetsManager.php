<?php

namespace Remp\MailerModule\Components;

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

    public function getAllWidgets()
    {
        return $this->widgets;
    }
}
