<?php

namespace App\Http\Controllers;

use App\Account;
use App\Property;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Yajra\Datatables\Datatables;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Account $account
     * @return \Illuminate\Http\Response
     * @internal param $accountId
     */
    public function index(Account $account)
    {
        return view('properties.index', [
            'account' => $account,
        ]);
    }

    public function json(Account $account, Request $request, Datatables $datatables)
    {
        $columns = array_pluck($request->input('columns'), 'name');
        $columns = array_diff($columns, ['actions']);
        $columns[] = 'id';
        $columns[] = 'account_id';

        return $datatables->eloquent($account->properties()->getQuery()->select($columns))
            ->addColumn('actions', function(Property $property) {
                return view('properties._actions', [
                    'account' => $property->account,
                    'property' => $property,
                ]);
            })
            ->rawColumns([2])
            ->removeColumn('id')
            ->removeColumn('account_id')
            ->make();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Account $account
     * @return \Illuminate\Http\Response
     */
    public function create(Account $account)
    {
        return view('properties.create', [
            'account' => $account,
            'property' => new Property(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Account $account
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @internal param $accountId
     */
    public function store(Account $account, Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|unique:accounts|max:255',
        ]);

        $property = new Property();
        $property->fill($request->all());
        $property->uuid = Uuid::uuid4();
        $property->account()->associate($account);
        $property->save();

        return redirect(route('accounts.properties.index', $account))->with('success', 'Property created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Property  $property
     * @return \Illuminate\Http\Response
     */
    public function show(Property $property)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Account $account
     * @param  \App\Property $property
     * @return \Illuminate\Http\Response
     */
    public function edit(Account $account, Property $property)
    {
        return view('properties.edit', [
            'account' => $account,
            'property' => $property,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Property $property
     * @param Account $account
     * @return \Illuminate\Http\Response
     */
    public function update(Account $account, Property $property, Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|unique:accounts|max:255',
        ]);

        $property->fill($request->all());
        $property->save();

        return redirect(route('accounts.properties.index', $account))->with('success', 'Property updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Property $property
     * @param Account $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Property $property, Account $account)
    {
        $property->delete();
        return redirect(route('accounts.properties.index', $account))->with('success', 'Property removed');
    }
}
