<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\ContentGenerator\Respekt\Replace;

use Remp\MailerModule\Models\ContentGenerator\Replace\IReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\TextUrlRtmReplace;

class TextUrlUtmReplace extends TextUrlRtmReplace implements IReplace
{
    use UtmReplaceTrait;
}
