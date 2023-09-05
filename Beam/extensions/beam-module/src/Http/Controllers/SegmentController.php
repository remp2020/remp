<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Http\Requests\SegmentRequest;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentGroup;
use Remp\BeamModule\Model\SegmentRule;
use Html;
use Remp\Journal\JournalContract;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    private $journalContract;

    public function __construct(JournalContract $journalContract)
    {
        $this->journalContract = $journalContract;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('beam::segments.index');
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = ['id', 'name', 'active', 'code', 'created_at', 'updated_at'];
        $segments = Segment::select($columns)
            ->where('segment_group_id', SegmentGroup::getByCode(SegmentGroup::CODE_REMP_SEGMENTS)->id);

        return $datatables->of($segments)
            ->addColumn('actions', function (Segment $segment) {
                return [
                    'edit' => route('segments.edit', $segment),
                    'copy' => route('segments.copy', $segment),
                ];
            })
            ->addColumn('name', function (Segment $segment) {
                return [
                    'url' => route('segments.edit', ['segment' => $segment]),
                    'text' => $segment->name,
                ];
            })
            ->rawColumns(['active', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $segment = new Segment();

        list($segment, $categories) = $this->processOldSegment($segment, old(), $segment->rules->toArray());

        return view('beam::segments.create', [
            'segment' => $segment,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource (beta version of new segment builder).
     *
     * @return \Illuminate\Http\Response
     */
    public function betaCreate()
    {
        $segment = new Segment();

        list($segment, $categories) = $this->processOldSegment($segment, old(), $segment->rules->toArray());

        return view('beam::segments.beta.create', [
            'segment' => $segment,
            'categories' => $categories,
        ]);
    }

    public function copy(Segment $sourceSegment)
    {
        $segment = new Segment();
        $segment->fill($sourceSegment->toArray());

        list($segment, $categories) = $this->processOldSegment($segment, old(), $sourceSegment->rules->toArray());

        flash(sprintf('Form has been pre-filled with data from segment "%s"', $sourceSegment->name))->info();

        return view('beam::segments.create', [
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

        $segment = $this->saveSegment($segment, $request->all(), $request->get('rules'));

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
     * @return \Illuminate\Http\Response
     */
    public function edit(Segment $segment)
    {
        list($segment, $categories) = $this->processOldSegment($segment, old(), $segment->rules->toArray());

        return view('beam::segments.edit', [
            'segment' => $segment,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for editing the specified resource (beta version of new segment builder).
     *
     * @param  \App\Segment $segment
     * @return \Illuminate\Http\Response
     */
    public function betaEdit(Segment $segment)
    {
        list($segment, $categories) = $this->processOldSegment($segment, old(), $segment->rules->toArray());

        return view('beam::segments.beta.edit', [
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
        $segment = $this->saveSegment($segment, $request->all(), $request->get('rules'));

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
     * @param \Remp\BeamModule\Model\Segment $segment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Segment $segment)
    {
        $segment->delete();

        return redirect(route('segments.index', $segment))->with('success', 'Segment removed');
    }

    /**
     * process data from old not valid form
     *
     * @param Segment $segment
     * @param array $old
     * @param array $segmentRules
     * @return array
     */
    public function processOldSegment(Segment $segment, array $old, array $segmentRules)
    {
        $segment->fill($old);
        $rulesData = $old['rules'] ?? $segmentRules;

        $segment['removedRules'] = $old['removedRules'] ?? [];

        if ($rulesData) {
            $rules = [];

            foreach ($rulesData as $rule) {
                $rules[] = $segment->rules()->make($rule);
            }
            $segment->setRelation('rules', collect($rules));
        }

        $categories = collect($this->journalContract->categories());

        return [
            $segment,
            $categories
        ];
    }

    /**
     * save segment and relations
     *
     * @param \Remp\BeamModule\Model\Segment $segment
     * @param array $data
     * @param array $rules
     * @return Segment
     */
    public function saveSegment(Segment $segment, array $data, array $rules)
    {
        if (!array_key_exists('segment_group_id', $data)) {
            $data['segment_group_id'] = SegmentGroup::getByCode(SegmentGroup::CODE_REMP_SEGMENTS)->id;
        }

        $segment->fill($data);
        $segment->save();

        $rules = $rules ?? [];

        foreach ($rules as $rule) {
            $loadedRule = SegmentRule::findOrNew($rule['id']);
            $loadedRule->segment_id = $segment->id;
            $loadedRule->fill($rule);

            $loadedRule->fields = array_filter($loadedRule->fields, function ($item) {
                return !empty($item["key"]);
            });
            $loadedRule->save();
        }

        if (isset($data['removedRules'])) {
            SegmentRule::destroy($data['removedRules']);
        }

        return $segment;
    }

    public function embed(Request $request)
    {
        $segment = null;
        if ($segmentId = $request->get('segmentId')) {
            $segment = Segment::find($segmentId);
        }
        return view('beam::segments.beta.embed', [
            'segment' => $segment,
        ]);
    }
}
