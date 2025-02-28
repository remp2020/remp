<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\DI\Container;
use Remp\MailerModule\Components\GeneratorWidgets\GeneratorWidgetsManager;

class GeneratorFactory
{
    /** @var IGenerator[] */
    private $generators = [];

    private $pairs = [];

    private $generatorWidgetsManager;

    private $container;

    public function __construct(
        Container $container,
        GeneratorWidgetsManager $generatorWidgetsManager
    ) {
        $this->generatorWidgetsManager = $generatorWidgetsManager;
        $this->container = $container;
    }

    public function registerGenerator(string $type, string $label, IGenerator $generator): void
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
    public function get(string $type): IGenerator
    {
        if (isset($this->generators[$type])) {
            return $this->generators[$type];
        }
        throw new \Exception("Unknown generator type: {$type}");
    }

    public function keys(): array
    {
        return array_keys($this->generators);
    }

    public function pairs(): array
    {
        return $this->pairs;
    }
}
