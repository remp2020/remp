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
    const CRITERIA_PAGEVIEWS_ALL = 'pageviews_all';
    const CRITERIA_PAGEVIEWS_SIGNED_IN = 'pageviews_signed_in';
    const CRITERIA_PAGEVIEWS_SUBSCRIBERS = 'pageviews_subscribers';
    const CRITERIA_TIMESPENT_ALL = 'timespent_all';
    const CRITERIA_TIMESPENT_SIGNED_IN = 'timespent_signed_in';
    const CRITERIA_TIMESPENT_SUBSCRIBERS = 'timespent_subscribers';
    const CRITERIA_CONVERSIONS = 'conversions';
    const CRITERIA_AVERAGE_PAYMENT = 'average_payment';

    private $mailer;

    public function __construct(MailerContract $mailer)
    {
        $this->mailer = $mailer;
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'segment', 'mailer_generator_id', 'created_at', 'updated_at', 'starts_at'];
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
        $mailTypes = $this->loadMailTypes();
        $newsletter = new Newsletter();
        $newsletter->starts_at = Carbon::now()->addHour(1);
        $newsletter->articles_count = 1;

        return response()->format([
            'html' => view(
                'newsletters.create',
                compact(['newsletter', 'segments', 'generators', 'criteria', 'mailTypes'])
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
        $mailTypes = $this->loadMailTypes();

        return response()->format([
            'html' => view(
                'newsletters.edit',
                compact(['newsletter', 'segments', 'generators', 'criteria', 'mailTypes'])
            ),
            'json' => [],
        ]);
    }

    public static function allCriteriaConcatenated($glue = ',')
    {
        return implode($glue,[
            self::CRITERIA_PAGEVIEWS_ALL,
            self::CRITERIA_PAGEVIEWS_SIGNED_IN,
            self::CRITERIA_PAGEVIEWS_SUBSCRIBERS,
            self::CRITERIA_TIMESPENT_ALL,
            self::CRITERIA_TIMESPENT_SIGNED_IN,
            self::CRITERIA_TIMESPENT_SUBSCRIBERS,
            self::CRITERIA_CONVERSIONS,
            self::CRITERIA_AVERAGE_PAYMENT,
            ]);
    }

    private function loadCriteria()
    {
        return [
            self::CRITERIA_PAGEVIEWS_ALL => 'Pageviews all',
            self::CRITERIA_PAGEVIEWS_SIGNED_IN => 'Pageviews signed in',
            self::CRITERIA_PAGEVIEWS_SUBSCRIBERS => 'Pageviews subscribers',
            self::CRITERIA_TIMESPENT_ALL => 'Time spent all',
            self::CRITERIA_TIMESPENT_SIGNED_IN => 'Time spent signed in',
            self::CRITERIA_TIMESPENT_SUBSCRIBERS => 'Time spent subscribers',
            self::CRITERIA_CONVERSIONS => 'Conversions',
            self::CRITERIA_AVERAGE_PAYMENT => 'Average payment'
        ];
    }

    private function loadGenerators()
    {
        return $this->mailer->generatorTemplates('best_performing_articles')
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->title];
            });
    }

    private function loadSegments()
    {
        return $this->mailer->segments()->mapToGroups(function ($item) {
            return [$item->provider => [$item->provider . '::' . $item->code => $item->name]];
        })->mapWithKeys(function ($item, $key) {
            return [$key => $item->collapse()];
        })->toArray();
    }

    private function loadMailTypes()
    {
        return $this->mailer->mailTypes()
            ->mapWithKeys(function ($item) {
                return [$item->code => $item->title];
            });
    }

    public function store(NewsletterRequest $request)
    {
        $newsletter = new Newsletter();
        $newsletter->fill($request->all());
        $newsletter->state = Newsletter::STATE_STARTED;
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
