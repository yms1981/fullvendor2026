<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property Mymodel $mymodel
 * @property Commonmodel $Commonmodel
 * @property CompanyModel $CompanyModel
 * @property Helper $Helper
 * @property ProductModel $ProductModel
 * @property CI_DB_query_builder db
 */
class OrdenModel extends CI_Model
{
    use ApiClient;
    public $company = array();
    const STATUS_SHOPPING = -1;
    const STATUS_PLACED = 0;
    const STATUS_READ = 1;
    const STATUS_PENDING = 2;
    const STATUS_DISPATCHED = 3;
    const STATUS_DELIVERED = 4;
    const STATUS_RETURN_REQUEST = 5;
    const STATUS_RETURNED = 6;
    const STATUS_CANCELED = 7;
    const STATUS_APPROVED = 8;
    const STATUS_WHAREHOUSE_ASSIGNED = 9;
    const STATUS_PARTIALLY_COMPLETED = 10;
    const STATUS_WHAREHOUSE_COMPLETED = 11;
    const STATUS_ORDER_COMPLETED = 12;
    const STATUS_REJECTED = 13;

    const CREDIT_ORDER = "C";
    const STANDARD_ORDER = "D";
    const INVENTORY_ORDER = "I";
    const DECREASE_ON_PRICE = 'Decrease on Price List %';
    const INCREASE_ON_PRICE = 'Increase on Price List %';

    const DISCOUNT_IN_PERCENTAGE = 1;
    const DISCOUNT_IN_AMOUNT = 2;

    protected $saveOrderOnDetailMovement = false;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Helper');
        $this->company = $this->Helper->get_company_info();

