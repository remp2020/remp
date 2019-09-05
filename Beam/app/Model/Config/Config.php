<?php

namespace App\Model\Config;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'value',
        'description',
        'type',
        'sorting',
        'autoload',
        'locked',
    ];

    protected $casts = [
        'sorting' => 'integer',
        'autoload' => 'boolean',
        'locked' => 'boolean',
    ];

    public function configCategory()
    {
        return $this->belongsTo(ConfigCategory::class);
    }

    public function scopeUnlocked(Builder $query)
    {
        return $query->where('locked', false);
    }

    public function scopeGlobal(Builder $query)
    {
        return $query->whereNull('property_id');
    }

    public function scopeOfCategory(Builder $query, $categoryCode)
    {
        return $query->whereHas('configCategory', function (Builder $query) use ($categoryCode) {
            $query->where('code', $categoryCode);
        });
    }

    public static function loadByName(string $name)
    {
        $result = Config::where('name', $name)->first();
        if (!$result) {
            throw new \Exception("missing configuration for '$name'");
        }

        switch (mb_strtolower($result->type)) {
            case 'double':
                return (double) $result->value;
            case 'float':
                return (float) $result->value;
            case 'int':
            case 'integer':
                return (int) $result->value;
            case 'bool':
            case 'boolean':
                return (bool) $result->value;
            default:
                return $result->value;
        }
    }
}
