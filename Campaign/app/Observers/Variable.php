<?php

namespace App\Observers;

class Variable
{
    public function saved()
    {
        \App\Variable::refreshVariableCache();
    }
}
