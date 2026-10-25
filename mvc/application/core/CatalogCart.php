<?php
trait CatalogCart
{
    protected $cartJs  = [
        'check_newaddress.js',
        'placeorder.js',
        'cleancart.js',
        'validate.js',
        'locationSearch.js'
    ];
    public function initCart($customer_id, $company_id): void
    {
        if ($customer_id && $company_id) {
            $cart = $this->getObjectFromApi("catalogcart/company/{$company_id}/customer/{$customer_id}");
            if (!$this->hasError($cart)) {
                $this->setUserData('cart', $cart);
            } else {
                $this->createNewCart();
            }
        }
    }

    public function getCart()
    {
        if (!$this->getUserData('cart')) {
            $this->initCart($this->getCustomerId(), $this->getCompanyId());
        }
        return $this->getUserData('cart');
    }

    private function addProductToCart($endpoint, $data)
    {
        $cart = $this->getCart();
        if (!$cart) {
            echo json_encode(['success' => false, 'message' => 'The requested address does not exist']);  //lang, lo resuelve el front o lo devolvemos desde el back?
            return;
        }

        try {
            $data['order_id'] = $cart->order_id;

            $response = $this->post($endpoint, $data);

            if ($this->hasError($response)) {
                echo json_encode(['success' => false, 'message' => $response['message']]);  //lang, lo resuelve el front o lo devolvemos desde el back?
                return;
            }

            $msg_success = 'Producto agregado!';

            $detalle_total = $response;
            //isolate the value to avoid errors in the old front
            $updatedProductQty = $detalle_total['product_qty'];
            unset($detalle_total['product_qty']);

            echo json_encode([
                'success' => true,
                'message' => $msg_success,
                'detalle' => $detalle_total,
                'cantidad_actual' => $updatedProductQty
            ]);
        } catch (Exception $e) {
            $msg = $e['message'] ?? 'Ocurrió un error inesperado';
            $errors = $e['errors'] ?? [];

            echo json_encode([
                'success' => false,
                'message' => $msg,
                'errors' => $errors
            ]);
        }
    }

    public function addProduct()
    {
        $this->addProductToCart("catalogcart/addProduct", [
            "product_id" => $this->input->post('product_id'),
            "quantity" => $this->input->post('qty'),
            "comments" => $this->input->post('comment')
        ]);
    }

    public function quickAddProduct($product_id)
    {
        $this->addProductToCart("catalogcart/quickAddProduct", [
            "product_id" => $product_id
        ]);
    }

    public function create_neworder()
    {
        //aca deberia crearse la orden en estado shopping con minimo el $catalog_id, $company_id y $customer_id
        $request = $this->input->post();
        $order = $this->getObjectFromApi("order/{$request['token']}");

        $this->post("order/{$order->order_id}", [
            "order_status" => OrdenModel::STATUS_PLACED
        ]);
        return $this->Helper->response_json(['success' => true]);
    }

    public function deleteProduct($productId)
    {
        $orderId = $this->getCart()->order_id;

        $msgDeleted = 'Product Deleted!';
        $msgError = 'Error removing product!';
        if (method_exists($this, 'getModuleLang')) {
            $lang = $this->getModuleLang('cmp-orders');
            if (!empty($lang['producto_eliminado'])) {
                $msgDeleted = $lang['producto_eliminado'];
            }
            if (!empty($lang['error_eliminar_producto'])) {
                $msgError = $lang['error_eliminar_producto'];
            }
        }

        if ($this->apiDelete("catalogcart/{$orderId}/delete-product/{$productId}")) {
            $detail = $this->getObjectFromApi("order/{$orderId}/total-details?formated");
            echo json_encode(['success' => true, 'msg' => $msgDeleted, 'detalle' => $detail]);
            return;
        }
        echo json_encode(['success' => false, 'msg' => $msgError, 'detalle' => []]);
    }

    public function showOrder()
    {
        $this->setApplication('marketplace');

        // Get cart data
        $cart = $this->getCart();

        // Prepare data for the view
        $pageData = [];
        $pageData['info_customer'] = $this->CustomerModel->getCustomer($this->getCustomerId(), true);
        $pageData['cart_info'] = $this->cartInfo();

        $pageData['js'] = $this->cartJs;
        $pageData['breadcrumb'] = ['Lista Catalogo']; //localize

        // Load the view
        $this->showContent('marketplace/placeorder', $pageData);
    }