        $this->load->model('Commonmodel');
        $this->load->model('CustomerModel');
        $this->load->model('ProductModel');
        $this->load->model('CompanyModel');
        $this->load->model('CatalogoPublicModel');
    }

    function generate_uuid()
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    protected function populateEmptyUUIDs()
    {
        // FULLV-1307 failing unique default UUID fix
        $countResult = $this->db->query("SELECT COUNT(*) as count from orders WHERE UUID is null")->result_array();
        if (current($countResult)['count']>0) {
            $ordersWithoutUUID = $this->db->query("SELECT * from orders WHERE UUID is null")->result_array();
            foreach ($ordersWithoutUUID as $item) {

                $this->db->update('orders',['UUID'=>$this->generate_uuid()],['order_id'=> $item['order_id']]);
            }
        }

    }

    public function previouslyOrderedProductsInAprovedOrders($companyId, $cutomerId,$productId=null) {
        $aprovedStatusesAndBeyond = [
            self::STATUS_DISPATCHED,
            self::STATUS_DELIVERED,
            self::STATUS_APPROVED,
            self::STATUS_ORDER_COMPLETED,
        ];
        return $this->previouslyOrderedProducts($companyId, $cutomerId, $aprovedStatusesAndBeyond, $productId);
    }

    public function previouslyOrderedProducts($comp_id, $customer_id, $statusList = [], $product_id = null){

        $result = $this->getDataFromApi("order/company/{$comp_id}/customer/{$customer_id}/previouslyOrderedProducts",[
            "status_list" => $statusList,
            "product_id" => $product_id
        ]);
        
        // Si hay error (ej. 401 sin autenticación), devolver array vacío
        if (is_array($result) && isset($result['error'])) {
            return [];
        }
        
        return $result ?? [];

    }

    protected function getNextPossibleOrderNumber($companyId,$length,$prefix='ORD') {
        $nextOrderNumber = $this->CompanyModel->getNextOrderNumber($companyId,$prefix);

        return sprintf("%0{$length}d", $nextOrderNumber);
    }

    /**
     * @param $companyId
     * @param $orderType string character between D/C[/I]
     * @param $length
     * @return string
     */
    public function generateOrderNumber($companyId, $orderType='D', $length = 6)
    {
        $prefix = 'ORD';
        if($orderType=='C') $prefix = 'OCN';

        $randomString= $this->getNextPossibleOrderNumber($companyId,$length,$prefix);
        //if($orderType=='I') $prefix = 'INV';
        return "{$prefix}{$randomString}";
    }

    public function update_orderbymovil()
    {
        $compId = $this->company->company_id;
        $query = "SELECT o.order_id FROM orders as o
				  WHERE o.company_id =$compId and source = 'app' and update_bybackend = 0 ";
        //and update_bybackend = 0";

        $orders_bymovil = $this->db->query($query)->result();
        $orderids_implode = implode("','", array_column($orders_bymovil, 'order_id'));

        // obtenemos el detalle de la orden actualizada segundo grupo asignado al cliente
        if ($orders_bymovil) {
            $query_getDetail = "SELECT d.detail_id,o.order_id,g.name, d.product_id, d.sale_price,
								case
								when g.percentage_on_price = 'Decrease on Price List %' then ROUND(p.sale_price - (p.sale_price * (g.percent_price_amount/100)), 2)
								when g.percentage_on_price = 'Increase on Price List %' then ROUND(p.sale_price + (p.sale_price * (g.percent_price_amount/100)), 2)
								when g.percentage_on_price = 'Nothing' then p.sale_price
								ELSE p.sale_price
								END AS sale_price_group
								FROM orders AS o
								INNER JOIN customers as c ON c.customer_id = o.customer_id
								INNER JOIN customer_groups as g ON g.group_id = c.group_id
								INNER JOIN order_details AS d ON o.order_id = d.order_id
								INNER JOIN products AS p ON p.pro_id = d.product_id
								WHERE o.order_id IN ('$orderids_implode')";

            // actualizamos el detalle de las ordenes
            $detalle = $this->db->query($query_getDetail)->result();

            foreach ($detalle as $key => $value) {
                $this->mymodel->update(
                    ["sale_price" => $value->sale_price_group, "sale_price_app" => $value->sale_price],
                    'order_details',
                    ['detail_id' => $value->detail_id]
                );
            }
        }

        // obtenemos datos de la cabecera de la orden actualizada
        $query_orders_updated = "SELECT
								o.order_id, o.discount, o.discount_type AS discount_type_since_movil,
								o.order_number,
								d.product_id,
								o.discount AS discount_since_movil,
								CONCAT(c.discount,'%')  AS discount_customer,
								COUNT(d.detail_id) AS qty_items,
								SUM(d.sale_price * d.qty) AS sub_total,
	    						(SUM(d.sale_price * d.qty) * c.discount / 100) as discount_a,
								SUM(d.sale_price * d.qty) - (SUM(d.sale_price * d.qty) * c.discount / 100) AS total
								FROM orders AS o
								LEFT JOIN order_details AS d ON d.order_id = o.order_id
								INNER JOIN customers as c ON c.customer_id = o.customer_id
								WHERE o.order_id IN('$orderids_implode')
								GROUP BY o.order_id";

        $result_orders_updated = $this->db->query($query_orders_updated)->result();

        foreach ($result_orders_updated as $key => $order) {
            $this->mymodel->update([
                                       'amount' => $order->sub_total,
                                       'total_amount' => $order->total,
                                       'discount_a' => $order->discount_a,
                                       'update_bybackend' => 1,
                                       'discount' => $order->discount_customer,
                                       'discount_movil' => $order->discount,
                                       'discount_type_since_movil' => $order->discount_type_since_movil,
                                       'discount_type' => OrdenModel::DISCOUNT_IN_PERCENTAGE
                                   ], 'orders', ['order_id' => $order->order_id]);
        }

        //echo json_encode($result_orders_updated);
        return $result_orders_updated;
    }


    public function get_orders(
        $compId,
        $desde_fecha,
        $hasta_fecha,
        $seller_id,
        $origin,
        $status,
        $categoy,
        $grupo_id,
        $search,
        $order_sort,
        $order_type,
        $show_per_page,
        $offset,
        $catalog_id
    ) {


        $consulta_adicional = null;
        $consulta = "SELECT o.*,s.sombrilla,s.color_icono,c.business_name as nombre_cliente, c.name as nombre_contacto,
		             (SELECT g.name from customer_groups AS g where g.group_id = c.group_id LIMIT 1) as grupo_cliente,
					 (SELECT CONCAT(first_name,' ',last_name) FROM seller WHERE user_id=o.user_id) as seller_info,
					 (SELECT CONCAT(first_name,' ',last_name) FROM warehouse_users WHERE user_id=o.warehouse_user_id)
					 as warehouse_user
					 FROM myorders as o INNER JOIN customers as c ON o.customer_id = c.customer_id
					 LEFT JOIN seller AS v ON o.user_id = v.user_id
					 INNER JOIN status_orders as s ON o.order_status = s.cod_status WHERE o.company_id = $compId ";

        if ($desde_fecha != '' && $desde_fecha != 'Invalid date') {
            $desde_fecha = date("Y-m-d", strtotime($desde_fecha));
            $consulta_adicional .= "AND o.fechaorden > '$desde_fecha'";
        } else {
            $fecha = date('Y-m-01');
            $fecha = date('Y-m-d', strtotime($fecha . '-1 days'));
            $consulta_adicional .= "AND o.fechaorden > '$fecha'";
        }

        if ($hasta_fecha != '' && $hasta_fecha != 'Invalid date') {
            $hasta_fecha = date("Y-m-d", strtotime($hasta_fecha . "+ 1 days"));
            $consulta_adicional .= " AND o.fechaorden < '$hasta_fecha'";
        }

        if (count($seller_id) > 0) {
            $string_sellers = implode("','", $seller_id);
            $consulta_adicional .= " AND o.user_id in ('$string_sellers')";
        }

        switch ($origin) {
            case 'todo':
                $consulta_adicional .= "";
                break;
            case 'Catalog':
                $consulta_adicional .= " AND o.source = 'Catalog'";
                break;
            case 'app':
                $consulta_adicional .= " AND o.source = 'app'";
                break;
            case 'manual':
                $consulta_adicional .= " AND o.source = 'manual'";
                break;
            case 'Marketplace':
                $consulta_adicional .= " AND o.source = 'Marketplace'";
                break;
        }
        $count = 0;

        if (count($status) > 0) {
            $string_estados = implode("','", $status);
            $consulta_adicional .= " AND o.order_status in ('$string_estados')";
        }
        if ($grupo_id != "" && is_numeric($grupo_id) && $grupo_id != "todo") {
            $consulta_adicional .= " AND c.group_id = '$grupo_id'";
        }

        if ($catalog_id != "" && is_numeric($catalog_id) && $catalog_id != "todo") {
            $consulta_adicional .= " AND o.catalog_id = $catalog_id";
        }

        if ($search != '') {
            $consulta_adicional .= " AND (o.order_number LIKE '%$search%' OR c.business_name LIKE '%$search%' OR (CONCAT(v.first_name,' ',v.last_name) like '%$search%'))";
        }

        if ($order_type != '' && $order_sort != '') {
            switch ($order_sort) {
                case 'orden_number':
                    $consulta_adicional .= " ORDER BY o.order_number " . $order_type;
                    break;
                case 'orden_customer':
                    $consulta_adicional .= " ORDER BY c.business_name " . $order_type;
                    break;
                case 'orden_group':
                    $consulta_adicional .= " ORDER BY (SELECT g.name from customer_groups AS g where g.group_id = c.group_id LIMIT 1) " . $order_type;
                    break;
                case 'orden_seller':
                    $consulta_adicional .= " ORDER BY CONCAT(v.first_name,' ',v.last_name) " . $order_type;
                    break;
                case 'orden_warehouseuser':
                    $consulta_adicional .= " ORDER BY (SELECT CONCAT(first_name,' ',last_name) FROM warehouse_users WHERE user_id=o.warehouse_user_id) " . $order_type;
                    break;
                case 'orden_date':
                    $consulta_adicional .= " ORDER BY o.fechaorden " . $order_type;
                    break;
                case 'orden_total':
                    $consulta_adicional .= " ORDER BY (SELECT sum(od.qty*od.sale_price) FROM order_details as od WHERE od.order_id= o.order_id) " . $order_type;
                    break;
                case 'orden_comments':
                    $consulta_adicional .= " ORDER BY o.order_comments " . $order_type;
                    break;
            }
        } else {
            $consulta_adicional .= " ORDER BY o.fechaorden DESC";
        }

        if ($show_per_page == 'sin_limite' && $offset == 'sin_limite') {
        } else {
            $consulta_adicional .= " LIMIT " . $show_per_page . " OFFSET " . $offset;
        }

        $consulta .= $consulta_adicional;
        $result = $this->db->query($consulta)->result();

        return $result;
    }

    public function obtener_cantidad(
        $compId,
        $desde_fecha,
        $hasta_fecha,
        $seller_id,
        $origin,
        $status,
        $categoy,
        $grupo_id,
        $search,
        $order_sort,
        $order_type,
        $catalog_id
    ) {
        $consulta_adicional = null;
        $consulta = "SELECT o.*,c.business_name as nombre_cliente, c.name as nombre_contacto,(SELECT g.name from customer_groups AS g where g.group_id = c.group_id LIMIT 1) as grupo_cliente,(SELECT CONCAT(first_name,' ',last_name) FROM seller WHERE user_id=o.user_id) as seller_info,(SELECT CONCAT(first_name,' ',last_name) FROM warehouse_users WHERE user_id=o.warehouse_user_id) as warehouse_user FROM myorders as o INNER JOIN customers as c ON o.customer_id = c.customer_id LEFT JOIN seller AS v ON o.user_id = v.user_id INNER JOIN status_orders as s ON o.order_status = s.cod_status WHERE o.company_id = '$compId'";

        if ($desde_fecha != '' && $desde_fecha != 'Invalid date') {
            $desde_fecha = date("Y-m-d", strtotime($desde_fecha . "- 1 days"));
            $consulta_adicional .= "AND o.fechaorden > '$desde_fecha'";
        } else {
            $fecha = date('Y-m-01');
            $fecha = date('Y-m-d', strtotime($fecha . '-1 days'));
            $consulta_adicional .= " AND o.fechaorden > '$fecha'";
        }

        if ($hasta_fecha != '' && $hasta_fecha != 'Invalid date') {
            $hasta_fecha = date("Y-m-d", strtotime($hasta_fecha . "+ 1 days"));
            $consulta_adicional .= " AND o.fechaorden < '$hasta_fecha'";
        }

        if (count($seller_id) > 0) {
            $string_sellers = implode("','", $seller_id);
            $consulta_adicional .= " AND o.user_id in ('$string_sellers')";
        }

        switch ($origin) {
            case 'todo':
                $consulta_adicional .= "";
                break;
            case 'Catalog':
                $consulta_adicional .= " AND o.source = 'Catalog'";
                break;
            case 'app':
                $consulta_adicional .= " AND o.source = 'app'";
                break;
            case 'manual':
                $consulta_adicional .= " AND o.source = 'manual'";
                break;
        }

        $count = 0;
        if (count($status) > 0) {
            $string_estados = implode("','", $status);
            $consulta_adicional .= " AND o.order_status in ('$string_estados')";
        }

        if ($grupo_id != "" && is_numeric($grupo_id) && $grupo_id != "todo") {
            $consulta_adicional .= " AND c.group_id = '$grupo_id'";
        }

        if ($catalog_id != "" && is_numeric($catalog_id) && $catalog_id != "todo") {
            $consulta_adicional .= " AND o.catalog_id = $catalog_id";
        }

        if ($search != '') {
            $consulta_adicional .= " AND (o.order_number LIKE '%$search%' OR c.business_name like '%$search%' OR (CONCAT(v.first_name,' ',v.last_name) like '%$search%'))";
        }

        $consulta .= $consulta_adicional;
        $result = $this->db->query($consulta)->num_rows();
        return $result;
    }

    public function obtener_status_orders($langId = null, $tipo_d = null, $cod_status = null)
    {
        $resoruce = "statusorder";
        if (isset($cod_status)) {
            $resoruce .= "/" . $cod_status;
        }
        $query=[];

        if(isset($tipo_d))
            $query['tipo_d'] = $tipo_d;

        if(isset($langId))
            $query['language_id'] = $langId;

        return $this->getDataFromApi($resoruce, $query);
    }

    public function get_status($cod_status)
    {
        return $this->mymodel->get_by("status_orders", true, ["cod_status" => $cod_status]);
    }

    public function getById($id)
    {
        return $this->mymodel->get_by("orders", true, ["company_id" => $this->company->company_id, "order_id" => $id]);
    }

    /**
     * Makes in memory an object thar represents an order, useful for creation and update
     * @param $userId
     * @param $customerId
     * @param $companyId
     * @param null $uuid
     * @param string $orderType
     * @param $discountType
     * @param $discount
     * @param $orderComments
     * @param $source
     * @param $status
     * @param $internalNotes
     * @param $orderNumber
     * @param $proforma
     * @return object
     */
    public function makeOrder(
        $userId,
        $customerId,
        $companyId,
        $uuid = null,
        string $orderType=OrdenModel::STANDARD_ORDER,
        $discountType=OrdenModel::DISCOUNT_IN_PERCENTAGE,
        $discount=0,
        $orderComments="",
        $source=null,
        $status=OrdenModel::STATUS_PLACED,
        $internalNotes="",
        $orderNumber=null
    ): object {
        if(!isset($uuid))
            $uuid = $this->generate_uuid();

        $orderNumber = isset($orderNumber)
            ?$orderNumber
            :$this->getDataFromApi("order/company/{$companyId}/getOrderNumber/orderType/{$orderType}")['order_number'];
        $order = [
            'UUID' => $uuid,
            'tipo_d' => $orderType,
            'user_id' => $userId,
            'internal_notes' => $internalNotes,
            'discount_type' => $discountType,
            'customer_id' => $customerId,
            'discount' => $discount,
            'order_status' => $status,
            'company_id' => $companyId,
            'order_number' => $orderNumber,
            'created' => date('Y-m-d h:i:s'),
            'details' => [],
            'order_comments'=>$orderComments
        ];
        if (isset($source)) {
            $order['source'] = $source;
        }
        return $this->Helper->cast($order);
    }

    /**
     * Makes an updated orderDetails array from existing merging (adding|replacing or deleting) each orderDetail from $orderDetailsToUpdateOrAdd
     *
     * This method will create a new array with every element from $existingOrderDetails except for the ones that exist on
     * orderDetailsToUpdateOrAdd, if qty is 0 it won't be added to the new array, otherwise the order_detail will be replaced
     * This is useful for using the crudOrderDetails method for updates requested with partial orderDetails instead of the full
     * updated orderDetails list of the order
     *
     * @param $existingOrderDetails
     * @param $orderDetailsToUpdateOrAdd
     * @return array
     */
    public function makeUpdatedOrderDetails($existingOrderDetails,$orderDetailsToUpdateOrAdd) {
        $updatedOrderDetails=[];

        foreach($orderDetailsToUpdateOrAdd as $orderDetailToUpdateOrAdd) {
            foreach($existingOrderDetails as $existingDetail) {
                if($existingDetail['product_id']==$orderDetailToUpdateOrAdd['product_id'])
                {
                    if($orderDetailToUpdateOrAdd['qty']!=0)
                    {
                        $updatedOrderDetails[]=$orderDetailToUpdateOrAdd;
                    }
                }else
                    $updatedOrderDetails[]=$existingDetail;
            }
        }
        return $updatedOrderDetails;
    }

    public function formatNumber($number, $formatNumeric = null)
    {
        $formatNumeric = $formatNumeric ?: $this->format_numeric;
        if (!is_object($formatNumeric) || !isset($formatNumeric->digits, $formatNumeric->radixPoint, $formatNumeric->groupSeparator)) {
            return number_format((float) $number, 2, '.', ',');
        }
        return number_format(
            $number,
            (int) $formatNumeric->digits,
            $formatNumeric->radixPoint,
            $formatNumeric->groupSeparator
        );
    }

    protected function getDiscountedAmount($amount, $discountType, $discount)
    {
        if ($discountType == OrdenModel::DISCOUNT_IN_PERCENTAGE) {
            return ($amount * ($discount / 100));
        }
        if ($discountType == OrdenModel::DISCOUNT_IN_AMOUNT) {
            return $discount;
        }
        return 0;
    }

    /**
     * Recalculates the amounts and discounts and updates it onto $order
     * @param $order
     * @param $customer
     * @return array the details list
     *
     */
    public function updateDetailsPrices($order, $orderDetails = null, $customer = null)
    {
        $order->amount = 0;
        if (!isset($orderDetails)) {
            $orderDetails = $order->details;
        }
        $processedDetails = [];
        if (count($orderDetails) > 0) {
            foreach ($orderDetails as $detail) {
                $detail = $this->Helper->cast($detail);

                if ($order->recalcular_precio && $customer) {
                    $detail->sale_price = $this->getDataFromApi("customer/{$customer->customer_id}/product/{$detail->id_producto}/price");
                    $detail->name_input_price = 'detalle[' . $detail->row . '][sale_price]';
                }

                $detail->sale_price_recalculado = $this->formatNumber($detail->sale_price);
                $detail->cantidad = $detail->qty ?: 0;
                $totalImporte = $detail->cantidad * $detail->sale_price;
                $order->amount += $totalImporte;
                $detail->total_importe_muestra = $totalImporte;

                $detail->digits = $this->format_numeric->digits;
                $detail->radixPoint = $this->format_numeric->radixPoint;
                $detail->groupSeparator = $this->format_numeric->groupSeparator;
                $detail->total_importe = $this->formatNumber($totalImporte);
                $detail->sin_convertir = $detail->qty;
                if(isset($detail->row))
                    $detail->name_input = 'detalle[' . $detail->row . '][importe_total]';
                $processedDetails[] = $detail;

            }
        }
        $order->details = $processedDetails;
        $this->updateDiscountAmounts($order);
        return $processedDetails;
    }

    /**
     * Updates the discount and total amount of the order according to the order discount type and discount value
     * @param $order
     * @return mixed
     */
    public function updateDiscountAmounts($order)
    {
        $discountType = $order->discount_type;
        $discount = $order->discount;
        $order->discount_a = $this->getDiscountedAmount($order->amount, $discountType, $discount);
        $order->total_amount = $order->amount - $order->discount_a;
        return $order;
    }

    /**
     * Adds an order detail to order
     * @param $orderDetail
     * @param $customer
     * @param $order
     * @param bool $isBackup If set to true the order detail is added to the backup table
     * @return void
     */
    public function addOrderDetail($orderDetail, $customer, $order,$isBackup=false)
    {
        $orderDetailsTable=(!$isBackup)?'order_details':"bakorder_details";
        if ($orderDetail['qty'] != 0) {
            $orderDetailData = $this->getOrderDetailData($order->order_id, $customer, $orderDetail);

            if($this->mymodel->save($orderDetailsTable, $orderDetailData)) {
                $detail_id = $this->db->insert_id();
                $order->details[] = $this->mymodel->get($orderDetailsTable, true, 'detail_id',$detail_id);
                $this->updateDetailsPrices($order);
                $this->moveStock($orderDetail["id_producto"], $orderDetail['qty']);

                if ($this->saveOrderOnDetailMovement) {
                    $this->mymodel->update($order, 'orders', ['order_id' => $order->order_id]);
                }
            }
        }
    }

    public function makeAddOrderDetail($orderDetail, $customer, $order)
    {

        if ($orderDetail['qty'] != 0) {
            $orderDetailData = $this->getOrderDetailData($order, $customer, $orderDetail);
            return $orderDetailData;
        }
        return [];
    }

    /**
     * @param $orderDetail
     * @param $order
     * @param bool $isBackup If set to true the order detail is removed from the backup table
     * @return void
     */
    public function removeOrderDetail($orderDetail, $order,$isBackup=false)
    {
        $orderDetailsTable=(!$isBackup)?'order_details':"bakorder_details";
        if ($this->mymodel->delete($orderDetailsTable, ["detail_id" => $orderDetail->detail_id])) {
            foreach ($order->details as $k => $oD) {
                if ($oD->detail_id == $orderDetail->detail_id) {
                    unset($order->details[$k]);
                    break;
                }
            }

            $this->moveStock($orderDetail->product_id, $orderDetail->qty, false);
            $this->updateDetailsPrices($order);
            if($this->saveOrderOnDetailMovement)
                $this->mymodel->update($order, 'orders', ['order_id' => $order->order_id]);
        }
    }

    /**
     * @param $orderDetailActual
     * @param $orderDetail
     * @param $customer
     * @param $order
     * @param bool $isBackup If set to true the order detail is updated to the backup table
     * @return void
     */
    public function updateOrderDetail($orderDetailActual,$orderDetail, $customer, $order,$isBackup=false)
    {
        $orderDetailsTable=(!$isBackup)?'order_details':"bakorder_details";
        if ($orderDetail['qty'] != 0) {

            $item_a_modificar = $this->getOrderDetailData($order->order_id, $customer, $orderDetail);
            if($this->mymodel->update($item_a_modificar, $orderDetailsTable, ['detail_id' => $orderDetailActual->detail_id])) {
                $orderDetailModificado = $this->mymodel->get($orderDetailsTable, true, 'detail_id',$orderDetailActual->detail_id);;
                foreach ($order->details as $k => $oD) {
                    if ($oD->detail_id == $orderDetailModificado->detail_id) {
                        $order->details[$k] = $orderDetailModificado;
                        break;
                    }
                }

                $this->moveStock($orderDetailActual->product_id, $orderDetail['qty'] - $orderDetailActual->qty);
            }
            $this->updateDetailsPrices($order);
            if($this->saveOrderOnDetailMovement)
                $this->mymodel->update($order, 'orders', ['order_id' => $order->order_id]);
        }
    }

    public function getOrderDetailData($order, $customer, $itemActualizar)
    {
        $today = date('Y-m-d h:i:s');
        $cantidad = $itemActualizar['qty'];
        $sale_price = $itemActualizar['sale_price'];
        $producto_exitDatailOrden = isset($order->order_id) ? $this->getObjectFromApi("order/{$order->order_id}/product/{$itemActualizar['id_producto']}"):null;
        $product = $this->getObjectFromApi("product/{$itemActualizar['id_producto']}");
        $created = $today;
        $modify_date = null;
        $fixed_pricedate = null;

        if ($producto_exitDatailOrden) {
            $created = $producto_exitDatailOrden->created;
            $modify_date = $producto_exitDatailOrden->modify_date;
            $fixed_pricedate = null;

            if ($producto_exitDatailOrden->sale_price != $sale_price) {
                $modify_date = $today;
            }
        }

        if (isset($itemActualizar['fixed_price'])) {
            $this->modificar_preciolista_producto($customer, $sale_price, $itemActualizar);
            $fixed_pricedate = $today;
        }

        return [
            // 'order_id' => $order_id,
            'product_id' => (int) $itemActualizar['id_producto'],
            'qty' => $cantidad,
            'sale_price' => $sale_price,
            'purchase_price' => 0,
            'comments' => $itemActualizar['comments'] ? $itemActualizar['comments'] : "-",
            'created' => $created,
            'modify_date' => $modify_date,
            'fixed_pricedate' => $fixed_pricedate,
            'groupcustomer' => $customer->group_name,
            'tipolista' => $customer->percentage_on_price,
            'perc_price' => $customer->percent_price_amount,
            'salesp' => $product->sale_price,
            'totalprice' => $sale_price,
            'delivered_quantity' => 0,
            'discount' => 0,
            'comment'=>'-',
            'id_kor' => 0,
        ];
    }

    public function modificar_preciolista_producto($customer, $sale_price, $itemActualizar)
    {
        $sale_priceInverso = $this->revertirPrecio($customer, $sale_price);
        $this->post("product/{$itemActualizar['id_producto']}", [
            'sale_price' => $sale_priceInverso
        ]);
//        $this->mymodel->update(['sale_price' => $sale_priceInverso],
//                               'products',
//                               ['company_id' => $this->company->company_id, 'pro_id' => $itemActualizar['id_producto']]
//        );
    }

    public function is_validtoedit($id)
    {
        $order = $this->getById($id);
        $status_order = $this->get_status($order->order_status);

        if ($status_order && $status_order->cannot_edit == 1) {
            return false;
        }

        return true;
    }

    public function all()
    {
        return $this->db->query("SELECT * FROM orders")->result();
    }

    public function obtener_orders($request)
    {
        //$desde_fecha,$hasta_fecha,$seller_id,$origin,$status,$categoy,$grupo_id,$search,$order_sort,$order_type,$show_per_page,$offset
        $id_company = $this->company->company_id;
        $request = $this->Heler->cast($request);
        $consulta_adicional = null;
        $consulta = "SELECT o.*,s.sombrilla,s.color_icono,c.business_name as nombre_cliente, c.name as nombre_contacto,
		             (SELECT g.name from customer_groups AS g where g.group_id = c.group_id LIMIT 1) as grupo_cliente,
					 (SELECT CONCAT(first_name,' ',last_name) FROM seller WHERE user_id=o.user_id) as seller_info,
					 (SELECT CONCAT(first_name,' ',last_name) FROM warehouse_users WHERE user_id=o.warehouse_user_id) as warehouse_user
					 FROM myorders as o
					 INNER JOIN customers as c ON o.customer_id = c.customer_id
					 LEFT JOIN seller AS v ON o.user_id = v.user_id
					 INNER JOIN status_orders as s ON o.order_status = s.cod_status
					 WHERE o.company_id = $id_company";

        if ($request->desde_fecha != '' && $request->desde_fecha != 'Invalid date') {
            $desde_fecha = date("Y-m-d", strtotime($request->desde_fecha . "- 1 days"));
            $consulta_adicional .= "AND o.fechaorden > '$desde_fecha'";
        } else {
            $fecha = date('Y-m-01');
            $fecha = date('Y-m-d', strtotime($fecha . '-1 days'));
            $consulta_adicional .= "AND o.fechaorden > '$fecha'";
        }

        if ($request->hasta_fecha != '' && $request->hasta_fecha != 'Invalid date') {
            $hasta_fecha = date("Y-m-d", strtotime($request->hasta_fecha . "+ 1 days"));
            $consulta_adicional .= " AND o.fechaorden < '$hasta_fecha'";
        }

        if (count($request->seller_id) > 0) {
            $string_sellers = implode("','", $request->seller_id);
            $consulta_adicional .= " AND o.user_id in ('$string_sellers')";
        }

        switch ($request->origin) {
            case 'todo':
                $consulta_adicional .= "";
                break;
            case 'Catalog':
                $consulta_adicional .= " AND o.source = 'Catalog'";
                break;
            case 'app':
                $consulta_adicional .= " AND o.source = 'app'";
                break;
            case 'manual':
                $consulta_adicional .= " AND o.source = 'manual'";
                break;
        }

        if (count($request->status) > 0) {
            $string_estados = implode("','", $request->status);
            $consulta_adicional .= " AND o.order_status in ('$string_estados')";
        }

        if ($request->grupo_id != "" && is_numeric($request->grupo_id) && $request->grupo_id != "todo") {
            $consulta_adicional .= " AND c.group_id = $request->grupo_id";
        }

        if ($request->search != '') {
            $consulta_adicional .= " AND (o.order_number LIKE '%$request->search%' OR c.business_name LIKE '%$request->search%' OR (CONCAT(v.first_name,' ',v.last_name) like '%$request->search%'))";
        }

        if ($request->order_type != '' && $request->order_sort != '') {
            switch ($request->order_sort) {
                case 'orden_number':
                    $consulta_adicional .= " ORDER BY o.order_number " . $request->order_type;
                    break;
                case 'orden_customer':
                    $consulta_adicional .= " ORDER BY c.business_name " . $request->order_type;
                    break;
                case 'orden_group':
                    $consulta_adicional .= " ORDER BY (SELECT g.name from customer_groups AS g where g.group_id = c.group_id LIMIT 1) " . $request->order_type;
                    break;
                case 'orden_seller':
                    $consulta_adicional .= " ORDER BY CONCAT(v.first_name,' ',v.last_name) " . $request->order_type;
                    break;
                case 'orden_warehouseuser':
                    $consulta_adicional .= " ORDER BY (SELECT CONCAT(first_name,' ',last_name) FROM warehouse_users WHERE user_id=o.warehouse_user_id) " . $request->order_type;
                    break;
                case 'orden_date':
                    $consulta_adicional .= " ORDER BY o.fechaorden " . $request->order_type;
                    break;
                case 'orden_total':
                    $consulta_adicional .= " ORDER BY (SELECT sum(od.qty*od.sale_price) FROM order_details as od WHERE od.order_id= o.order_id) " . $request->order_type;
                    break;
                case 'orden_comments':
                    $consulta_adicional .= " ORDER BY o.order_comments " . $request->order_type;
                    break;
            }
        } else {
            $consulta_adicional .= " ORDER BY o.fechaorden DESC";
        }

        if ($request->show_per_page == 'sin_limite' && $request->offset == 'sin_limite') {
        } else {
            $consulta_adicional .= " LIMIT " . $request->show_per_page . " OFFSET " . $request->offset;
        }

        $consulta .= $consulta_adicional;
        $result = $this->db->query($consulta)->result();
        return $result;
    }


    public function convertcart_order($cart)
    {
        $customer = $this->CustomerModel->getCustomer($cart->customer_id);
        $group = $this->CustomerModel->get_group($customer->customer_id);
        $date = date("Y-m-d H:i:s");

        $calculo = $this->CatalogoPublicModel->calcular_total_ajax($cart->token_kor, $with_format = false);

        $insert = $this->mymodel->add_details('orders', array(
            'company_id' => $this->company->company_id,
            'user_id' => $cart->user_id,
            'customer_id' => $cart->customer_id,
            'order_number' => $this->generateOrderNumber($this->company->company_id),
            'created' => $date,
            'source' => "Catalog",
            'discount' => ($customer->discount <= 0) ? 0 : $customer->discount,
            'discount_type' => OrdenModel::DISCOUNT_IN_PERCENTAGE,
            'portal_vendor' => $cart->login_seller ? 1 : null,
            'amount' => $calculo['sub_total'],
            'total_amount' => $calculo['total_orden'],
            'discount_a' => $calculo['discount_total']
        ));

        $carproducts = $this->fetchOpenCartLinesFromApi((int) $cart->cart_id);
        foreach ($carproducts as $productInfo) {
            $precio_obtenido = $productInfo->precio_grupo;
            $ordData = array(
                'order_id' => $insert,
                'product_id' => $productInfo->product_id,
                'qty' => $productInfo->qty,
                'sale_price' => $precio_obtenido,
                'comments' => ($productInfo->comment == null) ? '' : $productInfo->comment,
                'created' => $date,
                'groupcustomer' => $group->group_name,
                'tipolista' => $group->percentage_on_price,
                'perc_price' => $group->percent_price_amount,
                'salesp' => $productInfo->sale_price,
                'totalprice' => $precio_obtenido
            );
            $this->mymodel->add_details('order_details', $ordData);
        }
        return $insert;
    }

    /**
     * Líneas de carrito vía API (sustituye CarritoModel::obtener_product_car).
     *
     * @return array<int, object>
     */
    protected function fetchOpenCartLinesFromApi(int $cartId): array
    {
        $companyId = (int) ($this->company->company_id ?? 0);
        if ($companyId < 1 || $cartId < 1) {
            return [];
        }

        $detail = $this->getDataFromApi(
            "catalogcart/company/{$companyId}/cart/{$cartId}/open-detail",
            [],
            true
        );

        if (! is_array($detail) || isset($detail['error']) || empty($detail['details_car'])) {
            return [];
        }

        return array_map(static function ($row) {
            return is_object($row) ? $row : (object) $row;
        }, $detail['details_car']);
    }

    public function producto_conformato($productId, $search_sku = false)
    {
        $compId = $this->session->userdata('compId');
        $type_search = $search_sku ? "sku" : "pro_id";

        $query = "SELECT p.*,(SELECT formato from units as u where u.company_id='$compId' AND u.unit_id = p.unit_id) as formato,(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id=p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) as imagen_principal,
		(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) AS imagen_por_defecto FROM products as p WHERE p.company_id='$compId' AND p.$type_search='$productId'";

        $result = $this->db->query($query)->row();
        return $result;
    }

    public function buscarCustomer($customer_id = null)
    {
        $compId = $this->session->userdata('compId');

        if ($customer_id == null) {
            $group_default = $this->GroupModel->get_default();

            return $this->Helper->cast([
                                           "business_name" => "",
                                           "group_name" => $group_default->name,
                                           "percentage_on_price" => $group_default->percentage_on_price,
                                           "percent_price_amount" => $group_default->percent_price_amount
                                       ]);
        }

        $consulta = "SELECT c.business_name,g.name as group_name,
						g.percentage_on_price as percentage_on_price,
						g.percent_price_amount as percent_price_amount
						FROM customers AS c LEFT JOIN customer_groups AS g ON c.group_id = g.group_id
						WHERE c.company_id=$compId and c.customer_id=$customer_id";

        $result = $this->db->query($consulta)->row();

        if ($result != null && !$result->group_name) {
            $group_default = $this->GroupModel->get_default();
            return $this->Helper->cast([
                                           "business_name" => $result->business_name,
                                           "group_name" => $group_default->name,
                                           "percentage_on_price" => $group_default->percentage_on_price,
                                           "percent_price_amount" => $group_default->percent_price_amount
                                       ]);
        }
        //echo json_encode($result);
        return $result;
    }

    public function generar_token($order_id = null)
    {
        $ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        // Obtener la cadena de $ahora
        $ahoraStr = $ahora->format('Y-m-d H:i:s.u');
        // Aplicar hash SHA-256
        $hash = hash('sha256', $ahoraStr);
        // Recortar a 10 caracteres
        $hash10 = substr($hash, 0, 10);
        return $hash10;
    }

    public function validar_multiplo($numero, $multiplo)
    {
        $count = $numero;
        $msg = null;
        if (($count % $multiplo) == 0) {
            $msg = true;
        } else {
            $msg = false;
        }
        return $msg;
    }


    protected function getDetailsToAdd($allDetails)
    {
        return array_filter($allDetails, function ($detallenuevo) {
            return empty($detallenuevo['id']);
        });
    }

    protected function getDetailsToDelete($orderDetailActuales, $idsNewDetails)
    {
        return array_filter(
            $orderDetailActuales,
            function ($detailActual) use ($idsNewDetails) {
                return !in_array($detailActual->detail_id, $idsNewDetails);
            }
        );
    }

    protected function getDetailsToUpdate($orderDetailNuevos, $orderDetailActuales)
    {
        return array_filter(
            $orderDetailNuevos,
            function ($detailnuevo) use ($orderDetailActuales) {
                $actual = $orderDetailActuales[$detailnuevo['id']];
                return isset($actual) &&
                    (
                        $detailnuevo['qty'] != $actual->qty ||
                        $detailnuevo['sale_price'] != $actual->sale_price ||
                        $detailnuevo['comments'] != $actual->comments
                    );
            }
        );
    }

    public function parseOrderDetailsPriceFormat($orderDetails){

        foreach ($orderDetails as $k=>$orderDetail) {
            $orderDetails[$k]['sale_price'] = $this->Commonmodel->get_value_format($orderDetail['sale_price']);
        }
        return $orderDetails;
    }

    /**
     * @param object $order
     * @param $customer
     * @param array $orderDetailsReemplazo
     * @param bool $isBackup
     * @return void
     */
    public function crudOrderDetails(
        object $order,
        $customer,
        array $orderDetailsReemplazo,
        bool $isBackup = false
    ): void {

        $orderDetailsReemplazo = $this->parseOrderDetailsPriceFormat($orderDetailsReemplazo);
        $idsNewDetails = array_column($orderDetailsReemplazo, 'id');
        $orderDetailActuales = $order->details;
        $orderDetailActuales = array_combine(array_column($orderDetailActuales, 'detail_id'), $orderDetailActuales);
        $orderDetailsABorrar = $this->getDetailsToDelete($orderDetailActuales, $idsNewDetails);
        $orderDetailsAActualizar = $this->getDetailsToUpdate($orderDetailsReemplazo, $orderDetailActuales);
        $orderDetailsAAgregar = $this->getDetailsToAdd($orderDetailsReemplazo);

        foreach ($orderDetailsABorrar as $orderDetail) {
            $this->removeOrderDetail($orderDetail, $order, $isBackup);
        }

        foreach ($orderDetailsAActualizar as $orderDetailFromRequest) {
            $odCurrent = $orderDetailActuales[$orderDetailFromRequest['id']];
            $this->updateOrderDetail($odCurrent, $orderDetailFromRequest, $customer, $order, $isBackup);
        }

        foreach ($orderDetailsAAgregar as $orderDetail) {
            $this->addOrderDetail($orderDetail, $customer, $order, $isBackup);
        }

        // Overkill?
        $this->updateDetailsPrices($order);
    }

    public function makecrudOrderDetails(
        object $order,
               $customer,
        array $orderDetailsReemplazo,
        bool $isBackup = false
    ) {

        $orderDetailsReemplazo = $this->parseOrderDetailsPriceFormat($orderDetailsReemplazo);
        $idsNewDetails = array_column($orderDetailsReemplazo, 'id');
        $orderDetailActuales = $order->details;
        $orderDetailActuales = array_combine(array_column($orderDetailActuales, 'detail_id'), $orderDetailActuales);
        // $orderDetailsABorrar = $this->getDetailsToDelete($orderDetailActuales, $idsNewDetails);
        // $orderDetailsAActualizar = $this->getDetailsToUpdate($orderDetailsReemplazo, $orderDetailActuales);
        $orderDetailsAAgregar = $this->getDetailsToAdd($orderDetailsReemplazo);

        foreach ($orderDetailsAAgregar as $orderDetail) {
            $item = $this->makeAddOrderDetail($orderDetail, $customer, $order);
            array_push($order->details, $item);
        }
        return $order->details;
        // Overkill?
        // $this->updateDetailsPrices($order);
    }

    public function getCommercialAddress($orden) {
        $address = !empty($orden->commercial_zone) ? ', '.$orden->commercial_zone : "";
        $address .= !empty($orden->commercial_zip_code) ? ', '.$orden->commercial_zip_code : "";
        $address .= !empty($orden->commercial_city) ? ', '.$orden->commercial_city : "";
        $address .= !empty($orden->commercial_state) ? ', '.$orden->commercial_state : "";
        $address .= !empty($orden->commercial_country) ? ', '.$orden->commercial_country : "";

        return $orden->commercial_address . $address;
    }

    function getDispatchAddress($orden) {
        $addres_distpatch = "";

        // Verificar si se ha ingresado una nueva dirección de entrega
        if (!empty($orden->new_address_json)) {
            $new_address_objet = json_decode($orden->new_address_json);
            if ($new_address_objet->new_address == "true") {
                $addres_distpatch = $new_address_objet->delivery_address;
                $addres_distpatch .= !empty($new_address_objet->zone) ? ', '.$new_address_objet->zone : "";
                $addres_distpatch .= !empty($new_address_objet->zip_code) ? ', '.$new_address_objet->zip_code : "";
                $addres_distpatch .= !empty($new_address_objet->city) ? ', '.$new_address_objet->city : "";
                $addres_distpatch .= !empty($new_address_objet->state) ? ', '.$new_address_objet->state : "";
                $addres_distpatch .= !empty($new_address_objet->country) ? ', '.$new_address_objet->country : "";
            }
        }

        // Si no hay nueva dirección, usar la dirección de despacho
        if (empty($addres_distpatch) && !empty($orden->dispatch_address)) {
            $addres_distpatch = $orden->dispatch_address;
            $addres_distpatch .= !empty($orden->dispatch_zone) ? ', '.$orden->dispatch_zone : "";
            $addres_distpatch .= !empty($orden->dispatch_zip_code) ? ', '.$orden->dispatch_zip_code : "";
            $addres_distpatch .= !empty($orden->dispatch_city) ? ', '.$orden->dispatch_city : "";
            $addres_distpatch .= !empty($orden->dispatch_state) ? ', '.$orden->dispatch_state : "";
            $addres_distpatch .= !empty($orden->dispatch_country) ? ', '.$orden->dispatch_country : "";
        }

        // Si tampoco hay dirección de despacho, usar dirección comercial de entrega
        if (empty($addres_distpatch) && !empty($orden->commercial_delivery_address)) {
            $addres_distpatch = $orden->commercial_delivery_address;
            $addres_distpatch .= $this->getCommercialAddress($orden);
        }

        // Si no hay dirección comercial de entrega, usar dirección comercial
        if (empty($addres_distpatch)) {
            $addres_distpatch = $orden->commercial_address;
            $addres_distpatch .= $this->getCommercialAddress($orden);
        }
        return $addres_distpatch;
    }

    /**
     * @return void
     */
    public function moveStock($productId, $qty, $substract = true): void
    {
        $product = $this->mymodel->get_by("products", true, ["pro_id" => $productId]);
        $product->stock += ($substract ? -1:1 ) * $qty ;
        $this->mymodel->update(["stock" => $product->stock], 'products', ['pro_id' => $productId]);
    }


    public function obtenerDetailOrder($orderId, $companyId = null)
    {
        $compId = $companyId ? $companyId : $this->session->userdata('compId');
        $query = "SELECT p.*, o.*,o.sale_price as precio_cliente, o.comments as comment_producto,
		(SELECT formato from units where unit_id=p.unit_id and company_id='$compId') as formato_unidad,(o.qty * o.sale_price) as total_importe,
		(CASE WHEN o.delivered_quantity > 0 THEN o.delivered_quantity * o.sale_price ELSE 0 END) as total_importe_asignado,
		(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id
		WHERE pimg.product_id=p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) as imagen_principal,
		(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) AS imagen_por_defecto,
		(SELECT `name` from customer_groups
		where group_id= (SELECT group_id from customers
			where customer_id = (SELECT customer_id FROM orders
				WHERE order_id= $orderId))) as grupo_nombre,
		(SELECT percentage_on_price
		from customer_groups
		where group_id = (SELECT group_id from customers
			where customer_id = (SELECT customer_id FROM orders WHERE order_id= $orderId))) as percentage_on_price
		FROM order_details as o INNER JOIN products as p ON o.product_id=p.pro_id
		where o.order_id = $orderId ORDER BY o.detail_id ASC";

        $result = $this->db->query($query)->result();
        return $result;
    }

    public function revertirPrecio($customer,$precioLista)
    {
        return $this->recalcularPrecio($customer,$precioLista,true);
    }

    public function recalcularPrecio($customer, $precioLista, $revertirPrecio = false): float
    {
        $percentAmount = $customer->percent_price_amount ?? 0;
        $porcentaje = $percentAmount / 100;
        $coeficiente = 1;

        $percentageOnPrice = $customer->percentage_on_price ?? null;
        if ($percentageOnPrice === self::DECREASE_ON_PRICE) {
            $coeficiente = 1 - $porcentaje;
        } elseif ($percentageOnPrice === self::INCREASE_ON_PRICE) {
            $coeficiente = 1 + $porcentaje;
        }

        if($revertirPrecio)
            $coeficiente = 1/$coeficiente;

        return round($precioLista * $coeficiente ,2);
    }
}

