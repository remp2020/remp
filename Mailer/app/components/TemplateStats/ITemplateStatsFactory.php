<?php

namespace Remp\MailerModule\Components;

interface ITemplateStatsFactory
{
    /** @return TemplateStats */
    public function create();
}
