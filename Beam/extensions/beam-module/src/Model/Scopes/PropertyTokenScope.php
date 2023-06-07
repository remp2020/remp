<?php
namespace Remp\BeamModule\Model\Scopes;

use Remp\BeamModule\Model\Property\SelectedProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PropertyTokenScope implements Scope
{
    private $selectedProperty;

    public function __construct(SelectedProperty $selectedProperty)
    {
        $this->selectedProperty = $selectedProperty;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model   $model
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $token = $this->selectedProperty->getToken();
        if ($token) {
            $builder->where('property_token', $token);
        }
    }
}
