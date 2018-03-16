<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function flags(\App\Contracts\JournalContract $journalContract)
    {
        return $journalContract->flags();
    }

    public function actions(
        \App\Contracts\JournalContract $journalContract,
        $group,
        $category
    ) {
        return $journalContract->actions($group, $category);
    }
}
