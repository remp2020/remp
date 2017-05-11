<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Http\Requests\BannerRequest;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Psy\Util\Json;
use Ramsey\Uuid\Uuid;
use Yajra\Datatables\Datatables;

class BannerController extends Controller
{
    const BUCKET = 'banners';

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
     * @internal param Dimensioner $dimensions
     */
    public function index()
    {
        return view('banners.index');
    }

    public function json(Datatables $datatables)
    {
        $banners = Banner::query();
        return $datatables->of($banners)
            ->addColumn('actions', function(Banner $banner) {
                return Json::encode([
                    'show' => route('banners.show', $banner),
                    'edit' => route('banners.edit', $banner) ,
                ]);
            })
            ->rawColumns(['actions'])
            ->setRowId('id')
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $banner = new Banner;
        $banner->fill(old());

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
     * @throws ValidationException
     */
    public function store(BannerRequest $request)
    {
        $banner = new Banner();
        $banner->fill($request->all());

        $banner->save();

        return redirect(route('banners.index'))->with('success', 'Account created');
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
            'positions' => $this->positionMap->positions(),
            'dimensions' => $this->dimensionMap->dimensions(),
            'alignments' => $this->alignmentMap->alignments(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Banner $banner)
    {
        $this->validate($request, [
            'file' => 'bail|mimes:jpeg,jpg,png,gif,bmp,svg',
            'name' => 'bail|required|max:255',
        ]);

        $picture = $request->file('file');
        if ($picture !== null) {
            $this->uploadPicture($picture);
        }

        $banner->fill($request->all());
        $banner->save();

        return redirect(route('banners.index'))->with('success', 'Banner updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Banner  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $account)
    {
        $account->delete();
        return redirect(route('banners.properties.index', $account))->with('success', 'Account removed');
    }

}
