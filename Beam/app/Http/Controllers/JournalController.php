<?php

namespace App\Http\Controllers;

use Remp\Journal\JournalContract;

class JournalController extends Controller
{
    public function flags(JournalContract $journalContract)
    {
        return $journalContract->flags();
    }

    public function actions(
        JournalContract $journalContract,
        $group,
        $category
    ) {
        return collect($journalContract->actions($group, $category));
    }
}
