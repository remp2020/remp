<?php

namespace Remp\MailerModule\Generators;

use Nette\Application\UI\Form;

class EmptyGenerator implements IGenerator
{
    public function generate(Form $form)
    {
        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function onSubmit(callable $onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }
}
