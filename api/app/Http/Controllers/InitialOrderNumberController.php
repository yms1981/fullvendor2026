<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInitialOrderNumberRequest;
use App\Http\Requests\UpdateInitialOrderNumberRequest;
use App\Models\InitialOrderNumber;
use Illuminate\Http\Response;

class InitialOrderNumberController extends Controller
{
    protected $resourceRoute = 'initialordernumber';

    protected $defaultOrderProperty = 'company_id';

    public function index()
    {
        return parent::index();
    }

    public function store(StoreInitialOrderNumberRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    public function show($initialordernumber)
    {
        return $this->makeResource($this->getMainModelInstance($initialordernumber));
    }

    public function update(UpdateInitialOrderNumberRequest $request, $initialordernumber)
    {
        $item = $this->getMainModelInstance($initialordernumber);

        return $this->updateModel($item, $request->toArray());
    }

    public function destroy($initialordernumber): ?Response
    {
        $item = $this->getMainModelInstance($initialordernumber);
        $this->authorize('delete', $item);

        return $this->destroyResourceEmptyResponse($item);
    }
}
