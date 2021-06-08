<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
