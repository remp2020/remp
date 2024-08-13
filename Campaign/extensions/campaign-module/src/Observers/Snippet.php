<?php

namespace Remp\CampaignModule\Observers;

class Snippet
{
    public function saved()
    {
        \Remp\CampaignModule\Snippet::refreshSnippetsCache();
    }
}
