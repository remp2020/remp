<?php

namespace Remp\MailerModule\Generators;

use Nette\DI\Container;
use Remp\MailerModule\Components\GeneratorWidgetsManager;
use Remp\MailerModule\Repository\SourceTemplatesRepository;

class GeneratorFactory
{
    /** @var array(ExtensionInterface) */
    private $generators = [];

    private $pairs = [];

    private $generatorWidgetsManager;

    private $container;

    public function __construct(
        Container $container,
        SourceTemplatesRepository $sourceTemplateRepository,
        GeneratorWidgetsManager $generatorWidgetsManager
    ) {
    
        $this->generatorWidgetsManager = $generatorWidgetsManager;
        $this->container = $container;
    }

    public function registerGenerator($type, $label, IGenerator $generator)
    {
        $this->generators[$type] = $generator;
        $this->pairs[$type] = $label;
        $widgetClasses = $generator->getWidgets();

        foreach ($widgetClasses as $class) {
            $widget = $this->container->getByType($class);
            $this->generatorWidgetsManager->registerWidget($type, $widget);
        }
    }

    /**
     * @param string $type
     * @return IGenerator
     * @throws \Exception
     */
    public function get($type)
    {
        if (isset($this->generators[$type])) {
            return $this->generators[$type];
        }
        throw new \Exception("Unknown generator type: {$type}");
    }

    public function keys()
    {
        return array_keys($this->generators);
    }

    public function pairs()
    {
        return $this->pairs;
    }
}
