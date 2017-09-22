<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['uuid', 'name'];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
