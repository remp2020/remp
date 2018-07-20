<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    const TYPE_RULE = 'rule';
    const TYPE_EXPLICIT = 'explicit';

    protected $casts = [
        'active' => 'boolean',
        'public' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
    ];

    protected $fillable = [
        'name',
        'code',
        'active',
        'type',
        'public',
    ];

    public function rules()
    {
        return $this->hasMany(SegmentRule::class);
    }

    public function users()
    {
        return $this->hasMany(SegmentUser::class);
    }
}
