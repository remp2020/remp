<?php

namespace Remp\BeamModule\Http\Controllers;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Remp\BeamModule\Model\Account;
use Yajra\DataTables\DataTables;

class AccountController extends Controller
{
    public function index()
    {
        return view('beam::accounts.index');
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
                return [
                    'url' => route('accounts.edit', ['account' => $account]),
                    'text' => $account->name,
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('beam::accounts.create', [
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

    public function show(Account $account)
    {
        //
    }

    public function edit(Account $account)
    {
        return view('beam::accounts.edit', [
            'account' => $account,
        ]);
    }

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

    public function destroy(Account $account)
    {
        $account->delete();
        return redirect(route('accounts.properties.index', $account))->with('success', 'Account removed');
    }
}
