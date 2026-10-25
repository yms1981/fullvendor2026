<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    protected $resourceRoute = 'invoice';

    protected $defaultOrderProperty = 'invoice_id';

    protected $defaultOrderDirection = 'DESC';

    public function index()
    {
        return parent::index();
    }

    public function store(StoreInvoiceRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    public function show($invoice)
    {
        return $this->makeResource($this->getMainModelInstance($invoice));
    }

    public function update(UpdateInvoiceRequest $request, $invoice)
    {
        $item = $this->getMainModelInstance($invoice);

        return $this->updateModel($item, $request->toArray());
    }

    public function destroy($invoice): ?Response
    {
        $item = $this->getMainModelInstance($invoice);
        $this->authorize('delete', $item);

        return $this->destroyResourceEmptyResponse($item);
    }
}
