<?php

namespace App\Http\Controllers;

use App\Author;
use App\Console\Commands\AggregateConversionEvents;
use App\Conversion;
use App\Http\Request;
use App\Http\Requests\ConversionRequest;
use App\Http\Requests\ConversionUpsertRequest;
use App\Http\Resources\ConversionResource;
use App\Model\Tag;
use App\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Remp\LaravelHelpers\Resources\JsonResource;
use Yajra\Datatables\Datatables;

class ConversionController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('conversions.index', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
                'tags' => Tag::all()->pluck('name', 'id'),
                'conversionFrom' => $request->get('conversion_from', 'now - 30 days'),
                'conversionTo' => $request->get('conversion_to', 'now'),
            ]),
            'json' => ConversionResource::collection(Conversion::paginate()),
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $conversions = Conversion::select('conversions.*')
            ->with(['article', 'article.authors', 'article.sections', 'article.tags'])
            ->join('articles', 'articles.id', '=', 'conversions.article_id')
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id')
            ->join('article_tag', 'articles.id', '=', 'article_tag.article_id');


        if ($request->input('conversion_from')) {
            $conversions->where('paid_at', '>=', Carbon::parse($request->input('conversion_from'), $request->input('tz'))->tz('UTC'));
        }
        if ($request->input('conversion_to')) {
            $conversions->where('paid_at', '<=', Carbon::parse($request->input('conversion_to'), $request->input('tz'))->tz('UTC'));
        }

        return $datatables->of($conversions)
            ->addColumn('actions', function (Conversion $conversion) {
                return [
                    'show' => route('conversions.show', $conversion),
                ];
            })
            ->addColumn('article.title', function (Conversion $conversion) {
                return \Html::link(route('articles.show', ['article' => $conversion->article->id]), $conversion->article->title);
            })
            ->filterColumn('article.title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', '%' . $value . '%');
            })
            ->filterColumn('article.authors[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('article_author.author_id', $values);
            })
            ->filterColumn('article.sections[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->filterColumn('article.tags[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('article_tag.tag_id', $values);
            })
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

        foreach ($conversion->pageviewEvents()->where('time', '>=', $from)->get() as $event) {
            $obj = new \stdClass();
            $obj->name = 'pageview';
            $obj->time = $event->time;
            $obj->tags = [];

            if ($event->article) {
                $t = new \stdClass();
                $t->title = $event->article->title;
                $t->href = route('articles.show', $event->article->id);
                $obj->tags[] = $t;
            }

            if ($event->timespent) {
                $t = new \stdClass();
                $t->title = "Timespent: {$event->timespent} s";
                $obj->tags[] = $t;
            }

            if ($event->locked === false) {
                $t = new \stdClass();
                $t->title = 'Unlocked';
                $obj->tags[] = $t;
            }

            if ($event->signed_in === false) {
                $t = new \stdClass();
                $t->title = 'Signed in';
                $obj->tags[] = $t;
            }

            $events[$event->time->toDateTimeString()] = $obj;
        }

        foreach ($conversion->commerceEvents()->where('time', '>=', $from)->get() as $event) {
            $obj = new \stdClass();
            $obj->name = "commerce:$event->step";
            $obj->time = $event->time;
            $obj->tags = [];
            $events[$event->time->toDateTimeString()] = $obj;
        }

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
            'html' => view('conversions.show', [
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
            $c['paid_at'] = Carbon::parse($c['paid_at'])->tz('UTC');
            $conversion = Conversion::firstOrNew([
                'transaction_id' => $c['transaction_id'],
            ]);

            $conversion->fill($c);
            $conversion->save();
            $conversions[] = $conversion;

            if (!$conversion->events_aggregated) {
                Artisan::queue(AggregateConversionEvents::COMMAND, [
                    '--conversion_id' => $conversion->id
                ]);
            }
        }

        return response()->format([
            'html' => redirect(route('conversions.index'))->with('success', 'Conversions created'),
            'json' => ConversionResource::collection(collect($conversions)),
        ]);
    }
}
