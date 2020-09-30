<?php

namespace Remp\LaravelHelpers\Resources;

use Illuminate\Http\Resources\Json\Resource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonResource extends Resource
{
    public function toArray($request)
    {
        if ($this->resource instanceof \Exception) {
            return [
                'message' => $this->resource->getMessage(),
            ];
        }
        if (!isset($this->resource) || is_array($this->resource)) {
            return null;
        }
        return parent::toArray($request);
    }

    public function withResponse($request, $response)
    {
        parent::withResponse($request, $response);

        if ($this->resource instanceof \Exception) {
            if ($this->resource instanceof HttpException) {
                $response->setStatusCode($this->resource->getStatusCode());
            } else {
                $response->setStatusCode(500);
            }
        }
    }

    // Fix for https://github.com/laravel/framework/issues/29916, (currently on Laravel 5.7)
    // TODO: upgrade laravel to newest version and remove implementation of this function
    public function offsetExists($offset){
        return is_array($this->resource) ? isset($this->resource[$offset]) : property_exists($this->resource, $offset);
    }
}