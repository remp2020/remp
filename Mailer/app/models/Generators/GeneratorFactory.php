<?php

namespace Remp\MailerModule\Generators;

use Remp\MailerModule\Repository\SourceTemplateRepository;

class GeneratorFactory
{
    private $sourceTemplateRepository;

    /** @var array(ExtensionInterface) */
    private $generators = [];

    private $pairs = [];

    public function __construct(SourceTemplateRepository $sourceTemplateRepository)
    {
        $this->sourceTemplateRepository = $sourceTemplateRepository;
    }

    public function registerGenerator($type, $label, IGenerator $generator)
    {
        $this->generators[$type] = $generator;
        $this->pairs[$type] = $label;
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
