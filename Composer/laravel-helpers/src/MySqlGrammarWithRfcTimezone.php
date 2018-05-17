<?php

namespace Remp\LaravelHelpers;

use Illuminate\Database\Query\Grammars\MySqlGrammar;

class MySqlGrammarWithRfcTimezone extends MySqlGrammar
{
    public function getDateFormat()
    {
        return \DateTime::RFC3339;
    }
}
