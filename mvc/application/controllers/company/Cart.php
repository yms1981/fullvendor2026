<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart extends Company_Controller
{
    public ?int $company;
    public ?int $langId;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('langmodel');
        $this->load->model('Commonmodel');
        $this->company = (int) ($this->session->userdata('compId') ?? 0);
        $this->langId = (int) ($this->session->userdata('language_id') ?? 1);
    }

    public function abandoned($page = 1)
    {
        if ($this->company <= 0) {
            show_404();
        }

        $format_date = $this->Helper->format_date();
        $desde = $this->input->get('desde_fecha');
        $hasta = $this->input->get('hasta_fecha');
        if ($desde === null || $desde === '') {
            $desde = date('Y-m-01');
            $_GET['desde_fecha'] = $desde;
            $_GET['desde_fecha_texto'] = date($format_date->date_firstmonth);
        }
        if ($hasta === null || $hasta === '') {
            $hasta = date('Y-m-d');
            $_GET['hasta_fecha'] = $hasta;
            $_GET['hasta_fecha_texto'] = date($format_date->format_php);
        }
        if ($this->input->get('desde_fecha_texto') === null) {
            $_GET['desde_fecha_texto'] = date($format_date->format_php, strtotime($desde));
        }
        if ($this->input->get('hasta_fecha_texto') === null) {
            $_GET['hasta_fecha_texto'] = date($format_date->format_php, strtotime($hasta));
        }

        $estado = (string) ($this->input->get('estado') ?? 'all');
        $search = trim((string) ($this->input->get('search') ?? ''));
        $orderSort = (string) ($this->input->get('sort') ?? '');
        $orderType = strtoupper((string) ($this->input->get('order_type') ?? 'DESC'));
        $orderType = $orderType === 'ASC' ? 'ASC' : 'DESC';

        if ((int) $page < 1) {
            $page = 1;
        }
        if ($this->input->get('page')) {
            $page = (int) $this->input->get('page');
            if ($page < 1) {
                $page = 1;
            }
        }
        $perPage = 25;

        $langCol = $this->langId === 1 ? 'name_eng' : 'name_spa';
        $statusRaw = $this->getObjectFromApi('statuscart/getByLangColumn/' . $langCol);
        if (is_array($statusRaw) && isset($statusRaw['error'])) {
            $statusCart = [];
        } elseif (is_object($statusRaw) && isset($statusRaw->error)) {
            $statusCart = [];
        } elseif (is_array($statusRaw)) {
            $statusCart = array_map(static function ($row) {
                return is_object($row) ? $row : (object) $row;
            }, $statusRaw);
        } elseif (is_object($statusRaw) && isset($statusRaw->data) && is_array($statusRaw->data)) {
            $statusCart = $statusRaw->data;
        } else {
            $statusCart = [];
        }

        $apiParams = [
            'desde_fecha' => $desde,
            'hasta_fecha' => $hasta,
            'estado' => $estado,
            'search' => $search,
            'sort' => $orderSort,
            'order_type' => $orderType,
            'page' => $page,
            'per_page' => $perPage,
        ];
        $apiResponse = $this->getObjectFromApi("catalogcart/company/{$this->company}/abandoned", $apiParams);

        $cartHistory = [];
        $countTotal = 0;
        $countFiltered = 0;

        if (
            ! is_array($apiResponse)
            && is_object($apiResponse)
            && ! isset($apiResponse->error)
            && isset($apiResponse->data)
            && is_array($apiResponse->data)
        ) {
            $cartHistory = $apiResponse->data;
            $countTotal = (int) ($apiResponse->count_total ?? 0);
            $countFiltered = (int) ($apiResponse->count_filtered ?? $apiResponse->total ?? 0);
        }

        $_GET['order_type_general'] = $orderType;
        $_GET['order_type_sort'] = $orderSort;
        $_GET['order_type_id'] = ($orderSort === 'id' && $orderType === 'ASC') ? 'DESC' : 'ASC';
        $_GET['order_type_customer'] = ($orderSort === 'customer' && $orderType === 'ASC') ? 'DESC' : 'ASC';
        $_GET['order_type_monto'] = ($orderSort === 'monto' && $orderType === 'ASC') ? 'DESC' : 'ASC';
        $_GET['order_type_created'] = ($orderSort === 'created' && $orderType === 'ASC') ? 'DESC' : 'ASC';

        $config = $this->Commonmodel->obtener_configuracion_paginacion(company_url('cart/abandoned'), $countFiltered, $perPage);
        $this->pagination->initialize($config);

        $this->addJsFile(base_url('companyasset/js/sweetalert2.js'));
        $this->addJsFile(base_url('companyasset/company/cart/index.js'));
        $this->addJsFile(base_url('companyasset/company/cart/delete_cart.js'));

        $data = [
            'title' => 'Open Cart',
            'page' => 'order',
            'subpage' => 'cartlist',
            'lang_order' => $this->HelperLanguage->lang_order(),
            'langprods' => $this->HelperLanguage->lang_products(),
            'lang_cart' => $this->HelperLanguage->lang_cart(),
            'lang_status' => $this->HelperLanguage->status_active(),
            'lang_meses' => $this->HelperLanguage->meses(),
            'status_cart' => $statusCart,
            'format_date' => $format_date,
            'format_numeric' => $this->Helper->format_numeric(),
            'moneda' => (static function ($currency) {
                return is_object($currency) ? (string) ($currency->symbol ?? '$') : '$';
            })($this->Helper->currency()),
            'cart_history' => $cartHistory,
            'count_total' => $countTotal,
            'count_filtered' => $countFiltered,
            'paginate' => $this->pagination->create_links(),
        ];

        $this->showContent('company/cart/cart_list', $data, 'company');
    }

    public function opencart($cartId)
    {
        if ($this->company <= 0) {
            show_404();
        }

        $cartId = (int) $cartId;
        if ($cartId < 1) {
            show_404();
        }

        $detail = $this->fetchOpenCartDetail($cartId);
        if ($detail === null) {
            show_404();
        }

        $cartInfo = (object) ($detail['cart_info'] ?? []);
        $customerId = (int) ($cartInfo->customer_id ?? 0);
        $productosPedidos = [];
        if ($customerId > 0) {
            $orderedRaw = $this->getDataFromApi(
                "order/company/{$this->company}/customer/{$customerId}/previouslyOrderedProducts",
                [],
                false
            );
            if (is_array($orderedRaw)) {
                $productosPedidos = array_map(static function ($row) {
                    return is_object($row) ? $row : (object) $row;
                }, $orderedRaw);
            }
        }

        $this->addJsFile(base_url('companyasset/js/sweetalert2.js'));
        $this->addJsFile(base_url('companyasset/company/cart/index.js'));
        $this->addJsFile(base_url('companyasset/company/cart/notify_customer.js'));
        $this->addJsFile(base_url('companyasset/company/cart/showalert_tonotify.js'));

        $data = [
            'title' => 'Open Cart',
            'page' => 'order',
            'subpage' => 'cartlist',
            'lang_order' => $this->HelperLanguage->lang_order(),
            'lang_catalogo' => $this->HelperLanguage->catalog(),
            'lang_cart' => $this->HelperLanguage->lang_cart(),
            'format_date' => $this->Helper->format_date(),
            'format_numeric' => $this->Helper->format_numeric(),
            'cart_info' => $cartInfo,
            'details_car' => array_map(static function ($row) {
                return is_object($row) ? $row : (object) $row;
            }, $detail['details_car'] ?? []),
            'details_total' => is_array($detail['details_total'] ?? null)
                ? $detail['details_total']
                : (array) ($detail['details_total'] ?? []),
            'productos_pedidos' => $productosPedidos,
            'link' => (string) ($detail['link'] ?? ''),
            'cart_expired' => ! empty($detail['cart_expired']),
        ];

        $this->addJsFile(base_url('companyasset/company/cart/delete_cart.js'));

        $this->showContent('company/cart/open_cartdetails', $data, 'company');
    }

    public function convert_carttoorder($cartId)
    {
        if ($this->company <= 0) {
            show_404();
        }

        $cartId = (int) $cartId;
        if ($cartId < 1) {
            show_404();
        }

        $langCart = $this->HelperLanguage->lang_cart();
        $wantsJson = $this->input->is_ajax_request()
            || strpos((string) $this->input->get_request_header('Accept', true), 'application/json') !== false;

        $response = $this->post("catalogcart/company/{$this->company}/cart/{$cartId}/submit", [], false);

        $resolveMsg = static function ($response, array $langCart): string {
            $key = '';
            if (is_object($response)) {
                $key = (string) ($response->msg ?? $response->error ?? '');
            } elseif (is_array($response)) {
                $key = (string) ($response['msg'] ?? $response['error'] ?? '');
            }
            if ($key !== '' && isset($langCart[$key]) && is_string($langCart[$key])) {
                return $langCart[$key];
            }
            if ($key !== '' && ! isset($langCart[$key])) {
                // Clave desconocida: preferir mensaje genérico traducido.
                return (string) ($langCart['submit_failed'] ?? $key);
            }

            return (string) ($langCart['submit_failed'] ?? '');
        };

        if ($this->hasError($response) || ! is_object($response) || empty($response->success)) {
            $msg = $resolveMsg($response, $langCart);
            if ($wantsJson) {
                return $this->Helper->response_json([
                    'success' => false,
                    'msg' => is_object($response) ? (string) ($response->msg ?? $response->error ?? 'submit_failed') : 'submit_failed',
                    'message' => $msg,
                ]);
            }
            $this->session->set_flashdata('msg', $msg);
            redirect(company_url('cart/opencart/' . $cartId));
            return;
        }

        $orderLabel = (string) ($response->order_number ?? $response->order_id ?? '');
        $okMsg = (string) ($langCart['orden_created'] ?? '');
        if ($orderLabel !== '' && $okMsg !== '') {
            $okMsg = trim($okMsg . ' ' . $orderLabel);
        } elseif ($orderLabel !== '') {
            $okMsg = $orderLabel;
        }

        if ($wantsJson) {
            return $this->Helper->response_json([
                'success' => true,
                'msg' => 'orden_created',
                'message' => $okMsg,
                'order_id' => (int) ($response->order_id ?? 0),
                'order_number' => $orderLabel,
                'redirect' => company_url('cart/abandoned'),
            ]);
        }

        if ($orderLabel !== '') {
            $this->session->set_flashdata('orden_created', $orderLabel);
        }
        if ($okMsg !== '') {
            $this->session->set_flashdata('msg', $okMsg);
        }

        redirect(company_url('cart/abandoned'));
    }

    public function delete_cart($cartId)
    {
        if ($this->company <= 0) {
            return $this->Helper->response_json([
                'success' => false,
                'msg' => 'delete_error',
                'message' => $this->HelperLanguage->lang_cart()['delete_error'] ?? '',
            ]);
        }

        $cartId = (int) $cartId;
        $langCart = $this->HelperLanguage->lang_cart();
        $response = $this->getObjectFromApi("catalogcart/company/{$this->company}/cart/{$cartId}/soft-delete");

        $msgKey = '';
        if (is_object($response)) {
            $msgKey = (string) ($response->msg ?? $response->error ?? '');
        }

        $failed = $this->hasError($response)
            || ! is_object($response)
            || empty($response->success);

        if ($failed) {
            if ($msgKey === '' || ! isset($langCart[$msgKey])) {
                $msgKey = 'delete_error';
            }

            return $this->Helper->response_json([
                'success' => false,
                'msg' => $msgKey,
                'message' => (string) ($langCart[$msgKey] ?? $langCart['delete_error'] ?? ''),
            ]);
        }

        $okKey = $msgKey !== '' ? $msgKey : 'cart_deleted';

        return $this->Helper->response_json([
            'success' => true,
            'msg' => $okKey,
            'message' => (string) ($langCart[$okKey] ?? $langCart['cart_deleted'] ?? ''),
        ]);
    }

    public function notify($cartId)
    {
        if ($this->company <= 0) {
            return $this->Helper->response_json([
                'success' => false,
                'msg' => $this->HelperLanguage->lang_cart()['notify_error'] ?? '',
            ]);
        }

        $cartId = (int) $cartId;
        $langCart = $this->HelperLanguage->lang_cart();
        $email = trim((string) $this->input->post('email'));
        $confirm = $this->input->post('confirm');

        $detail = $this->fetchOpenCartDetail($cartId);
        if ($detail === null) {
            return $this->Helper->response_json([
                'success' => false,
                'msg' => $langCart['notify_not_found'] ?? '',
            ]);
        }

        if (! empty($detail['cart_expired']) && ($confirm === null || $confirm === '' || $confirm === 'FALSE')) {
            return $this->Helper->response_json([
                'success' => false,
                'question_confirm' => true,
                'msg' => $langCart['link_expired'] ?? '',
            ]);
        }

        if (! empty($detail['cart_expired'])) {
            $this->post("catalogcart/company/{$this->company}/cart/{$cartId}/renew", ['days' => 15], false);
            $detail = $this->fetchOpenCartDetail($cartId) ?? $detail;
        }

        if ($email === '') {
            $cartInfo = (object) ($detail['cart_info'] ?? []);
            $email = trim((string) ($cartInfo->email_customer ?? ''));
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->Helper->response_json([
                'success' => false,
                'msg' => $langCart['notify_email_required'] ?? '',
            ]);
        }

        $company = $this->getObjectFromApi('company/' . $this->company);
        $cartInfo = (object) ($detail['cart_info'] ?? []);
        $customer = $this->getObjectFromApi('customer/' . (int) ($cartInfo->customer_id ?? 0));
        $link = (string) ($detail['link'] ?? '');
        $token = (string) ($cartInfo->token_kor ?? '');
        $currency = $this->Helper->currency();
        $moneda = is_object($currency) ? (string) ($currency->symbol ?? '$') : '$';
        $detailsTotal = $detail['details_total'] ?? [];
        if (is_object($detailsTotal)) {
            $detailsTotal = (array) $detailsTotal;
        }
        if (! is_array($detailsTotal)) {
            $detailsTotal = [];
        }

        try {
            $htmlContent = $this->load->view('company/cart/template_submitorder', [
                'lang_cart' => $langCart,
                'lang_order' => $this->HelperLanguage->lang_order(),
                'cart_info' => $cartInfo,
                'details_car' => array_map(static function ($row) {
                    return is_object($row) ? $row : (object) $row;
                }, $detail['details_car'] ?? []),
                'details_total' => $detailsTotal,
                'link' => $link,
                'token' => $token,
                'company' => is_object($company) ? $company : (object) [],
                'customer_info' => is_object($customer) ? $customer : (object) [],
                'format_numeric' => $this->Helper->format_numeric(),
                'moneda' => $moneda,
                'v_imagen' => date('YmdHis'),
            ], true);
        } catch (\Throwable $e) {
            log_message('error', 'Cart::notify template: ' . $e->getMessage());

            return $this->Helper->response_json([
                'success' => false,
                'msg' => $langCart['notify_error'] ?? '',
            ]);
        }

        $companyName = is_object($company)
            ? (string) ($company->company_name ?? $company->name ?? 'FullVendor')
            : 'FullVendor';
        $subject = sprintf($langCart['cart_iswaiting'] ?? '%s', $companyName);

        require_once APPPATH . 'helpers/fullvendor_email_helper.php';
        if (! FullvendorEmailHelper::sendMailFromNoReply($email, $subject, $htmlContent)) {
            return $this->Helper->response_json([
                'success' => false,
                'msg' => $langCart['notify_error'] ?? '',
            ]);
        }

        return $this->Helper->response_json([
            'success' => true,
            'msg' => $langCart['notify_sent'] ?? '',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchOpenCartDetail(int $cartId): ?array
    {
        $response = $this->getDataFromApi(
            "catalogcart/company/{$this->company}/cart/{$cartId}/open-detail",
            [],
            true
        );

        if (! is_array($response) || isset($response['error'])) {
            return null;
        }

        return $response;
    }
}
