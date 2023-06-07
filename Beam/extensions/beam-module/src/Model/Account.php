<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Database\Factories\AccountFactory;

class Account extends BaseModel
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
