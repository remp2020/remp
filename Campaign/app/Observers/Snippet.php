<?php

namespace App\Observers;

class Snippet
{
    public function saved()
    {
        \App\Snippet::refreshSnippetsCache();
    }
}
