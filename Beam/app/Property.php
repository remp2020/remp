<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = ['uuid', 'name'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
