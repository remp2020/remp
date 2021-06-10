<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends BaseModel
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
