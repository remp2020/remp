<?php

namespace App\Model;

use App\Article;
use Illuminate\Database\Eloquent\Model;

class ConversionGeneralEvent extends Model
{
    protected $fillable = [
        'time',
        'action',
        'category',
    ];

    protected $dates = [
        'time',
        'created_at',
        'updated_at',
    ];
}
