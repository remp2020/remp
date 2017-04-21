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

    public function upload(Request $request)
    {
        $this->validate($request, [
            'file' => 'bail|required',
        ]);
        $picture = $request->file('file');

        $imageSize = getimagesize($picture->getRealPath());
        if ($imageSize === false) {
            return Response::json([
                'error' => true,
                'message' => 'uploaded file is not a valid image',
            ], 400);
        }

        $filename = Uuid::uuid4()->toString() . '.' . $picture->getClientOriginalExtension();
        $path = $picture->storeAs(static::BUCKET, $filename, 'cdn');

        return Response::json([
            'uri' => Storage::disk('cdn')->url($path),
            'width' => $imageSize[0],
            'height' => $imageSize[1],
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param DimensionMap $dimensionMap
     * @param PositionMap $positionMap
     * @param AlignmentMap $alignmentMap
     * @return \Illuminate\Http\Response
     */
    public function create(DimensionMap $dimensionMap, PositionMap $positionMap, AlignmentMap $alignmentMap)
    {
        return view('banners.create', [
            'banner' => new Banner,
            'positions' => $positionMap->positions(),
            'dimensions' => $dimensionMap->dimensions(),
            'alignments' => $alignmentMap->alignments(),
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

    private function uploadPicture(UploadedFile $picture)
    {
        $imageSize = getimagesize($picture->getRealPath());
        if ($imageSize === false) {
            throw new ValidationException('Unsupported type of file');
        }

        $uuid = Uuid::uuid4()->toString();
        $filename = $uuid . '.' . $picture->getClientOriginalExtension();
        $path = $picture->storeAs(static::BUCKET, $filename, 'cdn');

        return [$imageSize[0], $imageSize[1], Storage::disk('cdn')->url($path)];
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
