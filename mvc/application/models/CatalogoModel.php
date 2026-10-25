<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @property Mymodel $mymodel
 * @property Helper $Helper
 */
class CatalogoModel extends MY_Model
{
    public $company = array();
    const SCRIPT_COMPANY = ['enviroment_portal.js', 'get_language.js', 'shoplist.js', 'app.js'];
    const SCRIPT_SALESMAN = ['enviroment.js', 'get_language.js', 'shoplist.js', 'createneworder.js', 'app.js'];
    const SCRIPT_CUSTOMER = ['enviroment_catalogoweb.js', 'get_languagefromcart.js', 'shoplist.js', 'app.js'];

    public function __construct()
    {
        parent::__construct();
        $this->company = $this->Helper->get_company_info();
    }

    /**
     * @deprecated Usar company/Catalog::sendCatalogToCustomer (API). Se mantiene por compatibilidad legacy.
     */
    public function sendMailCatalog($company, $customer, $seller, $catalog_id, $expiration_days = 15, $catalog_text = null, $email_text = null)
    {
        $formatDate = isset($company->formatDate) ? $company->formatDate : null;
        if (!$formatDate) {
            $formatDate = $this->getObjectFromApi("formatdate/default");
        }

        $invitationPayload = [
            'catalog_id' => (int) $catalog_id,
            'customer_id' => (int) (is_scalar($customer->customer_id) ? $customer->customer_id : 0),
            'catalog_text' => $catalog_text !== null && is_scalar($catalog_text) ? (string) $catalog_text : '',
        ];
        if ($seller !== null && is_object($seller) && isset($seller->id)) {
            $sid = $seller->id;
            if (is_scalar($sid)) {
                $invitationPayload['seller_id'] = (int) $sid;
            }
        }

        $tokenInvitation = $this->post('order/invitation', $invitationPayload, false);
        if ($this->hasError($tokenInvitation)) {
            throw new \RuntimeException(is_object($tokenInvitation) ? ($tokenInvitation->message ?? 'Error creating invitation token') : ($tokenInvitation['message'] ?? 'Error creating invitation token'));
        }
        $tokenInvitation = is_array($tokenInvitation) ? (object) $tokenInvitation : $tokenInvitation;
        if (isset($tokenInvitation->data)) {
            $tokenInvitation = is_object($tokenInvitation->data) ? $tokenInvitation->data : (object) $tokenInvitation->data;
        }
        if (!$tokenInvitation || !isset($tokenInvitation->token)) {
            throw new \RuntimeException('Invalid response from invitation API');
        }

        $tokenString = isset($tokenInvitation->token) && is_string($tokenInvitation->token)
            ? $tokenInvitation->token
            : (isset($tokenInvitation->token) && is_scalar($tokenInvitation->token) ? (string) $tokenInvitation->token : '');
        if ($tokenString === '') {
            throw new \RuntimeException('Invalid response from invitation API');
        }
        $expirationDateRaw = $tokenInvitation->expirationDate ?? null;
        $expirationDateString = (is_string($expirationDateRaw)) ? $expirationDateRaw : (is_object($expirationDateRaw) && method_exists($expirationDateRaw, '__toString') ? $expirationDateRaw->__toString() : null);
        if ($expirationDateString === null && $expirationDateRaw !== null) {
            $expirationDateString = is_scalar($expirationDateRaw) ? (string) $expirationDateRaw : null;
        }

        $expiredDate = $expirationDateString !== null && $expirationDateString !== ''
            ? date('Y-m-d H:i:s', strtotime($expirationDateString))
            : date('Y-m-d H:i:s', strtotime('+' . ($expiration_days ?: 15) . ' days'));

        $catalogsend_time = isset($company->catalogsend_time) ? $company->catalogsend_time : 15;
        $fechaExpiredForView = $expirationDateString !== null && $expirationDateString !== '' ? $expirationDateString : $expiredDate;
        $lang = $this->HelperLanguage->catalog();
        if (is_object($lang)) {
            $lang = method_exists($lang, 'getArrayCopy') ? $lang->getArrayCopy() : (array) $lang;
        }
        $data_tosend = [
            'token' => $tokenString,
            'company' => $company,
            'customer_info' => $customer,
            'seller' => $seller,
            'lang' => is_array($lang) ? $lang : [],
            'expiration_days' => (int) $catalogsend_time,
            'catalog_text' => is_scalar($catalog_text) ? (string) $catalog_text : '',
            'email_text' => is_scalar($email_text) ? (string) $email_text : '',
            'catalogo_id' => (int) $catalog_id,
            'fecha_expired' => $fechaExpiredForView,
            'format_date' => $formatDate,
        ];

        $htmlContent = $this->load->view('company/catalogo/template_email', $data_tosend, true);

        $toEmail = isset($customer->email) && is_scalar($customer->email) ? trim((string) $customer->email) : '';
        if ($toEmail === '') {
            throw new \RuntimeException('Customer has no email address');
        }

        $langClient = $this->HelperLanguage->lang_customer();
        $subjectTpl = '%s Product Catalog';
        if (is_array($langClient) && !empty($langClient['subjet_email_send_catalog'])) {
            $subjectTpl = (string) $langClient['subjet_email_send_catalog'];
        } elseif (is_object($langClient) && isset($langClient->subjet_email_send_catalog)) {
            $subjectTpl = (string) $langClient->subjet_email_send_catalog;
        }
        $companyName = isset($company->company_name) && is_scalar($company->company_name)
            ? (string) $company->company_name
            : (isset($company->name) && is_scalar($company->name) ? (string) $company->name : 'Catalog');
        $subject = str_replace('%s', $companyName, $subjectTpl);

        $companyId = isset($company->company_id) ? (int) $company->company_id : 0;
        $this->load->helper('send_template');
        $catalogLink = rtrim((string) base_url(), '/') . '/catalogo/producto?token=' . rawurlencode($tokenString);
        $customerName = isset($customer->name) && is_scalar($customer->name) ? trim((string) $customer->name) : '';
        if ($customerName === '' && isset($customer->business_name) && is_scalar($customer->business_name)) {
            $customerName = trim((string) $customer->business_name);
        }
        $catalogName = is_scalar($catalog_text) ? trim((string) $catalog_text) : '';
        $renderedTpl = fv_send_template_render($companyId, 'catalog', 'email', [
            '{{customer_name}}' => $customerName,
            '{{company_name}}' => $companyName,
            '{{catalog_name}}' => $catalogName,
            '{{catalog_link}}' => $catalogLink,
            '{{order_number}}' => '',
            '{{order_total}}' => '',
            '{{order_status}}' => '',
            '{{message}}' => is_scalar($email_text) ? (string) $email_text : '',
        ]);
        if (is_array($renderedTpl) && trim((string) ($renderedTpl['body'] ?? '')) !== '') {
            $htmlContent = (string) $renderedTpl['body'];
            if (trim((string) ($renderedTpl['subject'] ?? '')) !== '') {
                $subject = (string) $renderedTpl['subject'];
            }
        }

        $ciEnv = getenv('CI_ENV') ?: (defined('ENVIRONMENT') ? (string) ENVIRONMENT : 'development');
        $mailHost = getenv('MAIL_HOST') ?: '';
        // Local/pruebas: envía la API (Laravel → Mailpit/SMTP del contenedor api). Producción: PHPMailer desde MVC.
        $sendViaMvcSmtp = ($ciEnv === 'production')
            || ($mailHost === 'smtp.sendgrid.net')
            || filter_var(getenv('CATALOG_SMTP_FROM_MVC') ?: '', FILTER_VALIDATE_BOOLEAN);

        $payload = [
            'html_body' => $htmlContent,
            'token' => $tokenString,
            'customer_id' => (int) (is_scalar($customer->customer_id) ? $customer->customer_id : 0),
            'catalog_id' => (int) $catalog_id,
        ];

        if ($sendViaMvcSmtp) {
            require_once APPPATH . 'helpers/fullvendor_email_helper.php';
            if (!FullvendorEmailHelper::sendMailFromNoReply($toEmail, $subject, $htmlContent)) {
                throw new \RuntimeException('Could not send catalog email (check SMTP / MAIL_* on the application server)');
            }
            $payload['skip_mailer_send'] = true;
        }

        $sendResponse = $this->post('catalog/send', $payload);
        if ($this->hasError($sendResponse)) {
            $err = is_object($sendResponse) ? ($sendResponse->message ?? 'Error sending catalog') : ($sendResponse['message'] ?? 'Error sending catalog');
            throw new \RuntimeException(is_string($err) ? $err : 'Error sending catalog');
        }
    }


