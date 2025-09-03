<?php

namespace Remp\BeamModule\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Remp\BeamModule\Http\Requests\ConversionRequest;
use Remp\BeamModule\Http\Requests\ConversionUpsertRequest;
use Remp\BeamModule\Http\Resources\ConversionResource;
use Remp\BeamModule\Jobs\ProcessConversionJob;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\ConversionCommerceEvent;
use Remp\BeamModule\Model\ConversionGeneralEvent;
use Remp\BeamModule\Model\ConversionPageviewEvent;
use Remp\BeamModule\Model\Rules\ValidCarbonDate;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Tag;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class ConversionController extends Controller
{
    public function index(Request $request)
    {
        $conversions = Conversion::select('conversions.*')
            ->join('articles', 'articles.id', '=', 'conversions.article_id');

        $conversionFrom = $request->get('conversion_from');
        if ($conversionFrom) {
            $conversions = $conversions->where('paid_at', '>=', Carbon::parse($conversionFrom));
        }
        $conversionTo = $request->get('conversion_to');
        if ($conversionTo) {
            $conversions = $conversions->where('paid_at', '<=', Carbon::parse($conversionTo));
        }

        $articlePublishedFrom = $request->get('article_published_from');
        if ($articlePublishedFrom) {
            $conversions = $conversions->where('articles.published_at', '>=', Carbon::parse($articlePublishedFrom));
        }
        $articlePublishedTo = $request->get('article_published_to');
        if ($articlePublishedTo) {
            $conversions = $conversions->where('articles.published_at', '<=', Carbon::parse($articlePublishedTo));
        }
        $articleContentType = $request->get('article_content_type');
        if ($articleContentType) {
            $conversions = $conversions->where('articles.content_type', '=', $articleContentType);
        }

        return response()->format([
            'html' => view('beam::conversions.index', [
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'authors' => Author::query()->pluck('name', 'id'),
                'sections' => Section::query()->pluck('name', 'id'),
                'tags' => Tag::query()->pluck('name', 'id'),
                'conversionFrom' => $request->get('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->get('conversion_to', 'now'),
            ]),
            'json' => ConversionResource::collection($conversions->paginate($request->get('per_page', 15)))->preserveQuery(),
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $request->validate([
            'conversion_from' => ['sometimes', new ValidCarbonDate],
            'conversion_to' => ['sometimes', new ValidCarbonDate],
            'article_published_from' => ['sometimes', new ValidCarbonDate],
            'article_published_to' => ['sometimes', new ValidCarbonDate],
        ]);

        $conversions = Conversion::select('conversions.*', 'articles.content_type')
            ->with(['article', 'article.authors', 'article.sections', 'article.tags'])
            ->ofSelectedProperty()
            ->join('articles', 'articles.id', '=', 'conversions.article_id');

        if ($request->input('conversion_from')) {
            $conversions->where('paid_at', '>=', Carbon::parse($request->input('conversion_from'), $request->input('tz')));
        }
        if ($request->input('conversion_to')) {
            $conversions->where('paid_at', '<=', Carbon::parse($request->input('conversion_to'), $request->input('tz')));
        }

        if ($request->input('article_published_from')) {
            $conversions->where('articles.published_at', '>=', Carbon::parse($request->input('article_published_from'), $request->input('tz')));
        }
        if ($request->input('article_published_to')) {
            $conversions->where('articles.published_at', '<=', Carbon::parse($request->input('article_published_to'), $request->input('tz')));
        }

        /** @var QueryDataTable $datatable */
        $datatable = $datatables->of($conversions);
        return $datatable
            ->addColumn('id', function (Conversion $conversion) {
                return $conversion->id;
            })
            ->addColumn('actions', function (Conversion $conversion) {
                return [
                    'show' => route('conversions.show', $conversion),
                ];
            })
            ->addColumn('article.title', function (Conversion $conversion) {
                return [
                    'url' => route('articles.show', $conversion->article->id),
                    'text' => $conversion->article->title,
                ];
            })
            ->filterColumn('article.title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', '%' . $value . '%');
            })
            ->filterColumn('content_type', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('articles.content_type', $values);
            })
            ->filterColumn('article.authors[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_author', 'articles.id', '=', 'article_author.article_id', 'left')
                    ->whereIn('article_author.author_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('article.sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_section', 'articles.id', '=', 'article_section.article_id', 'left')
                    ->whereIn('article_section.section_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('article.tags[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_tag', 'articles.id', '=', 'article_tag.article_id', 'left')
                    ->whereIn('article_tag.tag_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->orderColumn('id', 'conversions.id $1')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(ConversionRequest $request)
    {
        $conversion = new Conversion();
        $conversion->fill($request->all());
        $conversion->save();

        return response()->format([
            'html' => redirect(route('conversions.index'))->with('success', 'Conversion created'),
            'json' => new ConversionResource($conversion),
        ]);
    }

    private function eventsPriorConversion(Conversion $conversion, $daysInPast): array
    {
        $from = (clone $conversion->paid_at)->subDays($daysInPast);

        $events = [];

        /** @var ConversionPageviewEvent $event */
        foreach ($conversion->pageviewEvents()->where('time', '>=', $from)->get() as $event) {
            $obj = new \stdClass();
            $obj->name = 'pageview';
            $obj->time = $event->time;
            $obj->tags = [];

            if ($event->article) {
                $obj->tags[] = (object) [
                    'title' => $event->article->title,
                    'href' => route('articles.show', ['article' => $event->article->id]),
                ];
                $obj->tags[] = (object) [
                    'title' => $event->locked ? 'Locked' : 'Unlocked',
                ];

                if ($event->timespent) {
                    $obj->tags[] = (object) [
                        'title' => "Timespent: {$event->timespent} s",
                    ];
                }
            }

            $obj->tags[] = (object) [
                'title' => $event->signed_in ? 'Signed in' : 'Anonymous',
            ];

            $events[$event->time->toDateTimeString()] = $obj;
        }

        /** @var ConversionCommerceEvent $event */
        foreach ($conversion->commerceEvents()->where('time', '>=', $from)->get() as $event) {
            $obj = new \stdClass();
            $obj->name = "commerce:$event->step";
            $obj->time = $event->time;
            $obj->tags[] = (object) [
                'title' => "Funnel: {$event->funnel_id}",
            ];
            if ($event->amount) {
                $obj->tags[] = (object) [
                    'title' => "Amount: {$event->amount} {$event->currency}",
                ];
            }
            $events[$event->time->toDateTimeString()] = $obj;
        }

        /** @var ConversionGeneralEvent $event */
        foreach ($conversion->generalEvents()->where('time', '>=', $from)->get() as $event) {
            $obj = new \stdClass();
            $obj->name = "{$event->action}:{$event->category}";
            $obj->time = $event->time;
            $obj->tags = [];
            $events[$event->time->toDateTimeString()] = $obj;
        }

        krsort($events);

        return $events;
    }

    public function show(Conversion $conversion)
    {
        $events = $this->eventsPriorConversion($conversion, 10);

        return response()->format([
            'html' => view('beam::conversions.show', [
                'conversion' => $conversion,
                'events' => $events
            ]),
            'json' => new ConversionResource($conversion),
        ]);
    }

    public function upsert(ConversionUpsertRequest $request)
    {
        Log::info('Upserting conversions', ['params' => $request->json()->all()]);

        $conversions = [];
        foreach ($request->get('conversions', []) as $c) {
            // When saving to DB, Eloquent strips timezone information,
            // therefore convert to UTC
            $c['paid_at'] = Carbon::parse($c['paid_at']);
            $conversion = Conversion::firstOrNew([
                'transaction_id' => $c['transaction_id'],
            ]);

            $conversion->fill($c);
            $conversion->save();
            $conversions[] = $conversion;

            if (!$conversion->events_aggregated) {
                ProcessConversionJob::dispatch($conversion);
            }
        }

        return response()->format([
            'html' => redirect(route('conversions.index'))->with('success', 'Conversions created'),
            'json' => ConversionResource::collection(collect($conversions)),
        ]);
    }
}
