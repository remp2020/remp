<?php

namespace App\Http\Controllers;

use App\Account;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Yajra\Datatables\Datatables;
use Yajra\Datatables\Helper;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('accounts.index');
    }

    public function json(Request $request, Datatables $datatables)
    {
        $columns = array_pluck($request->input('columns'), 'name');
        $columns = array_diff($columns, ['actions']);
        $columns[] = 'id';

        return $datatables->eloquent(Account::select($columns))
            ->addColumn('actions', function(Account $account) {
                return view('accounts._actions', [
                    'account' => $account,
                ]);
            })
            ->rawColumns([2])
            ->removeColumn('id')
            ->make();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('accounts.create', [
            'account' => new Account,
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
            'name' => 'bail|required|unique:accounts|max:255',
        ]);

        $account = new Account();
        $account->fill($request->all());
        $account->uuid = Uuid::uuid4();
        $account->save();

        return redirect(route('accounts.index'))->with('success', 'Account created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function edit(Account $account)
    {
        return view('accounts.edit', [
            'account' => $account,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Account $account)
    {
        $this->validate($request, [
            'name' => 'bail|required|unique:accounts|max:255',
        ]);

        $account->fill($request->all());
        $account->save();

        return redirect(route('accounts.index'))->with('success', 'Account updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $account->delete();
        return redirect(route('accounts.properties.index', $account))->with('success', 'Account removed');
    }
}