    public function all_notdefaults(){

        $id = $this->company->company_id;
        $query = "SELECT * FROM catalog WHERE company_id=$id AND default_catalog is null
                  and deleted_at IS NULL and status = 1 order by catalog_name ASC";
        return $this->db->query($query)->result();

    }

    public function all(){
        $id = $this->company->company_id;
        $query = "SELECT *,NULL AS categories,NULL AS products FROM catalog
                  WHERE company_id=$id and deleted_at is null ORDER BY (default_catalog = 1) DESC, default_catalog DESC,catalog_id DESC";
        //$query = "SELECT *,NULL as categories FROM catalog WHERE company_id=$id AND default_catalog is null";
        $result = $this->db->query($query)->result();

        foreach($this->get_categories() as $cat){

            if(in_array($cat->catalog_id, array_column($result,'catalog_id'))){
                $index = array_search($cat->catalog_id, array_column($result,'catalog_id'));

                if (!$result[$index]->categories) $result[$index]->categories = array();
                array_push($result[$index]->categories, $cat);
            }
        }

        foreach($this->get_products() as $pro){
            if(in_array($pro->catalog_id, array_column($result,'catalog_id'))){
                $index = array_search($pro->catalog_id, array_column($result,'catalog_id'));

                if (!$result[$index]->products) $result[$index]->products = array();
                array_push($result[$index]->products, $pro);
            }
        }

        foreach($this->get_qty_orders() as $pro){
            if(in_array($pro->catalog_id, array_column($result,'catalog_id'))){
                $index = array_search($pro->catalog_id, array_column($result,'catalog_id'));

                if (!isset($result[$index]->sending)) $result[$index]->sending = null;
                $result[$index]->sending = $pro->qty_send;
            }
        }

        foreach($this->get_total_orders_value() as $pro){
            if(in_array($pro->catalog_id, array_column($result,'catalog_id'))){
                $index = array_search($pro->catalog_id, array_column($result,'catalog_id'));

                if (!isset($result[$index]->total)) $result[$index]->total = 0.00;
                $result[$index]->total = $pro->total;
            }
        }

        foreach($this->get_total_customers() as $pro){
            if(in_array($pro->catalog_id, array_column($result,'catalog_id'))){
                $index = array_search($pro->catalog_id, array_column($result,'catalog_id'));

                if (!isset($result[$index]->customer_count)) $result[$index]->customer_count = 0;
                $result[$index]->customer_count = $pro->customer_count;
            }
        }

        return $result;
    }

