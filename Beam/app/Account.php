<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
