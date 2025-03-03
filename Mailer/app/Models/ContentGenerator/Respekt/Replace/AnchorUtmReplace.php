<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\ContentGenerator\Respekt\Replace;

use Remp\MailerModule\Models\ContentGenerator\Replace\AnchorRtmReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\IReplace;

class AnchorUtmReplace extends AnchorRtmReplace implements IReplace
{
    use UtmReplaceTrait;
}
