<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    protected $dates = [
        'starts_at',
        'created_at',
        'updated_at',
    ];
    protected $fillable = [
        'name',
        'mailer_generator_id',
        'segment_code',
        'criteria',
        'articles_count',
        'recurrence_rule',
        'starts_at',
    ];
}
