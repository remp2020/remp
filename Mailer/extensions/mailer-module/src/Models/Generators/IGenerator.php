<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Tomaj\NetteApi\Params\InputParam;

interface IGenerator
{
    /**
     * generates additional form elements based on the Generator type. If generator adds custom submit element,
     * it should return array of tabs in format [linkToTab => label].
     *
     * @param Form $form
     */
    public function generateForm(Form $form): void;

    /**
     * @param callable $onSubmit
     * @return void
     */
    public function onSubmit(callable $onSubmit): void;


    /**
     * Return widget classes
     * @return string[]
     */
    public function getWidgets(): array;

    /**
     * Returns available parameters that generator needs
     *
     * @return InputParam[]
     */
    public function apiParams(): array;

    /**
     * Generates output data from input values object
     * Used by both Form POST and API call
     *
     * @param array $values
     * @return array
     */
    public function process(array $values): array;


    /**
     * Generates parameters for generator from arbitrary object (e.g. WP article dump)
     * Each generator can define its own rules
     *
     * @param \stdClass $data
     * @return ?ArrayHash
     */
    public function preprocessParameters($data): ?ArrayHash;
}
