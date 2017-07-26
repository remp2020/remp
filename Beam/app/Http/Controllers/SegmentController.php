<?php

namespace App\Http\Controllers;

use App\Http\Requests\SegmentRequest;
use App\Segment;
use App\SegmentRule;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;
use Psy\Util\Json;

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
        $columns = ['id', 'name', 'active', 'code', 'created_at'];
        $segments = Segment::select($columns);

        return $datatables->of($segments)
            ->addColumn('actions', function (Segment $segment) {
                return Json::encode([
                    'edit' => route('segments.edit', $segment),
                ]);
            })
            ->rawColumns(['actions'])
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
        $segment->fill(old());

        return view('segments.create', [
            'segment' => $segment,
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

        foreach ($request->get('rules') as $r) {
            list($category, $event) = explode('|', $r['event']);

            /** @var SegmentRule $rule */
            $rule = SegmentRule::findOrNew($r['id']);
            $rule->timespan = $r['timespan'];
            $rule->count = $r['count'];
            $rule->event_category = $category;
            $rule->event_name = $event;
            $rule->segment_id = $segment->id;
            $rule->fields = $r['fields'];
            $rule->save();
        }

        return redirect(route('segments.index'))->with('success', 'Segment created');
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
     * @param  \App\Segment  $segment
     * @return \Illuminate\Http\Response
     */
    public function edit(Segment $segment)
    {
        return view('segments.edit', [
            'segment' => $segment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Segment  $segment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Segment $segment)
    {
        $this->validate($request, [
            'name' => 'bail|required|unique:accounts|max:255',
        ]);

        $segment->fill($request->all());
        $segment->save();

        foreach ($request->get('rules') as $r) {
            /** @var SegmentRule $rule */
            $rule = SegmentRule::findOrNew($r['id']);
            $rule->timespan = $r['timespan'];
            $rule->count = $r['count'];
            $rule->event_category = $r['event_category'];
            $rule->event_name = $r['event_name'];
            $rule->segment_id = $segment->id;
            $rule->fields = $r['fields'];
            $rule->save();
        }
        SegmentRule::destroy($request->get('removedRules'));

        return redirect(route('segments.index'))->with('success', 'Segment updated');
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
