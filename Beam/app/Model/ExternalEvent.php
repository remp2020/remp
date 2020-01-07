<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ExternalEvent extends Model
{
    use TableName;

    protected $fillable = [
        'type',
        'external_article_id',
        'title',
        'detail',
        'happened_at',
    ];

    protected $dates = [
        'happened_at',
    ];
}
