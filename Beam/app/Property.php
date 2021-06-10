<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends BaseModel
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