    public function placeOrder()
    {
        $cart = $this->getCart();

        if (!$cart) {
            return $this->Helper->response_json(['success' => false, 'message'=> 'No se encuentra el carrito']);
        }

        $request = json_decode(file_get_contents('php://input'), true);
        $request['source'] = 'Catalog';
        $request['user_placed_order'] = $this->getUserId();
        $request['user_type'] = $this->getRole();

        $response = $this->put("catalogcart/{$cart->order_id}/place-order", $request);

        if (!$this->hasError($response)) {
            $this->flash('success', 'success');
            $this->flashMessage('Orden creada!');

            $this->removeUserData('cart');
        }

        return $this->Helper->response_json($response);

        //redirecciones
//        switch ($cart->type_cart) {
//                case 'to_company':
//                    $msg = '["' . $lang_order['order_saved'] . '!", "success", "#A5DC86"]';
//                    $this->session->set_flashdata('msg', $msg);
//                    $fecha_actual = date("Y-m-d H:i:s");
//                    $fecha_expired = date("Y-m-d H:i:s", strtotime($fecha_actual . "- 1 days"));
//                    $this->put("catalogcart/{$cart->cart_id}", ['expired_date' => $fecha_expired]);
//                    return $this->Helper->response_json(['success' => true, 'back' => true, 'url' => company_url('orders')]);
//                    break;
//
//                case 'to_portal_seller':
//                    $this->session->set_flashdata('success', 'success');
//                    $this->session->set_flashdata('msg', $lang_order['order_enviada'], 'success');
//                    $this->delete("catalogcart/{$cart->cart_id}/details");
//                    $emailOrderSubject = $this->EmailOrderModel->getEmailOrderSubject($order);
//                    $emailOrderHtmlBody = $this->EmailOrderModel->getEmailOrderHtmlBody($order, OrdenModel::STATUS_PLACED);
//                    $seller = $this->getObjectFromApi("vendor/{$order->user_id}");
//
//                    if ($info_company->email_client_on_salesman_order) {
//                        FullvendorEmailHelper::sendMail($this->EmailOrderModel->getEmailOrderTo($order), $emailOrderSubject, $emailOrderHtmlBody);
//                    }
//
//                    if ($info_company->email_on_salesman_order){
//                        if ($seller->email_on_salesman_order)
//                            FullvendorEmailHelper::sendMail($seller->email, $emailOrderSubject, $emailOrderHtmlBody);
//                    }
//
//                    return $this->Helper->response_json(['success' => true, 'back' => true, 'url' => base_url('catalogo/producto?token=' . $cart->token_kor)]);
//                    break;
//
//                case 'to_customer':
//
//                    $this->put("catalogcart/{$cart->cart_id}", ['order_sent' => 2]);
//                    $this->session->set_flashdata('success', 'success');
//                    $this->session->set_flashdata('msg', $lang_order['order_enviada']);
//                    $emailOrderSubject = $this->EmailOrderModel->getEmailOrderSubject($order);
//                    $emailOrderHtmlBody = $this->EmailOrderModel->getEmailOrderHtmlBody($order, OrdenModel::STATUS_APPROVED);
//                    FullvendorEmailHelper::sendMail($this->EmailOrderModel->getEmailOrderTo($order), $emailOrderSubject, $emailOrderHtmlBody);
//                    return $this->Helper->response_json(['success' => true, 'msg' => $lang_order['order_enviada']]);
//                    break;
//
//                default:
//                    $msg = '["' . $lang_order['order_enviada'] . '!", "success", "#A5DC86"]';
//                    $this->put("catalogcart/{$cart->cart_id}", ['order_sent' => 2]);
//                    $this->session->set_flashdata('success', 'success');
//                    $this->session->set_flashdata('msg', $msg);
//                    return $this->Helper->response_json(['success' => true, 'msg' => $lang_order['order_enviada']]);
//                    break;
//            }
    }

    public function emptyCart()
    {
        $cart = $this->getCart();

        if (isset($cart)) {
            $response = $this->apiDelete("catalogcart/{$cart->order_id}/details");

            if (!$this->hasError($response)) {
                $this->flash('success', true);
                $this->flashMessage( 'Emptied cart!');

                return $this->Helper->response_json(['success' => true]);
            }
        }

        return $this->Helper->response_json([
            'success' => false,
            'message' => $response['message'] ?? 'Error to get the cart'
        ]);
    }

    protected function getCartDetailes($cartId) {
        return $this->getDataFromApi("order/{$cartId}/details");
    }

    public function getProduct($productId)
    {
        return $this->getObjectFromApi("product/{$productId}");
    }

    public function cartInfo()
    {
        return $this->getObjectFromApi("order/{$this->getCart()->order_id}", ['relations' => ['order_details.product.images']]);
    }

    private function createNewCart()
    {
        $newCart = [
            "customer_id" => $this->getCustomerId(),
            "order" => [
                "company_id" => $this->getCompanyId(),
                "catalog_id" => $this->getCatalogId()
            ]
        ];

        if ($this->isSellerSetted()){
            $newCart['seller_id'] = $this->getSellerId();
        }

        $response = $this->post("catalogcart", $newCart);

        if (!$this->hasError($response)) {
            $this->setUserData('cart', $response);
        }
    }

    public function search() //location search (used for new address)
    {
        $resource = $this->input->get('resource');
        $term = $this->input->get('term');
        $locationParams = ['all' => true,'orderProperty' => 'name','props' => ['id', 'name'],'name' => $term];

        $response = $this->getObjectFromApi($resource,$locationParams);

        echo !$this->hasError($response) ? json_encode($response) : [];
    }
}
