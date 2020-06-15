<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Helpers\Showtime;
use App\Http\Requests\BannerOneTimeDisplayRequest;
use App\Http\Requests\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;
use Carbon\Carbon;
use HTML;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Yajra\Datatables\Datatables;

class BannerController extends Controller
{
    protected $dimensionMap;
    protected $positionMap;
    protected $alignmentMap;
    private $showtime;

    public function __construct(DimensionMap $dm, PositionMap $pm, AlignmentMap $am, Showtime $showtime)
    {
        $this->dimensionMap = $dm;
        $this->positionMap = $pm;
        $this->alignmentMap = $am;
        $this->showtime = $showtime;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 15);

        return response()->format([
            'html' => view('banners.index'),
            'json' => BannerResource::collection(Banner::paginate($perPage)),
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

        $defaultPositions = $this->positionMap->positions()->first()->style;

        if (is_null($banner->offset_vertical)) {
            $banner->offset_vertical = isset($defaultPositions['top']) ? $defaultPositions['top'] : $defaultPositions['bottom'];
        }

        if (is_null($banner->offset_horizontal)) {
            $banner->offset_horizontal = isset($defaultPositions['left']) ? $defaultPositions['left'] : $defaultPositions['right'];
        }

        return view('banners.create', [
            'banner' => $banner,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
        ]);
    }

    public function copy(Banner $sourceBanner)
    {
        $sourceBanner->load(
            'htmlTemplate',
            'mediumRectangleTemplate',
            'overlayRectangleTemplate',
            'htmlOverlayTemplate',
            'barTemplate',
            'collapsibleBarTemplate',
            'shortMessageTemplate'
        );
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
     * Ajax validate form method.
     *
     * @param BannerRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function validateForm(BannerRequest $request)
    {
        return response()->json(false);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BannerRequest|Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function store(BannerRequest $request)
    {
        $banner = new Banner();
        $banner->fill($request->all());
        $banner->save();

        $templateRelation = $banner->getTemplateRelation();
        $templateRelation->create($request->all());

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
     * @param  \App\Banner $banner
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function show(Banner $banner)
    {
        return response()->format([
            'html' => view('banners.show', [
                'banner' => $banner->loadTemplate(),
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
     * @param  \App\Banner $banner
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function edit(Banner $banner)
    {
        $banner->loadTemplate();
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
     * @throws \Exception
     */
    public function update(BannerRequest $request, Banner $banner)
    {
        $banner->update($request->all());

        $template = $banner->getTemplate();
        $template->update($request->all());

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

    public function oneTimeDisplay(BannerOneTimeDisplayRequest $request, Banner $banner)
    {
        $userId = $request->get('user_id');
        $browserId = $request->get('browser_id');
        $expiresInSeconds = Carbon::parse($request->get('expires_at'))->diffInSeconds(Carbon::now());

        $banner->cache();

        if ($userId) {
            $this->showtime->displayForUser($banner, $userId, $expiresInSeconds);
        }

        if ($browserId) {
            $this->showtime->displayForBrowser($banner, $browserId, $expiresInSeconds);
        }

        return response()->json([
            'status' => 'ok'
        ], 202);
    }
}
