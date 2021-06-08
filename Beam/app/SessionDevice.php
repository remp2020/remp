<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Model\Aggregable;
use Illuminate\Database\Eloquent\Model;

class SessionDevice extends Model implements Aggregable
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'time_from',
        'time_to',
        'subscriber',
        'type',
        'model',
        'brand',
        'os_name',
        'os_version',
        'client_type',
        'client_name',
        'client_version',
        'count'
    ];

    public function aggregatedFields(): array
    {
        return ['count'];
    }

    public function groupableFields(): array
    {
        return ['subscriber', 'type', 'model', 'brand', 'os_name', 'os_version', 'client_type', 'client_name', 'client_version'];
    }

    protected $casts = [
        'subscriber' => 'boolean',
    ];
}