    public function default(){
        return $this->mymodel->get_by('catalog', true,
                                     ['company_id'=>$this->company->company_id,'default_catalog'=>1]);
    }

    public function get_actives(){

        $id = $this->company->company_id;
        $query = "SELECT * FROM catalog WHERE company_id=$id
        and deleted_at IS NULL and status = 1 order by catalog_name ASC";
        return $this->db->query($query)->result();
    }


    public function get_categoriasWithproducts(){

        $id_company = $this->company->company_id;
        $sql = "SELECT cat_id, category_name, category_default, qty_products
        FROM (
            SELECT
                categories.cat_id,
                categories.category_name,
                categories.category_default,
                (
                    SELECT COUNT(p.pro_id)
                    FROM products AS p
                    WHERE p.status = 1
                    AND p.name IS NOT NULL
                    AND p.sale_price > 0
                    AND FIND_IN_SET(categories.cat_id, p.category_id)
                ) AS qty_products
            FROM categories
            WHERE categories.company_id = $id_company
            AND categories.category_status = 1
            AND categories.category_default IS null
        ) AS subquery
        WHERE qty_products > 0
        ORDER BY category_name DESC                     ;";

       return $this->db->query($sql)->result();
    }

    public function get_one($id){
        return $this->mymodel->get_by('catalog', true,
                                     ['company_id'=>$this->company->company_id,'catalog_id'=>$id]);
    }

