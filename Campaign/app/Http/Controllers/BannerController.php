<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Http\Requests\BannerRequest;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Yajra\Datatables\Datatables;

class BannerController extends Controller
{
    protected $dimensionMap;
    protected $positionMap;
    protected $alignmentMap;

    public function __construct(DimensionMap $dm, PositionMap $pm, AlignmentMap $am)
    {
        $this->dimensionMap = $dm;
        $this->positionMap = $pm;
        $this->alignmentMap = $am;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('banners.index');
    }

    public function json(Datatables $dataTables)
    {
        $banners = Banner::query();
        return $dataTables->of($banners)
            ->addColumn('actions', function (Banner $banner) {
                return [
                    'show' => route('banners.show', $banner),
                    'edit' => route('banners.edit', $banner) ,
                ];
            })
            ->rawColumns(['actions'])
            ->setRowId('id')
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $banner = new Banner;
        $banner->fill(old());

        return view('banners.create', [
            'banner' => $banner,
            'template' => $request->get('template'),
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BannerRequest|Request $request
     * @return \Illuminate\Http\Response
     * @throws ValidationException
     */
    public function store(BannerRequest $request)
    {
        $banner = new Banner();
        $banner->fill($request->all());
        $banner->save();

        switch ($banner->template) {
            case Banner::TEMPLATE_HTML:
                $banner->htmlTemplate()->create($request->all());
                break;
            case Banner::TEMPLATE_MEDIUM_RECTANGLE:
                $banner->mediumRectangleTemplate()->create($request->all());
                break;
            default:
                throw new BadRequestHttpException('unhandled template type: '. $banner->template);
        }

        return redirect(route('banners.index'))->with('success', 'Banner created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        return view('banners.show', [
            'banner' => $banner,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        return view('banners.edit', [
            'banner' => $banner,
            'template' => $banner->template,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BannerRequest|Request $request
     * @param  \App\Banner $banner
     * @return \Illuminate\Http\Response
     */
    public function update(BannerRequest $request, Banner $banner)
    {
        $banner->update($request->all());

        switch ($banner->template) {
            case Banner::TEMPLATE_HTML:
                $banner->htmlTemplate->update($request->all());
                break;
            case Banner::TEMPLATE_MEDIUM_RECTANGLE:
                $banner->mediumRectangleTemplate->update($request->all());
                break;
            default:
                throw new BadRequestHttpException('unhandled template type: '. $banner->template);
        }

        return redirect(route('banners.index'))->with('success', 'Banner updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();
        return redirect(route('banners.index'))->with('success', 'Banner removed');
    }

    public function preview($uuid)
    {
        $banner = Banner::whereUuid($uuid)->first();
        if (!$banner) {
            throw new ResourceNotFoundException("banner [{$uuid}] was not found");
        }
        $positions = $this->positionMap->positions();
        $dimensions = $this->dimensionMap->dimensions();
        $alignments = $this->alignmentMap->alignments();

        return response()
            ->view('banners.preview', [
                'banner' => $banner,
                'positions' => [$banner->position => $positions[$banner->position]],
                'dimensions' => [$banner->dimensions => $dimensions[$banner->dimensions]],
                'alignments' => [$banner->text_align => $alignments[$banner->text_align]],
            ])
            ->header('Content-Type', 'application/x-javascript');
    }
}
