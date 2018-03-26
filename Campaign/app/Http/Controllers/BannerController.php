<?php

namespace App\Http\Controllers;

use App\Banner;
use App\HtmlTemplate;
use App\Http\Requests\BannerRequest;
use App\Http\Resources\BannerResource;
use App\MediumRectangleTemplate;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;
use HTML;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
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
        return response()->format([
            'html' => view('banners.index'),
            'json' => BannerResource::collection(Banner::paginate()),
        ]);
    }

    public function json(Datatables $dataTables)
    {
        $banners = Banner::select()
            ->with('campaigns')
            ->get();

        return $dataTables->of($banners)
            ->addColumn('actions', function (Banner $banner) {
                return [
                    'show' => route('banners.show', $banner),
                    'edit' => route('banners.edit', $banner),
                    'copy' => route('banners.copy', $banner),
                ];
            })
            ->addColumn('name', function (Banner $banner) {
                return Html::linkRoute('banners.edit', $banner->name, $banner);
            })
            ->addColumn('active', function (Banner $banner) {
                foreach ($banner->campaigns as $campaign) {
                    if ($campaign->active) {
                        return true;
                    }
                }
                return false;
            })
            ->rawColumns(['actions', 'name', 'active'])
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
        $banner->template = $request->get('template');
        $banner->fill(old());

        return view('banners.create', [
            'banner' => $banner,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
        ]);
    }

    public function copy(Banner $sourceBanner)
    {
        $sourceBanner->load('htmlTemplate', 'mediumRectangleTemplate', 'barTemplate', 'shortMessageTemplate');
        $banner = $sourceBanner->replicate();

        flash(sprintf('Form has been pre-filled with data from banner "%s"', $sourceBanner->name))->info();

        return view('banners.create', [
            'banner' => $banner,
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
            case Banner::TEMPLATE_BAR:
                $banner->barTemplate()->create($request->all());
                break;
            case Banner::TEMPLATE_SHORT_MESSAGE:
                $banner->shortMessageTemplate()->create($request->all());
                break;
            default:
                throw new BadRequestHttpException('unhandled template type: '. $banner->template);
        }

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'banners.index',
                    self::FORM_ACTION_SAVE => 'banners.edit',
                ],
                $banner
            )->with('success', sprintf('Banner [%s] was created', $banner->name)),
            'json' => new BannerResource($banner),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        return response()->format([
            'html' => view('banners.show', [
                'banner' => $banner,
                'positions' => $this->positionMap->positions(),
                'dimensions' => $this->dimensionMap->dimensions(),
                'alignments' => $this->alignmentMap->alignments(),
            ]),
            'json' => new BannerResource($banner),
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
        $banner->fill(old());

        return view('banners.edit', [
            'banner' => $banner,
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
            case Banner::TEMPLATE_BAR:
                $banner->barTemplate->update($request->all());
                break;
            case Banner::TEMPLATE_SHORT_MESSAGE:
                $banner->shortMessageTemplate->update($request->all());
                break;
            default:
                throw new BadRequestHttpException('unhandled template type: '. $banner->template);
        }

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'banners.index',
                    self::FORM_ACTION_SAVE => 'banners.edit',
                ],
                $banner
            )->with('success', sprintf('Banner [%s] was updated', $banner->name)),
            'json' => new BannerResource($banner),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Banner $banner
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->format([
            'html' => redirect(route('banners.index'))->with('success', 'Banner removed'),
            'json' => new BannerResource([]),
        ]);
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
