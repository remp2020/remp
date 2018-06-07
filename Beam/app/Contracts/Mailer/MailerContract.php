<?php

namespace App\Contracts\Mailer;

use Illuminate\Support\Collection;

interface MailerContract
{
    public function segments(): Collection;

    public function generatorTemplates($generator = null): Collection;
}
