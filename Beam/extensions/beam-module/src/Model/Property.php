<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\BeamModule\Database\Factories\PropertyFactory;

class Property extends BaseModel
{
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    protected static function newFactory(): PropertyFactory
    {
        return PropertyFactory::new();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
