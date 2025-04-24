<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Http\Requests\BannerOneTimeDisplayRequest;
use Remp\CampaignModule\Http\Requests\BannerRequest;
use Remp\CampaignModule\Http\Resources\BannerResource;
use Remp\CampaignModule\Http\Showtime\Showtime;
use Remp\CampaignModule\Models\ColorScheme\Map as ColorSchemeMap;
use Remp\CampaignModule\Models\Dimension\Map as DimensionMap;
use Remp\CampaignModule\Models\Position\Map as PositionMap;
use Remp\CampaignModule\Models\Alignment\Map as AlignmentMap;
use Remp\CampaignModule\Snippet;
use Carbon\Carbon;
use HTML;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class BannerController extends Controller
{
    public function __construct(
        protected DimensionMap $dimensionMap,
        protected PositionMap $positionMap,
        protected AlignmentMap $alignmentMap,
        protected ColorSchemeMap $colorSchemeMap,
        private readonly Showtime $showtime
    ) {
    }

    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 15);

        return response()->format([
            'html' => view('campaign::banners.index'),
            'json' => BannerResource::collection(Banner::paginate($perPage)),
        ]);
    }

    public function json(Datatables $dataTables)
    {
        $banners = Banner::select()
            ->with('campaigns');

        /** @var QueryDataTable $datatable */
        $datatable = $dataTables->of($banners);
        return $datatable
            ->addColumn('actions', function (Banner $banner) {
                return [
                    'show' => route('banners.show', $banner),
                    'edit' => route('banners.edit', $banner),
                    'copy' => route('banners.copy', $banner),
                ];
            })
            ->addColumn('name', function (Banner $banner) {
                return [
                    'url' => route('banners.edit', ['banner' => $banner]),
                    'text' => $banner->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) {
                $query->where('banners.name', 'like', "%{$value}%");
            })
            ->filterColumn('public_id', function (Builder $query, $value) {
                $query->where('banners.public_id', $value);
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

        return view('campaign::banners.create', [
            'banner' => $banner,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
            'colorSchemes' => $this->colorSchemeMap->colorSchemes(),
            'snippets' => Snippet::query()->pluck('value', 'name'),
        ]);
    }

    public function copy(Banner $sourceBanner)
    {
        $sourceBanner->load(
            'htmlTemplate',
            'mediumRectangleTemplate',
            'overlayRectangleTemplate',
            'htmlOverlayTemplate',
            'overlayTwoButtonsSignatureTemplate',
            'barTemplate',
            'collapsibleBarTemplate',
            'shortMessageTemplate',
            'newsletterRectangleTemplate'
        );
        $banner = $sourceBanner->replicate();

        flash(sprintf('Form has been pre-filled with data from banner "%s"', $sourceBanner->name))->info();

        return view('campaign::banners.create', [
            'banner' => $banner,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
            'colorSchemes' => $this->colorSchemeMap->colorSchemes(),
            'snippets' => Snippet::query()->pluck('value', 'name'),
        ]);
    }

    public function validateForm(BannerRequest $request)
    {
        return response()->json(false);
    }

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

    public function show(Banner $banner)
    {
        return response()->format([
            'html' => view('campaign::banners.show', [
                'banner' => $banner->loadTemplate(),
                'positions' => $this->positionMap->positions(),
                'dimensions' => $this->dimensionMap->dimensions(),
                'alignments' => $this->alignmentMap->alignments(),
                'colorSchemes' => $this->colorSchemeMap->colorSchemes(),
                'snippets' => Snippet::query()->pluck('value', 'name'),
            ]),
            'json' => new BannerResource($banner),
        ]);
    }

    public function edit(Banner $banner)
    {
        $banner->loadTemplate();
        $banner->fill(old());

        return view('campaign::banners.edit', [
            'banner' => $banner,
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
            'colorSchemes' => $this->colorSchemeMap->colorSchemes(),
            'snippets' => Snippet::query()->pluck('value', 'name'),
        ]);
    }

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

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->format([
            'html' => redirect(route('banners.index'))->with('success', 'Banner removed'),
            'json' => new BannerResource([]),
        ]);
    }

    public function oneTimeDisplay(BannerOneTimeDisplayRequest $request, Banner $banner)
    {
        $userId = $request->get('user_id');
        $browserId = $request->get('browser_id');
        $expiresInSeconds = Carbon::now()->diffInSeconds(Carbon::parse($request->get('expires_at')));

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
