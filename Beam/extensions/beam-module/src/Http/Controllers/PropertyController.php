<?php

namespace Remp\BeamModule\Http\Controllers;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Remp\BeamModule\Model\Account;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Yajra\DataTables\DataTables;

class PropertyController extends Controller
{
    private $selectedProperty;

    public function __construct(SelectedProperty $selectedProperty)
    {
        $this->selectedProperty = $selectedProperty;
    }

    public function index(Account $account)
    {
        return view('beam::properties.index', [
            'account' => $account,
        ]);
    }

    public function json(Account $account, Request $request, Datatables $datatables)
    {
        $properties = $account->properties()->getQuery();
        return $datatables->of($properties)
            ->addColumn('actions', function (Property $property) use ($account) {
                return [
                    'edit' => route('accounts.properties.edit', [$account, $property]),
                ];
            })
            ->addColumn('name', function (Property $property) use ($account) {
                return [
                    'url' => route('accounts.properties.edit', ['account' => $account, 'property' => $property]),
                    'text' => $property->name,
                ];
            })
            ->rawColumns(['actions'])
            ->setRowId('id')
            ->make(true);
    }

    public function create(Account $account)
    {
        return view('beam::properties.create', [
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
            'name' => 'bail|required|unique:properties|max:255',
        ]);

        $property = new Property();
        $property->fill($request->all());
        $property->uuid = Uuid::uuid4();
        $property->account()->associate($account);
        $property->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'accounts.properties.index',
                    self::FORM_ACTION_SAVE => 'accounts.properties.edit',
                ],
                [$account, $property]
            )->with('success', sprintf('Property [%s] was created', $property->name)),
        ]);
    }

    public function edit(Account $account, Property $property)
    {
        return view('beam::properties.edit', [
            'account' => $account,
            'property' => $property,
        ]);
    }

    public function update(Account $account, Property $property, Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|unique:properties|max:255',
        ]);

        $property->fill($request->all());
        $property->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'accounts.properties.index',
                    self::FORM_ACTION_SAVE => 'accounts.properties.edit',
                ],
                [$account, $property]
            )->with('success', sprintf('Property [%s] was updated', $property->name)),
        ]);
    }

    public function destroy(Property $property, Account $account)
    {
        $property->delete();
        return redirect(route('accounts.properties.index', $account))->with('success', 'Property removed');
    }


    /**
     * Method for switching selected property token
     * Careful, it's not protected by user authentication, since it should be also available from public dashboard
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function switch(Request $request)
    {
        $propertyToken = $request->input('token');

        try {
            $this->selectedProperty->setToken($propertyToken);
        } catch (\InvalidArgumentException $exception) {
            abort('400', 'No such token');
        }

        return back();
    }
}
