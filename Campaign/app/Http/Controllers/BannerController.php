<?php

namespace App\Http\Controllers;

use App\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
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
     */
    public function index()
    {
        return view('banners.index');
    }

    public function json(Datatables $datatables)
    {
        $columns = ['id', 'name', 'width', 'height'];
        $banners = Banner::select($columns);
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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('banners.create', [
            'banner' => new Banner,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:255',
            'storage_uri' => 'bail|required|unique:banners',
            'width' => 'bail|required|integer',
            'height' => 'bail|required|integer',
        ]);

        $banner = new Banner();
        $banner->fill($request->all());
        $banner->uuid = Uuid::uuid4();
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
            'name' => 'bail|required|max:255',
        ]);

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
