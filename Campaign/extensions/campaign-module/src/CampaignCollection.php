<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CampaignCollection extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $table = 'collections';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'name' => 'string',
    ];

    /**
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(
            Campaign::class,
            'campaign_collections',
            'collection_id',
            'campaign_id',
        );
    }
}
