<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\ContentGenerator\Respekt\Replace;

use Remp\MailerModule\Models\ContentGenerator\Replace\IReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\UrlRtmReplace;

class UrlUtmReplace extends UrlRtmReplace implements IReplace
{
    use UtmReplaceTrait;
}
