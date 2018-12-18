<?php

namespace App\Http\Controllers;

use App\Author;
use App\Contracts\JournalContract;
use App\Conversion;
use App\Http\Request;
use App\Section;

class UserPathController extends Controller
{
    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    public function index(Request $request)
    {
        $authors = Author::all();
        $sections = Section::all();
        $sumCategories = Conversion::select('amount', 'currency')->groupBy('amount', 'currency')->get();


        return view('userpath.index', [
            'authors' => $authors,
            'sections' => $sections,
            'days' => range(1,14),
            'sumCategories' => $sumCategories,
        ]);
    }
}
