<?php

namespace App\Model\Property;

use App\Account;
use App\Property;
use InvalidArgumentException;

class SelectedProperty
{
    const SELECTED_PROPERTY_TOKEN_UUID = 'selected_property_token_uuid';

    public function getToken(): ?string
    {
        return \Session::get(self::SELECTED_PROPERTY_TOKEN_UUID);
    }

    /**
     * @param string|null $propertyToken
     * @throws InvalidArgumentException when assigned token is not in DB
     */
    public function setToken(?string $propertyToken)
    {
        if (!$propertyToken) {
            \Session::remove(self::SELECTED_PROPERTY_TOKEN_UUID);
        } else {
            $property = Property::where('uuid', $propertyToken)->first();
            if (!$property) {
                throw new InvalidArgumentException("No such token");
            }
            \Session::put(self::SELECTED_PROPERTY_TOKEN_UUID, $property->uuid);
        }
    }

    public function uiSelectData()
    {
        $selectedPropertyTokenUuid = (new SelectedProperty())->getToken();
        $accountPropertyTokens = [
            [
                'name' => null,
                'tokens' => [
                    [
                        'uuid' => null,
                        'name' => 'All tokens',
                        'selected' => true,
                    ]
                ]
            ]
        ];

        foreach (Account::all() as $account) {
            $tokens = [];
            foreach ($account->properties as $property) {
                $selected = $property->uuid === $selectedPropertyTokenUuid;
                if ($selected) {
                    $accountPropertyTokens[0]['tokens']['selected'] = false;
                }
                $tokens[] = [
                    'uuid' => $property->uuid,
                    'name' => $property->name,
                    'selected' => $selected
                ];
            }

            if (count($tokens) > 0) {
                $accountPropertyTokens[] = [
                    'name' => $account->name,
                    'tokens' => $tokens
                ];
            }
        }
        return $accountPropertyTokens;
    }
}
