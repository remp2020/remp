<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $casts = [
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
    ];

    protected $fillable = [
        'name',
        'code',
        'active',
    ];

    public function rules()
    {
        return $this->hasMany(SegmentRule::class);
    }
}
