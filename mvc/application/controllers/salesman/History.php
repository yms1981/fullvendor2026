<?php

class History extends Salesman_Controller
{
    use HasPagination;

    protected $accessControlRole = 'seller';

    public function index(): void
    {
        if (!$this->isSellerSetted()) show_404();

        $paginatedList = $this->getPaginatedList(
            "customer/{$this->getCustomerId()}/orders",
            [],
            $this->input->get('page') ?? 1,
            $this->input->get('per_page') ?? 10
        );

        $this->pagination->initialize(
            $this->getCodeIgniterPaginationConfig($paginatedList->total, $paginatedList->per_page,'history')
        );

        $data['page'] = $paginatedList->current_page;
        $data['orders'] = $paginatedList->data;
        $data['info_customer'] = $this->getObjectFromApi("customer/{$this->getCustomerId()}", ['relations' => ['customer_group']]);
        $data['breadcrumb'] = ['Lista Catalogo','Historial']; //localize
        $data['mostrarColumnaseller'] = false;

        $this->showContent('catalogo/CustomerOrders', $data);
    }

    /**
     * Displays the order detail of a given orderId focusing on the detail level needed for history purposes
     * @param $orderId
     * @return void|null
     */
    public function order($orderId = null) {

        if (!$orderId || !$this->isSellerSetted()) show_404();

        $response = $this->getObjectFromApi("order/{$orderId}", ['relations' => ['order_details.product.images','statusOrder']]);

        if ($this->hasError($response)) show_404();

        $dataOrder['data'] = $response->data;

        $dataOrder['info_customer'] = $this->getObjectFromApi("customer/{$this->getCustomerId()}", ['relations' => ['customer_group']]);

        $dataOrder['mostrar_asignados'] = false;
        $dataOrder['mostrarNombreCliente'] = false;
        $dataOrder['mostrarNombreVendedor'] = false;

        $dataOrder['allowedStatus'] = $this->isAllowed($dataOrder['data']->status_order);
        $dataOrder['breadcrumb'] = ['Lista Catalogo','Historial','Detalle de orden']; //localize

        $this->showContent("catalogo/DetalleOrden", $dataOrder);
    }

    private function isAllowed($status): bool
    {
        //Currently, the value that coincides with the states is the property "cod_status"
        return in_array($status->cod_status, [
            11, //OrderStatusCodes::WAREHOUSE_COMPLETED->value
            12 //OrderStatusCodes::COMPLETED->value
        ]);
    }

}
