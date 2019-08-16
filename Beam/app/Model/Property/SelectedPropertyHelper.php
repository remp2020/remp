<?php

namespace App\Model\Property;

use App\Account;

class SelectedPropertyHelper
{
    public static function selectInputData(SelectedProperty $selectedProperty)
    {
        $selectedPropertyTokenUuid = $selectedProperty->getToken();
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
        return json_decode(json_encode($accountPropertyTokens));
    }
}
