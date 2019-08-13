<?php

namespace App\Model\Property;

use App\Property;

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
}