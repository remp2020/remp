<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta;

enum AudioOriginEnum: string
{
    case Native = 'native';
    case AiGenerated = 'ai_generated';
}
