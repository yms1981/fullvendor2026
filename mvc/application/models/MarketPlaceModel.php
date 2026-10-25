<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @property OrdenModel $OrdenModel
 * */
class MarketPlaceModel extends CI_Model
{
    public $company_info = array();

    function __construct()
	{
        parent::__construct();
		$this->load->model("OrdenModel");
    }

    public function get_cataloDefault($company_id){

        return $this->mymodel->get_by('catalog', true, ['company_id' => $company_id, 'default_catalog'=> 1]);
    }

    public function get_customer($email){
        if ($email) {
            # code...
            $query = "SELECT com.company_id,c.password , com.name AS company_name ,com.language_id AS lang, com.image , com.small_image,c.email,c.name, c.business_name, c.customer_id
                      FROM customers AS c
                      INNER JOIN companies AS com ON c.company_id = com.company_id
                      WHERE c.email = '$email' and c.customer_status=1";
            return $this->db->query($query)->result();
        }
        return [];
    }

    public function format_numerictext_fromcatalog($number_toformat,$company_id){

		$company = $this->mymodel->get_by('companies', true, ['company_id'=> $company_id]);
		$format_numeric = $company->format_id?$this->mymodel->get_by('format_numeric',true,['format_id'=>$company->format_id]):$this->mymodel->get_by('format_numeric',true,['default'=>1]);
		return number_format($number_toformat, $format_numeric->digits,$format_numeric->radixPoint,$format_numeric->groupSeparator);

	}

    public function get_product($company_id, $product_id){
        return $this->mymodel->get_by('products', true,['company_id'=>$company_id,'pro_id'=>$product_id]);
    }

    public function obtener_product_car($pro_id){

		$consulta = "SELECT p.name, p.stock, p.pro_id, p.sale_price,p.price_reduced, pi.images, p.sku, p.minimum_stock, p.notify_minimum_stock
                    FROM products AS p
                    LEFT JOIN product_images AS pi ON p.product_id = pi.product_id
                    WHERE p.pro_id = $pro_id AND (pi.img_default = 1 OR pi.images IS NULL)
                    GROUP BY pi.product_id;";

		$result = $this->db->query($consulta)->row();
		return $result;
	}

    public function format_import($import,$company){
        return $this->CatalogoPublicModel->format_numerictext_fromcatalog($import,$company->company_id);
    }

    public function get_productsModified($products = [],$company,$customer,$format = true, $session_values = []){

        $id_company = $company->company_id;
        $detalleProduct_modified = [];


        if(count($session_values) > 0) {
            // vaciamos carrito
            $this->mymodel->delete('detail_car', ['session_id'=>$this->SESSION_CART->id]);
        }

        foreach ($products as $key => $product) {

            $product = $this->Helper->cast($product);
            $productFound = $format ? $this->obtener_product_car($product->id) : $this->get_product($company->company_id, $product->id);

            $sale_price = $productFound->price_reduced <> 0 ? $productFound->price_reduced : $this->OrdenModel->recalcularPrecio($customer, $productFound->sale_price);
            $image = $format ? $productFound->images : "";

            $url_image = "./uploads/products/$id_company/$image";

            // Si existe valores de session se Guarda el detalle
            if(count($session_values) > 0) {

                $product_detail = $this->mymodel->get_by('detail_car', true,
                            [
                                'session_id'=>$session_values['session_id'],
                                'product_id'=>$product->id
                            ]);

                if ($product_detail != null) {
                    $this->mymodel->update(['qty'=>$product->qty,'comment' => isset($product->comments) ? $product->comments : ''], 'detail_car',
                    [

                        'session_id'=>$session_values['session_id'],
                        'product_id'=>$product->id,

                    ]);
                }else{

                    $mydata = array(
                        'company_id' => $company->company_id,
                        'customer_id' => $session_values['customer_id'],
                        'session_id' => $session_values['session_id'],
                        'product_id' =>$product->id,
                        'qty' =>$product->qty,
                        'sale_price'=>$sale_price,
                        'comment' => isset($product->comments) ? $product->comments : '',
                        'created' =>date("Y-m-d H:i:s")
                    );
                    $this->mymodel->save('detail_car', $mydata);
                }
            }
            array_push($detalleProduct_modified,[
                "id" => $productFound->pro_id,
                "sale_price" => $format ? $this->format_import($sale_price, $company) : $sale_price,
                "qty" => $product->qty,
                "sku" => $productFound->sku,
                "name" => $productFound->name,
                "comments" => isset($product->comments) ? $product->comments : '',
                "total_amount" => $format ? $this->format_import($sale_price * $product->qty, $company) : $sale_price * $product->qty,
                "images" => file_exists($url_image)? base_url("uploads/products/$id_company/$image") : base_url('uploads/noimg.png'),
            ]);
        }
        return $detalleProduct_modified;
    }

    public function get_detalleTotal($company, $products = [], $customer, $format = true) {

        $total_orden = 0.00; $sub_total = 0.00  ;$discount = 0.00;

        if ($products ==null) return [
                        'total_orden'=> $total_orden,
                        'discount_total'=> $discount,
                        'sub_total'=> $sub_total,
                        'cantidad_productos'=>0,
                        'success'=>true,
                    ];

        foreach ($products as $key => $product) {
            $product = $this->Helper->cast($product);
            $productFound = $this->get_product($company->company_id, $product->id);
            $sale_price = $productFound->price_reduced <> 0 ? $productFound->price_reduced : $this->OrdenModel->recalcularPrecio($customer, $productFound->sale_price);
            $sub_total = $sub_total + ($sale_price * $product->qty);
        }

        if ($customer->discount != null && $customer->discount != 0) {
			$discount = $sub_total * ($customer->discount/100);
		}
        $total_orden = $sub_total - $discount;

        return [
            'total_orden'=> $format ? $this->format_import($total_orden, $company) : $total_orden,
            'discount_total'=> $format ? $this->format_import($discount, $company) : $discount,
            'sub_total'=> $format ? $this->format_import($sub_total, $company) : $sub_total,
            'cantidad_productos'=> count($products),
            'success'=>true,
            'sin_formato' => [
                'total_orden'=> $total_orden,
                'discount_total'=> $discount,
                'sub_total'=> $sub_total,
            ]
        ];

    }

    public function get_seller($customer){

        $sellers = $customer->user_id;

        if ($sellers) {
            $query = "SELECT * FROM seller WHERE company_id = 3 AND user_id IN ($sellers)";
            $result = $this->db->query($query)->result();

            return count($result)>0
                    ?$result[0]
                    :$this->mymodel->get_by('sellers', true, ['company_id'=>$customer->company_id,'default'=>1]);
        }
        return $this->mymodel->get_by('sellers', true, ['company_id'=>$customer->company_id,'default'=>1]);
    }

    public function get_productos_FullvendorCatalog(){

    }


    public function get_categories(){



    }

}
