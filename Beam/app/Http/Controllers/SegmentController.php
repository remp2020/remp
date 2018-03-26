<?php

namespace App\Http\Controllers;

use App\Contracts\JournalContract;
use App\Http\Requests\SegmentRequest;
use App\Segment;
use App\SegmentRule;
use HTML;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('segments.index');
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'active', 'code', 'created_at', 'updated_at'];
        $segments = Segment::select($columns);

        return $datatables->of($segments)
            ->addColumn('actions', function (Segment $segment) {
                return [
                    'edit' => route('segments.edit', $segment),
                    'copy' => route('segments.copy', $segment),
                ];
            })
            ->addColumn('name', function (Segment $segment) {
                return HTML::linkRoute('segments.edit', $segment->name, $segment);
            })
            ->rawColumns(['active', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param JournalContract $journalContract
     * @return \Illuminate\Http\Response
     */
    public function create(JournalContract $journalContract)
    {
        $old = old();
        $segment = new Segment();
        $segment->fill($old);

        if (isset($old['rules'])) {
            $rules = [];
            foreach ($old['rules'] as $rule) {
                $rules[] = $segment->rules()->make($rule);
            }
            $segment->setRelation('rules', collect($rules));
        }

        $categories = $journalContract->categories();

        return view('segments.create', [
            'segment' => $segment,
            'categories' => $categories,
        ]);
    }

    public function copy(Segment $sourceSegment, JournalContract $journalContract)
    {
        $segment = new Segment();
        $segment->fill($sourceSegment->toArray());
        $segment->fill(old());

        // user submitted rules, otherwise use rules from source Segment
        $sourceRules = old('rules', $sourceSegment->rules->toArray());
        $rules = [];
        foreach ($sourceRules as $rule) {
            // make sure we only set fillable attributes (no IDs)
            $rules[] = $segment->rules()->make($rule);
        }
        $segment->setRelation('rules', collect($rules));

        $categories = $journalContract->categories();
        flash(sprintf('Form has been pre-filled with data from segment "%s"', $sourceSegment->name))->info();

        return view('segments.create', [
            'segment' => $segment,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SegmentRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(SegmentRequest $request)
    {
        $segment = new Segment();
        $segment->fill($request->all());
        $segment->save();

        foreach ($request->get('rules', []) as $r) {
            $rule = $segment->rules()->create($r);
            $rule->fields = array_filter($rule->fields, function ($item) {
                return !empty($item["key"]);
            });
            $rule->save();
        }

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'segments.index',
                    self::FORM_ACTION_SAVE => 'segments.edit',
                ],
                $segment
            )->with('success', sprintf('Segment [%s] was created', $segment->name)),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Segment  $segment
     * @return \Illuminate\Http\Response
     */
    public function show(Segment $segment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Segment $segment
     * @param JournalContract $journalContract
     * @return \Illuminate\Http\Response
     */
    public function edit(Segment $segment, JournalContract $journalContract)
    {
        $segment->fill(old());
        $categories = $journalContract->categories();

        return view('segments.edit', [
            'segment' => $segment,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param SegmentRequest|Request $request
     * @param  \App\Segment $segment
     * @return \Illuminate\Http\Response
     */
    public function update(SegmentRequest $request, Segment $segment)
    {
        $segment->fill($request->all());
        $segment->save();

        foreach ($request->get('rules', []) as $r) {
            /** @var SegmentRule $rule */
            $rule = $segment->rules()->findOrNew($r['id']);
            $rule->fill($r);
            $rule->fields = array_filter($rule->fields, function ($item) {
                return !empty($item["key"]);
            });
            $rule->save();
        }
        SegmentRule::destroy($request->get('removedRules'));

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'segments.index',
                    self::FORM_ACTION_SAVE => 'segments.edit',
                ],
                $segment
            )->with('success', sprintf('Segment [%s] was updated', $segment->name)),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Segment  $segment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Segment $segment)
    {
        $segment->delete();
        return redirect(route('segments.index', $segment))->with('success', 'Segment removed');
    }
}
