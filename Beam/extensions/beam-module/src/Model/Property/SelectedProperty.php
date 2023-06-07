<?php

namespace Remp\BeamModule\Model\Property;

use Remp\BeamModule\Model\Account;
use Remp\BeamModule\Model\Property;
use InvalidArgumentException;
use Remp\Journal\TokenProvider;

class SelectedProperty implements TokenProvider
{
    const SELECTED_PROPERTY_TOKEN_UUID = 'selected_property_token_uuid';

    private $cachedSelectInputData;

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

    public function selectInputData()
    {
        if ($this->cachedSelectInputData) {
            return $this->cachedSelectInputData;
        }

        $selectedPropertyTokenUuid = $this->getToken();
        $accountPropertyTokens = [
            [
                'name' => null,
                'tokens' => [
                    [
                        'uuid' => null,
                        'name' => 'All properties',
                        'selected' => true,
                    ]
                ]
            ]
        ];

        foreach (Account::with('properties')->get() as $account) {
            $tokens = [];
            foreach ($account->properties as $property) {
                $selected = $property->uuid === $selectedPropertyTokenUuid;
                if ($selected) {
                    $accountPropertyTokens[0]['tokens'][0]['selected'] = false;
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
        // Convert to object recursively
        $this->cachedSelectInputData = json_decode(json_encode($accountPropertyTokens));
        return $this->cachedSelectInputData;
    }
}
