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
        $columns = ['id', 'name', 'segment_code', 'mailer_generator_id', 'created_at', 'updated_at'];
        $newsletters = Newsletter::select($columns);

        return $datatables->of($newsletters)
            ->addColumn('actions', function (Newsletter $newsletter) {
                return [
                    'edit' => route('newsletters.edit', $newsletter)
                ];
            })
            //->addColumn('name', function (newsletter $segment) {
            //    return HTML::linkRoute('segments.edit', $segment->name, $segment);
            //})
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function index()
    {
        return view('newsletters.index');
    }

    public function create()
    {
        $segments = $this->mailer->segments()->mapToGroups(function ($item) {
            return [$item->provider => [$item->code => $item->name]];
        })->mapWithKeys(function ($item, $key) {
            return [$key => $item->collapse()];
        })->toArray();

        $generators = $this->mailer->generatorTemplates('best_performing_articles')
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->title];
            });

        $criteria = [
            'pageviews' => 'Pageviews',
            'timespent' => 'Time spent',
            'conversion' => 'Conversions',
            'average_payment' => 'Average payment'
        ];

        $startsAt = Carbon::now()->addHour(1);
        $recurrenceRule = null;

        return response()->format([
            'html' => view(
                'newsletters.create',
                compact(['segments', 'generators', 'criteria', 'startsAt', 'recurrenceRule'])
            ),
            'json' => [],
        ]);
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
