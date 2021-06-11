<?php
declare(strict_types=1);

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\SmartObject;

class SignInFormFactory
{
    use SmartObject;

    /** @var User */
    private $user;

    public $onSignIn;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function create()
    {
        $form = new Form();

        $form->addProtection();
        $form->addText('username', 'Email Address')
            ->setHtmlType('email')
            ->setRequired('Please enter your email');

        $form->addPassword('password', 'Password')
            ->setRequired('Please enter your password.');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        try {
            $this->user->login($values->username, $values->password);
            ($this->onSignIn)($this->user->getIdentity());
        } catch (AuthenticationException $exception) {
            $form->addError($exception->getMessage());
        }
    }
}
