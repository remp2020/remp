<?php

namespace App\Http\Controllers;

use App\Account;
use HTML;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Yajra\Datatables\Datatables;

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
        $columns = ['id', 'name', 'created_at'];
        $accounts = Account::select($columns);

        return $datatables->of($accounts)
            ->addColumn('actions', function (Account $account) {
                return [
                    'edit' => route('accounts.edit', $account),
                ];
            })
            ->addColumn('name', function (Account $account) {
                return HTML::linkRoute('accounts.edit', $account->name, $account);
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

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'accounts.index',
                    self::FORM_ACTION_SAVE => 'accounts.edit',
                ],
                $account
            )->with('success', sprintf('Account [%s] was created', $account->name)),
        ]);
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

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'accounts.index',
                    self::FORM_ACTION_SAVE => 'accounts.edit',
                ],
                $account
            )->with('success', sprintf('Account [%s] was updated', $account->name)),
        ]);
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
