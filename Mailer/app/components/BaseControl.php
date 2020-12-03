<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components;

use Kdyby\Autowired\AutowireComponentFactories;
use Nette\Application\UI\Control;

abstract class BaseControl extends Control
{
    use AutowireComponentFactories;
}
