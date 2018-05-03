<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Tomaj\NetteApi\Params\InputParam;

interface IGenerator
{
    /**
     * generates additional form elements based on the Generator type. If generator adds custom submit element,
     * it should return array of tabs in format [linkToTab => label].
     *
     * @param Form $form
     * @return string|null
     */
    public function generateForm(Form $form);

    /**
     * @param callable $onSubmit
     * @return void
     */
    public function onSubmit(callable $onSubmit);


    /**
     * Return widget classes
     * @return string[]
     */
    public function getWidgets();

    /**
     * Returns available parameters that generator needs
     *
     * @return InputParam[]
     */
    public function apiParams();

    /**
     * Generates output data from input values object
     *
     * @return array
     */
    public function process($values);


    /**
     * Generates parameters for generator from arbitrary object (e.g. WP article dump)
     * Each generator can define its own rules
     *
     * @return array
     */
    public function preprocessParameters($data);
}
