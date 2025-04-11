<?php

namespace Remp\BeamModule\Model\Config;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\BeamModule\Model\BaseModel;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Property\SelectedProperty;

class Config extends BaseModel
{
    protected $fillable = [
        'name',
        'display_name',
        'value',
        'description',
        'type',
        'sorting',
        'config_category_id'
    ];

    protected $casts = [
        'sorting' => 'integer',
        'locked' => 'boolean',
    ];

    private $selectedProperty;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->selectedProperty = resolve(SelectedProperty::class);
    }

    public function configCategory(): BelongsTo
    {
        return $this->belongsTo(ConfigCategory::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function scopeGlobal(Builder $query)
    {
        return $query->whereNull('property_id');
    }

    public function scopeOfSelectedToken(Builder $query)
    {
        $tokenUuid = $this->selectedProperty->getToken();
        if ($tokenUuid) {
            return $query->whereHas('property', function (Builder $query) use ($tokenUuid) {
                $query->where('uuid', $tokenUuid);
            });
        }

        return $query;
    }

    public function scopeOfCategory(Builder $query, $categoryCode)
    {
        return $query->whereHas('configCategory', function (Builder $query) use ($categoryCode) {
            $query->where('code', $categoryCode);
        });
    }

    public function getValue()
    {
        switch (mb_strtolower($this->type)) {
            case 'double':
                return (double) $this->value;
            case 'float':
                return (float) $this->value;
            case 'int':
            case 'integer':
                return (int) $this->value;
            case 'bool':
            case 'boolean':
                return (bool) $this->value;
            default:
                return $this->value;
        }
    }

    public static function loadAllPropertyConfigs(string $name): array
    {
        $configs = Config::with('property')
            ->where('name', $name)
            ->whereNotNull('property_id')
            ->get();

        $results = [];
        foreach ($configs as $config) {
            $results[$config->property->id] = $config->getValue();
        }
        return $results;
    }

    public static function loadByName(string $name, $globalOnly = false)
    {
        $q = Config::where('name', $name);
        $fallback = false;
        // Try to load property config if present
        if (!$globalOnly && in_array($name, ConfigNames::propertyConfigs(), true)) {
            $fallback = true;
            $q = $q->ofSelectedToken();
        }
        $config = $q->first();

        // If not, fallback to global config
        if (!$config && $fallback) {
            $config = Config::where('name', $name)->first();
        }

        if (!$config) {
            throw new \Exception("missing configuration for '$name'");
        }

        return $config->getValue();
    }
}