    public function get_categories(){




        $id = $this->company->company_id;
        $query = "SELECT o.catalog_id,cat.catalog_name,c.category_name,o.`order`
                  FROM order_categories AS o
                  INNER JOIN categories AS c ON c.cat_id = o.category_id
                  INNER JOIN catalog AS cat ON cat.catalog_id  = o.catalog_id
                  WHERE o.company_id = $id
                  ORDER BY o.order ASC";

        return $this->db->query($query)->result();
    }

    public function get_products(){
        $id = $this->company->company_id;
        $query = "SELECT p.sku,p.name,c.catalog_id
                  FROM products as p INNER JOIN
                  catalog as c ON CONCAT(',',p.pro_id,',')
                  REGEXP CONCAT(',(',REPLACE(c.product_name,',','|'),'),')
                  WHERE c.company_id = $id";

        return $this->db->query($query)->result();
    }

    public function isnotvalid_todelete($id){

        $lang_error = $this->HelperLanguage->errores_catalog();

        if ($this->get_one($id)->default_catalog == 1) return $lang_error["catalogo_default"];

        return null;
    }

    /**
     * Firma compatible con MY_Model::delete($table, $where).
     * Si $table === 'catalog' y $where tiene catalog_id, hace borrado lógico del catálogo.
     */
    public function delete($table, $where)
    {
        if ($table === 'catalog' && is_array($where) && isset($where['catalog_id'])) {
            $this->mymodel->update(
                ['deleted_at' => date("Y-m-d H:i:s")],
                'catalog',
                ['company_id' => $this->company->company_id, 'catalog_id' => $where['catalog_id']]
            );
            return true;
        }
        return parent::delete($table, $where);
    }

    public function enable($id){

		$this->mymodel->update(['status' => 1], 'catalog', array(
			'company_id' => $this->company->company_id,
			'catalog_id' => $id
		));
    }

    public function disable($id){

		$this->mymodel->update(['status' => 0], 'catalog', array(
			'company_id' => $this->company->company_id,
			'catalog_id' => $id
		));
    }

    public function get_qty_orders(){

        $id = $this->company->company_id;
        $fecha =date('Y-01-01');
        $query = "SELECT cat.catalog_id, cat.catalog_name, 0 AS qty_send
                  FROM catalog AS cat
                  WHERE cat.company_id = $id
                  AND cat.deleted_at IS NULL
                  GROUP BY cat.catalog_id";

        return $this->db->query($query)->result();
    }

    public function get_total_orders_value(){

        $fecha =date('Y-01-01');
        $id = $this->company->company_id;
        // Actualizado para usar la vista monto
        // Nota: La vista monto no tiene type_cart ni status_cart, 
        // por lo que se filtra solo por company_id y fecha
        $query = "SELECT company_id, catalog_id, SUM(total) AS total 
        FROM monto
        WHERE company_id = $id
        AND created > '$fecha'
        GROUP BY catalog_id";

        return $this->db->query($query)->result();
    }

    public function get_total_customers(){

        $id = $this->company->company_id;
        $fecha =date('Y-01-01');
        $query = "SELECT
        cat.catalog_id,
        0 AS customer_count,
        cat.catalog_name
        FROM catalog AS cat
        WHERE cat.company_id = $id
        AND cat.deleted_at IS NULL
        GROUP BY cat.catalog_id";
        return $this->db->query($query)->result();
    }
}
