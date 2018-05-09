<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;
use Nette\DI\Container;
use Remp\MailerModule\Components\GeneratorWidgetsManager;

interface IGenerator
{
    /**
     * generate generates additional form elements based on the Generator type. If generator adds custom submit element,
     * it should return array of tabs in format [linkToTab => label].
     *
     * @param Form $form
     * @return string|null
     */
    public function generate(Form $form);

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
}
