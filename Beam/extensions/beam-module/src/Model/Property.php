<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends BaseModel
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    protected static function newFactory(): PropertyFactory
    {
        return PropertyFactory::new();
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
