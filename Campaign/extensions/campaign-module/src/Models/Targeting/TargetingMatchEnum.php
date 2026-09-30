<?php

namespace Remp\CampaignModule\Models\Targeting;

enum TargetingMatchEnum
{
    case Allowed;
    case Blacklisted;
    case NotWhitelisted;
}
