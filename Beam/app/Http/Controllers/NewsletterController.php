<?php

namespace App\Http\Controllers;

use App\Contracts\Mailer\MailerContract;
use App\Http\Requests\NewsletterRequest;
use App\Newsletter;
use Carbon\Carbon;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    private $mailer;

    public function __construct(MailerContract $mailer)
    {
        $this->mailer = $mailer;
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'segment_code', 'mailer_generator_id', 'created_at', 'updated_at', 'starts_at'];
        $newsletters = Newsletter::select($columns);

        return $datatables->of($newsletters)
            ->addColumn('actions', function (Newsletter $newsletter) {
                return [
                    'edit' => route('newsletters.edit', $newsletter)
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function index()
    {
        return view('newsletters.index');
    }

    public function create()
    {
        $segments = $this->loadSegments();
        $generators = $this->loadGenerators();
        $criteria = $this->loadCriteria();
        $newsletter = new Newsletter();
        $newsletter->starts_at = Carbon::now()->addHour(1);
        $newsletter->articles_count = 1;

        return response()->format([
            'html' => view(
                'newsletters.create',
                compact(['newsletter', 'segments', 'generators', 'criteria'])
            ),
            'json' => [],
        ]);
    }

    public function edit($id)
    {
        $newsletter = Newsletter::find($id);
        $segments = $this->loadSegments();
        $generators = $this->loadGenerators();
        $criteria = $this->loadCriteria();

        return response()->format([
            'html' => view(
                'newsletters.edit',
                compact(['newsletter', 'segments', 'generators', 'criteria'])
            ),
            'json' => [],
        ]);
    }

    private function loadCriteria() {
        return [
            'pageviews' => 'Pageviews',
            'timespent' => 'Time spent',
            'conversion' => 'Conversions',
            'average_payment' => 'Average payment'
        ];
    }

    private function loadGenerators() {
        return $this->mailer->generatorTemplates('best_performing_articles')
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->title];
            });
    }

    private function loadSegments() {
        return $this->mailer->segments()->mapToGroups(function ($item) {
            return [$item->provider => [$item->code => $item->name]];
        })->mapWithKeys(function ($item, $key) {
            return [$key => $item->collapse()];
        })->toArray();
    }

    public function store(NewsletterRequest $request)
    {
        $newsletter = new Newsletter();
        $newsletter->fill($request->all());
        $newsletter->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'newsletters.index',
                    self::FORM_ACTION_SAVE => 'newsletters.edit',
                ],
                $newsletter
            )->with('success', sprintf('Newsletter [%s] was created', $newsletter->name)),
        ]);
    }

    public function update(NewsletterRequest $request, Newsletter $newsletter)
    {
        $newsletter->fill($request->all());
        $newsletter->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'newsletters.index',
                    self::FORM_ACTION_SAVE => 'newsletters.edit',
                ],
                $newsletter
            )->with('success', sprintf('Newsletter [%s] was updated', $newsletter->name)),
        ]);
    }
}
