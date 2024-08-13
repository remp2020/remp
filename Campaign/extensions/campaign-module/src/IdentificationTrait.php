<?php

namespace Remp\CampaignModule;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

trait IdentificationTrait
{
    public static function generateUuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    public static function generatePublicId(): string
    {
        return Str::random(6);
    }
}
