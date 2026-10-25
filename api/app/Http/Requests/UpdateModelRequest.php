<?php

namespace App\Http\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class UpdateModelRequest extends FormRequest
{
    abstract public function getResourceRoute(): string;

    protected function getResourceWildcard($resourceRoute)
    {
        return Str::singular($resourceRoute);
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $resourceBaseRoute = $this->getResourceRoute();
        $model = Controller::getModelForRoute($resourceBaseRoute);
        $itemOrId = $this->route($this->getResourceWildcard($resourceBaseRoute));
        if(is_object($itemOrId)) {
            $item = $itemOrId;
        }
        else {

            $item = $model::where(\App::make($model)->getKeyName(),$itemOrId)->first();
        }

        return \Auth::user()->can('update', $item);
    }
}
