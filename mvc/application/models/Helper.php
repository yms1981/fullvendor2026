<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use Carbon\Carbon;
class Helper extends CI_Model {

    public ?int $company_id;
    public ?int $subuser_id;
    public ?int $langId;
    public $company_info = array();
    public $sub_user = array();

    public $red = '#CF1C00';
    public $amarillo = '#ffc107';
    public $verde = '#29b768';
    public $color_semaforoDefault = '#CF1C00';
    function __construct()
	{
		parent::__construct();

        $compId = null;
        $subuserId = null;
        $langId = null;
        if (isset($this->session) && is_object($this->session) && method_exists($this->session, 'userdata')) {
            $compId = $this->session->userdata('compId');
            $subuserId = $this->session->userdata('user_logueado');
            $langId = $this->session->userdata('language_id');
        }
        $this->company_id = $this->normalizeSessionCompanyId($compId);
        $this->subuser_id = $subuserId !== null && $subuserId !== '' ? (int) $subuserId : null;
        $this->langId = $langId !== null && $langId !== '' ? (int) $langId : null;
    }

    /**
     * Relee compId de sesión (reabriéndola si hace falta). El constructor puede
     * ejecutarse antes de que el controlador haya reabierto la sesión PHP.
     */
    public function resolveCompanyId(): int
    {
        if ($this->company_id !== null && $this->company_id > 0) {
            return (int) $this->company_id;
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $compId = null;
        if (isset($this->session) && is_object($this->session) && method_exists($this->session, 'userdata')) {
            $compId = $this->session->userdata('compId');
        }
        $normalized = $this->normalizeSessionCompanyId($compId);
        if ($normalized !== null && $normalized > 0) {
            $this->company_id = $normalized;

            return $normalized;
        }

        return 0;
    }

    /**
     * @param mixed $compId
     */
    protected function normalizeSessionCompanyId($compId): ?int
    {
        if ($compId === null || $compId === '' || $compId === false) {
            return null;
        }
        if (is_array($compId)) {
            $compId = $compId['company_id'] ?? (reset($compId)['company_id'] ?? null);
        } elseif (is_object($compId)) {
            $compId = $compId->company_id ?? null;
        }
        $id = (int) $compId;

        return $id > 0 ? $id : null;
    }

    protected function ensureCompanyInfoLoaded(): void
    {
        if (is_object($this->company_info) && (int) ($this->company_info->company_id ?? 0) > 0) {
            return;
        }
        if (empty($this->company_id)) {
            $this->resolveCompanyId();
        }
        if (empty($this->company_id)) {
            return;
        }
        $CI = &get_instance();
        if (! is_object($CI) || ! method_exists($CI, 'getObjectFromApi')) {
            $this->company_info = (object) ['company_id' => (int) $this->company_id];

            return;
        }
        $company = $CI->getObjectFromApi("company/{$this->company_id}");
        if (is_object($company) && ! isset($company->error) && (int) ($company->company_id ?? 0) > 0) {
            $this->company_info = $company;

            return;
        }
        // Fallback: muchas pantallas asumen objeto con company_id (p. ej. DashboardModel).
        $this->company_info = (object) ['company_id' => (int) $this->company_id];
    }

    protected function ensureSubUserLoaded(): void
    {
        if (! empty($this->sub_user) || empty($this->subuser_id)) {
            return;
        }
        $CI = &get_instance();
        if (! is_object($CI) || ! method_exists($CI, 'getObjectFromApi')) {
            return;
        }
        $subUser = $CI->getObjectFromApi("company/{$this->subuser_id}");
        if (is_object($subUser) && ! isset($subUser->error)) {
            $this->sub_user = $subUser;
        }
    }

    public function update_catalogFullvendor(){
        $this->creat_categorydefault();
		$this->creat_fullvendorcatalog();
    }

    public function get_company_info(){
        $this->ensureCompanyInfoLoaded();
        return $this->company_info;
    }

    public function get_subuser() {
        $this->ensureSubUserLoaded();
        return $this->sub_user;
    }

    public function get_language_head(){
        return $this->langmodel->getMdl('comp-login-head', $this->langId);
    }

    public function get_lang_enabled(){
        return $this->langmodel->getMdl('cmp-enabled', $this->langId);
    }

    public function creat_categorydefault($company_id=null){

        $compId = $company_id !== null ? (int) $company_id : (isset($this->company_id) ? (int) $this->company_id : null);
        if ($compId === null || $compId <= 0) {
            log_message('debug', 'creat_categorydefault: company_id no disponible, se omite creación de categoría por defecto.');
            return;
        }
        $this->company_id = $compId;

        $categoria_default =  $this->mymodel->get_by('categories', true, array(
            'company_id' => $compId,
            'category_default'=>1));

        // si no existe la categoria la creamos

        if (!$categoria_default) {
            $mydata = array(
                'company_id' => $compId,
                'category_name'=> 'Without Category',
                'category_status' => 1,
                'category_created_at' =>date("Y-m-d H:i:s"),
                'category_default'=>1
            );
            $this->mymodel->insert("categories", $mydata); // la guardamos
            $this->creat_fullvendorcatalog($compId); // creamos el catalogo Fullvendor

            $categ_default_created =  $this->mymodel->get_by('categories', true, array('company_id' => $compId,'category_default'=>1));
            if ($categ_default_created) {
                $this->asigncategory_toproduct($categ_default_created);
            }
            return;
        }

        // Asignar a los productos que no tienen categorías la categoría por defecto
        $this->asigncategory_toproduct($categoria_default);
    }

    /**
     * Asigna la categoría por defecto (Without Category) a todos los productos sin categoría de la empresa:
     * - Actualiza products.category_id cuando está NULL, vacío o 0.
     * - Inserta en category_product para todos los productos que no tienen ninguna categoría asignada.
     */
    public function asigncategory_toproduct($categ_default_created){

        $cat_id = (int) $categ_default_created->cat_id;
        $company_id = (int) $this->company_id;

        // 1) products.category_id: todos los productos sin categoría (NULL, vacío o 0)
        $this->db->query("UPDATE products SET category_id = ?
                  WHERE company_id = ? AND (category_id IS NULL OR TRIM(COALESCE(category_id,'')) = '' OR category_id = 0)", [$cat_id, $company_id]);

        // 2) category_product: todos los productos de la empresa que no tienen ninguna fila en category_product
        if ($this->db->table_exists('category_product')) {
            $this->db->query("
                INSERT INTO category_product (product_id, category_id)
                SELECT p.pro_id, ?
                FROM products p
                WHERE p.company_id = ?
                AND p.pro_id NOT IN (
                    SELECT cp.product_id FROM category_product cp
                    INNER JOIN categories c ON c.cat_id = cp.category_id
                    WHERE c.company_id = ?
                )
            ", [$cat_id, $company_id, $company_id]);
        }
    }

    public function removecategory_default_onproduct($prodid){

        $product = $this->mymodel->get_by('products', true, array('pro_id' =>$prodid));
        $categoria_default =  $this->mymodel->get_by('categories', true, array('company_id' =>$this->company_id,'category_default'=>1));

        $categories_found = explode(",", $product->category_id);


        if (in_array($categoria_default->cat_id,$categories_found)) {

            $clave = array_search($categoria_default->cat_id, $categories_found);
            unset($categories_found[$clave]);

            $categories_found = implode(',',$categories_found);

            if(!empty($categories_found)){

                $categories_found = ($categories_found[0] ==',')?substr($categories_found,1):$categories_found;
                $this->mymodel->update(
                    ["category_id" =>$categories_found],
                    "products",
                    ["company_id"=>$this->company_id,"pro_id" =>$product->pro_id]);

            }else{

                $this->mymodel->update(
                    ["category_id" =>null],
                    "products",
                    ["company_id"=>$this->company_id,"pro_id" =>$product->pro_id]);
            }
        }
    }

    public function get_count_categories_catalog($company_id, $catalog_id) {

        return $this->mymodel->get_by('order_categories',false,['company_id' =>$company_id,'catalog_id'=>$catalog_id]);
    }

    public function get_categories($company_id){
        return $this->mymodel->get_by('categories', false, array('company_id' =>$this->company_id,'category_status'=>1,'category_default='=>null));
    }

    public function updateorder_catalogo($catalogo_fullvendor=null,$categoria_default=null){


        $catalogo_fullvendor =  !$catalogo_fullvendor
                            ?$this->mymodel->get_by('catalog', true, array('company_id' =>$this->company_id,'default_catalog'=>1))
                            :$catalogo_fullvendor; // Obtenemos el catalogo por defecto

        $categoria_default  = !$categoria_default
                            ?$this->mymodel->get_by('categories', true, array('company_id' =>$this->company_id,'category_default'=>1))
                            :$categoria_default; // Obtenemos la categoria por defecto

        // order categories del catalogo por defecto
        $order_categories_count = $this->mymodel->count('order_categories',['company_id' =>$this->company_id,'catalog_id'=>$catalogo_fullvendor->catalog_id]);


        if ($order_categories_count <= 0){
            // si no hay categorias en el orden del catalogo
            // Agregar categorias en la tabla order_categories y la de sin categoria (categorias por defecto) al final
            // $categorias = $this->mymodel->get_by('categories', false, array('company_id' =>$this->company_id,'category_status'=>1,'category_default='=>null));

            $categorias = $this->get_categories($this->company_id);
            $order = 1;
            // echo json_encode($categorias);
            foreach ($categorias as $cat) {

                $this->mymodel->insert("order_categories", [
                    "category_id" => $cat->cat_id,
                    "catalog_id"=>$catalogo_fullvendor->catalog_id,
                    "company_id"=>$this->company_id,
                    "order"=>$order
                ]);
                $order = $order + 1;
            }

            $this->mymodel->insert("order_categories", [
                "category_id" => $categoria_default->cat_id,
                "catalog_id"=>$catalogo_fullvendor->catalog_id,
                "company_id"=>$this->company_id,
                "order"=>$order
            ]);

        }else{

            // El catalogo ya tiene categorias incluidas;

            $order_category_catalog = $order_categories_count;

            // validar si la categorias de la empresa esta dentro del orden del catalogo
            $categorias_company = $this->get_categories($this->company_id);

            // obtenemos el orden de la categorias
            $order_categories_rows = $this->get_count_categories_catalog(
                $this->company_id,
                $catalogo_fullvendor->catalog_id
            );


            // obtenemos las categorias no registradas;

            $categorias_company_ids = array_column($categorias_company, 'cat_id');
            $order_categories_ids = array_column($order_categories_rows, 'category_id');

            $categorias_no_registradas = array_values(array_diff($categorias_company_ids, $order_categories_ids));


            if (count($categorias_no_registradas) > 0) {

                foreach ($categorias_no_registradas as $key => $cat_id) {

                    // registramos categorias no registradas

                    $this->mymodel->insert("order_categories", [
                        "category_id" => $cat_id,
                        "catalog_id"=> $catalogo_fullvendor->catalog_id,
                        "company_id"=> $this->company_id,
                        "order"=> $order_category_catalog
                    ]);

                    $order_category_catalog = $order_category_catalog + 1;

                }
            }

            // validar si no existe la categoria por defecto en la tabla order_categories(category_id)
            if (!in_array($categoria_default->cat_id, array_column($order_categories_rows,'category_id'))) {

                $this->mymodel->insert("order_categories", [
                    "category_id" => $categoria_default->cat_id,
                    "catalog_id"=> $catalogo_fullvendor->catalog_id,
                    "company_id"=> $this->company_id,
                    "order"=>  $order_category_catalog + 1
                ]);

            }else {
                // si hay categorias no registradas entonces se debe actualizar el orden de la categoria por defecto a la ultima posision
                if (count($categorias_no_registradas) > 0) {

                    $this->mymodel->update([
                        "order" => $order_category_catalog,
                    ], 'order_categories', [
                        'company_id' => $this->company_id,
                        "catalog_id" => $catalogo_fullvendor->catalog_id,
                        "category_id" => $categoria_default->cat_id
                    ]);
                }

            }

        }
    }
    // Funcion para resetear el catalogo con las nuevas categorias
    // ingresadas desde el ABM de Catagorias
    public function reset_ordercatalog($catalogo_fullvendor){

        if (!$catalogo_fullvendor) return null;

        $categorias = $this->mymodel->get_by('categories', false, array('company_id' =>$this->company_id,'category_status'=>1,'category_default='=>null));
        $order = 1;

        foreach ($categorias as $cat) {

            $this->mymodel->insert("order_categories", [
                "category_id" => $cat->cat_id,
                "catalog_id"=>$catalogo_fullvendor->catalog_id,
                "company_id"=>$this->company_id,
                "order"=>$order
            ]);
            $order = $order + 1;
        }

        return $order;
    }

    public function get_token(){
        return hash('sha256', $this->get_codunique());
    }

    public function get_codunique()
    {
        return uniqid('', true);
    }

    public function generate_numbers($length)
	{
		$characters = '123456789';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

    public function get_codhash256(){
        return hash('sha256', "CoMpAnY". $this->company_id . $this->get_codunique());
    }

    public function enc_password($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

    public function creat_fullvendorcatalog($company_id=null){

        $compId = $company_id ? $company_id : $this->company_id;
        if ($compId === null || $compId === '') {
            return;
        }
        $categorias_activas =  $this->mymodel->get_by('categories', false, array('company_id' =>$compId,'category_status'=>1));


        $list_idcategs = array();

        foreach ($categorias_activas as $cat) {
            array_push($list_idcategs,$cat->cat_id);
        }

        $categories_unidas = implode(',',$list_idcategs); // todas los id categorias juntos ejemplo 54864,54643,21354

        $catalogo_fullvendor =  $this->mymodel->get_by('catalog', true, array('company_id' =>$compId,'default_catalog'=>1));
        $categoria_default =  $this->mymodel->get_by('categories', true, array('company_id' =>$compId,'category_default'=>1));

        if ($catalogo_fullvendor!=null) {
            // si existe catalogo fullvendor actualizamos el orden de catalogos
            // echo json_encode($catalogo_fullvendor);
            $mydata = array('cat_id' =>$categories_unidas);
            $this->mymodel->update($mydata, 'catalog', ['company_id'=>$compId,'catalog_id'=>$catalogo_fullvendor->catalog_id]);
            $this->updateorder_catalogo($catalogo_fullvendor,$categoria_default);

        }else{
            // creamos
            $mydata = array(
                'cat_id' =>$categories_unidas,
                'company_id' => $compId,
                'catalog_name'=> 'FullVendor Catalog',
                'status' => 1,
                'created' =>date("Y-m-d H:i:s"),
                'default_catalog'=>1
            );
            $this->mymodel->insert("catalog", $mydata); // creas el catalogo
            $catalogo_fullvendor =  $this->mymodel->get_by('catalog', true, array('company_id' =>$compId,'default_catalog'=>1));
            // Creas el ordern categorias del catalogo por defecto
            $this->updateorder_catalogo($catalogo_fullvendor,$categoria_default);

        }

        // Limpiar order_categories huérfanas (catalog_id/category_id NULL o catálogo inexistente)
        $this->db->query(
            "DELETE FROM order_categories WHERE company_id = ? AND (catalog_id IS NULL OR category_id IS NULL OR catalog_id NOT IN (SELECT catalog_id FROM catalog WHERE company_id = ?))",
            [(int) $compId, (int) $compId]
        );

        // Sincronizar catalog_category para que el CRUD de catálogos muestre todas las categorías en FullVendor Catalog
        $this->syncFullVendorCatalogCategory($catalogo_fullvendor);
    }

    /**
     * Sincroniza la tabla catalog_category con el orden de order_categories para el catálogo FullVendor.
     * Así el listado/edición en /company/catalog muestra todas las categorías asignadas.
     */
    protected function syncFullVendorCatalogCategory($catalogo_fullvendor)
    {
        if (!$catalogo_fullvendor || !isset($catalogo_fullvendor->catalog_id)) {
            return;
        }
        $catalogId = (int) $catalogo_fullvendor->catalog_id;
        $order_rows = $this->mymodel->get_by('order_categories', false, [
            'catalog_id' => $catalogId,
            'company_id' => $this->company_id
        ]);
        if (empty($order_rows)) {
            return;
        }
        usort($order_rows, function ($a, $b) {
            return (int) $a->order - (int) $b->order;
        });
        $this->db->query("DELETE FROM catalog_category WHERE catalog_id = ?", [$catalogId]);
        $order = 1;
        foreach ($order_rows as $row) {
            $this->mymodel->insert('catalog_category', [
                'catalog_id'  => $catalogId,
                'category_id' => $row->category_id,
                'order'       => $order++
            ]);
        }
    }

    public function cast($array_list)
    {
        return gettype($array_list) == "array" ? (object) $array_list : $array_list;
    }

    public function clean_customersnullables(){

		$this->mymodel->delete("customers",["company_id"=>null]);

	}

    public function removespaces($string){

        return rtrim(ltrim($string));
    }

    /** @var object|null Cache del formato de fecha de la empresa (también asignable desde Company_Controller). */
    public $cached_format_date = null;

    public function format_date($language_id = null)
    {
        $normalize = static function ($fmt) {
            return function_exists('fv_normalize_format_date_object')
                ? fv_normalize_format_date_object($fmt)
                : $fmt;
        };

        if (is_object($this->cached_format_date) && ! empty($this->cached_format_date->format_php)) {
            $this->cached_format_date = $normalize($this->cached_format_date);

            return $this->cached_format_date;
        }

        $this->ensureCompanyInfoLoaded();
        $CI = &get_instance();

        // Preferir el formato ya inyectado por Company_Controller (misma request).
        if (is_object($CI) && isset($CI->fv_format_date) && is_object($CI->fv_format_date)
            && ! empty($CI->fv_format_date->format_php)) {
            $this->cached_format_date = $normalize($CI->fv_format_date);

            return $this->cached_format_date;
        }

        $companyFormatDateId = (int) ($this->company_info->format_date_id ?? 0);
        // Si company_info llegó incompleto (solo company_id), pedir format_date_id explícitamente.
        if ($companyFormatDateId <= 0 && ! empty($this->company_id) && is_object($CI) && method_exists($CI, 'getObjectFromApi')) {
            $companyRow = $CI->getObjectFromApi("company/{$this->company_id}", [
                'props' => ['company_id', 'format_date_id', 'format_id'],
            ]);
            if (is_object($companyRow) && ! isset($companyRow->error)) {
                $companyFormatDateId = (int) ($companyRow->format_date_id ?? 0);
                if (is_object($this->company_info)) {
                    $this->company_info->format_date_id = $companyFormatDateId;
                    if (isset($companyRow->format_id)) {
                        $this->company_info->format_id = $companyRow->format_id;
                    }
                }
            }
        }

        if ($companyFormatDateId > 0 && is_object($CI) && method_exists($CI, 'getObjectFromApi')) {
            $formato_date = $CI->getObjectFromApi("formatdate/{$companyFormatDateId}");
            if (is_object($formato_date) && ! isset($formato_date->error) && ! empty($formato_date->format_php)) {
                $this->cached_format_date = $normalize($formato_date);

                return $this->cached_format_date;
            }
        }

        if (is_object($CI) && method_exists($CI, 'getObjectFromApi')) {
            $formato_date = $CI->getObjectFromApi('formatdate/default');
            if (is_object($formato_date) && ! isset($formato_date->error) && ! empty($formato_date->format_php)) {
                $this->cached_format_date = $normalize($formato_date);

                return $this->cached_format_date;
            }
        }

        $this->cached_format_date = fv_default_format_date_object();

        return $this->cached_format_date;
    }

    public function format_numeric($formatId = null){

        $this->ensureCompanyInfoLoaded();
        $CI = &get_instance();
        $companyFormatId = $formatId ?: ($this->company_info->format_id ?? null);
        $resource = $companyFormatId ? "formatnumeric/{$companyFormatId}" : 'formatnumeric/default';
        $format = $CI->getObjectFromApi($resource);
        if (is_object($format) && ! isset($format->error)) {
            return $format;
        }
        $default = $CI->getObjectFromApi('formatnumeric/default');
        return (is_object($default) && ! isset($default->error)) ? $default : null;
    }

    public function currency($currencyId = null){

        $this->ensureCompanyInfoLoaded();
        $CI = &get_instance();
        $companyId = $this->company_info->company_id ?? $this->company_id;
        if ($companyId) {
            $currency = $CI->getObjectFromApi("company/{$companyId}/currency");
            if (is_object($currency) && ! isset($currency->error)) {
                return $currency;
            }
        }
        return null;
    }

    public function replace_comentario($string){

        $lang = $this->HelperLanguage->lang_order();

        return sprintf($string, '',
                                $lang['address_catalog'],
                                $lang['zipcode_catalog'],
                                $lang['city_catalog'],
                                $lang['state_catalog'],
                                $lang['shipping_catalog'],
                                $lang['comments_catalog']
        );
    }

    public  function orderCommentJsonToStr($json): string
    {
        $lang = $this->HelperLanguage->lang_order();

        $orderDetail = json_decode($json);

        return $strToDisplay =
            $lang['address_catalog']." ".$orderDetail->delivery_address."<br>".
            $lang['zipcode_catalog']." ".$orderDetail->zip_code."<br>".
            $lang['city_catalog']." ".$orderDetail->city."<br>".
            $lang['state_catalog']." ".$orderDetail->state."<br>".
            $lang['zone_catalog']." ".$orderDetail->zone."<br>".
            $lang['country_catalog']." ".$orderDetail->country."<br>".
            $lang['shipping_catalog']." ".$orderDetail->shipping_notes."<br>".
            $lang['comments_catalog']." ".$orderDetail->comment_order."<br>";
    }

    public function deletecharactedspecials($string){

        $string = '^' .rtrim(ltrim($string)) . '$';
        return str_replace(
            array('[', ']', '\\', '.', '+', '*', '?', '(', ')', '{', '}'),
            array('\\\\[', '\\\\]', '\\\\\\\\', '\\\\.', '\\\\+', '\\\\*', '\\\\?', '\\\\(', '\\\\)', '\\\\{', '\\\\}'),
            $string
        );
    }



    public function new_botonstatus($request){

       $format ='<a href="%s" class="btn %s btn-sm w-100 boton_status_general" data-loader="%s">%s</a>';
       return sprintf($format, $request[0],
                               $request[1],
                               $request[2],
                               $request[3]);
    }

    public function update_orderdetails_general($company_id = null){

        $company_id = $this->company_info->company_id;
        $query = "update order_details od INNER JOIN orders om ON om.order_id = od.order_id
                  set od.amount_sales = od.qty * od.sale_price,
                  od.discount_amount = (od.qty * od.sale_price) * (IF(om.discount>0,om.discount/100,0)),
                  od.total_amount = (od.qty * od.sale_price)-((od.qty * od.sale_price) * (IF(om.discount>0,om.discount/100,0))),
                  od.amount_delivered = od.delivered_quantity * od.sale_price,
                  od.discount_delivered = (od.delivered_quantity * od.sale_price)*(IF(om.discount>0,om.discount/100,0)),
                  od.total_delivered = (od.delivered_quantity * od.sale_price)-((od.delivered_quantity * od.sale_price)*(IF(om.discount>0,om.discount/100,0))),
                  od.amount = IF(od.delivered_quantity <> 0, od.delivered_quantity * od.sale_price, od.qty * od.sale_price),
                  od.discountt = IF(od.delivered_quantity <> 0, (od.qty * od.sale_price)*(IF(om.discount>0,om.discount/100,0)),
                      (od.delivered_quantity * od.sale_price)*(IF(om.discount>0,om.discount/100,0))),
                  od.total = IF(od.delivered_quantity <> 0, (od.delivered_quantity * od.sale_price)-((od.delivered_quantity * od.sale_price) * (IF(om.discount>0,om.discount/100,0))),
                     (od.qty * od.sale_price)-((od.qty * od.sale_price)*(IF(om.discount>0,om.discount/100,0))))
                WHERE om.company_id = $company_id";
        $this->db->query($query);
    }

    public function update_caborder_general($company_id = null){

        date_default_timezone_set('America/Lima');
        $date_verified = date('Y-m-d H:i:s');

        $company_id = $this->company_info->company_id;
        $query = "UPDATE orders o INNER JOIN rs_orders rs  ON rs.order_id = o.order_id
                INNER JOIN customers c  ON c.customer_id = o.customer_id
                SET
                o.amount = rs.amount,
                o.discount_type = 1,
                o.discount = c.discount,
                o.discount_a = (rs.amount * (c.discount/100)),
                o.virified_byportal = '$date_verified',
                o.total_amount = rs.amount - (rs.amount * (c.discount/100)),
                o.amount_delivered = rs.amount_delivered,
                o.discount_delivered = rs.discount_delivered,
                o.total_delivered = rs.total_delivered WHERE o.company_id = $company_id and o.source = 'app' and o.virified_byportal is null";

        $this->db->query($query);
    }

    public function response_json($array_list){
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($array_list));
    }

    public function version(){

        $ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $v_imagen = $ahora->format("YmdHisu");
        return $v_imagen;
    }

    public function show_sessionmsg($msg,$type){

        $msg = '["'.$msg.'.", "'. $type .'", "#DD6B55"]';
        $this->session->set_flashdata('msg', $msg);
    }

    public function generar_token($length = 10){

		$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
		// Obtener la cadena de $ahora
		$ahoraStr = $ahora->format('Y-m-d H:i:s.u');
		// Aplicar hash SHA-256
		$hash = hash('sha256', $ahoraStr);
		// Recortar a 10 caracteres
		$hash10 = substr($hash, 0, $length);
		return $hash10;
	}


    /**
     * Menú por rol del navbar. Requiere un endpoint en la API que haga el join
     * navbar_rol_id + navbars + rol (TODO). Sin BD en el MVC devolvemos []: el header
     * cae al menú por defecto basado en traducciones.
     */
    public function get_roles_access($children = false , $config = null){
        return [];
    }

    public function get_modules(){

        $modul_fathers = $this->get_roles_access();
        $modul_children = $this->get_roles_access($children = true);

        foreach ($modul_children as $key => $children) {

            $children_id = $children->fhater_id;

            if(in_array($children_id, array_column($modul_fathers,'id'))){
              $index = array_search($children_id, array_column($modul_fathers,'id'));
              $modul_fathers[$index]->children[] = $children;
            }
          }
          return $modul_fathers;
    }

    /**
     * Estados por rol. Pendiente de endpoint en la API (status_rol + status_orders).
     * Sin BD en el MVC devolvemos [].
     */
    public function get_status_rol($rol_id){
        return [];
    }

    public function get_status_user_access(){
        return [];
    }


    public function obtener_colorSemaforo($product, $info_company) {
        $stock = $product->stock;
        $color_semaforo = $this->color_semaforoDefault;
        if($stock > 0){
            $redHasta=!empty($product->sem_red_hasta) ?$product->sem_red_hasta : $info_company->sem_red_hasta;
            $amarilloHasta=!empty($product->sem_amarillo_hasta) ? $product->sem_amarillo_hasta : $info_company->sem_amarillo_hasta;
            $color_semaforo= ($stock > $amarilloHasta) ? $this->verde : ( ($stock > $redHasta)  ? $this->amarillo : $this->red );
        }

        return $color_semaforo;
    }


    public function obtener_leyendaSemaforo($color_semaforo, $info_company){
        $leyendas = [$this->red=>$info_company->sem_red,$this->amarillo=>$info_company->sem_amarillo,$this->verde=>$info_company->sem_verde];
        // si existe leyenda para el color
        return $leyendas[$color_semaforo];
    }

    public function addDays($date,$expirationDays){

        $carbonDate = Carbon::parse($date);
        return $carbonDate->addDays($expirationDays)->format('Y-m-d H:i:s');

    }

}
