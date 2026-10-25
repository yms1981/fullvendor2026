<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @property OrdenModel $OrdenModel
 * */
class ProductModel extends CI_Model
{
    use ApiClient;

    public $company = array();
    public $lang_seller = array();

    function __construct()
	{
        parent::__construct();
        $this->getHttpClient();
        $this->company = $this->Helper->get_company_info();
		$this->load->model('CategoryModel');
		$this->load->model('CatalogoModel');
		$this->load->model('ImageModel');
    }

	public function getAllwithimages () {

		$version = substr($this->Helper->get_codhash256(), 0, 6);
		$image_emty = base_url("uploads/noimg.png?v1=$version");
		$company_id = $this->company->company_id;

		$query = "SELECT p.name,p.status,p.pro_id,p.sku, '$image_emty' AS images, CONCAT(p.sku,' | ',p.name) AS completed_name
				  FROM products AS p
				  WHERE p.company_id = $company_id
				  AND p.status=1
				  AND p.name is not null
				  and p.sale_price > 0";

		$productos = $this->db->query($query)->result();
		$product_imagesDefault = $this->ImageModel->product_imagesDefault($company_id);

		foreach ($product_imagesDefault as $pro){

            if(in_array($pro->product_id, array_column($productos,'pro_id'))){
                $index = array_search($pro->product_id, array_column($productos,'pro_id'));
                $productos[$index]->images = file_exists('./uploads/products/'.$company_id . '/'.$pro->images)
											 ? base_url("uploads/products/$company_id/$pro->images?v1=$version")
											 : $image_emty;
            }
        }

		return $productos;
	}

	public function founded_incatalog($productId,$compId){

		$catalogs = $this->CatalogoModel->all();
		foreach ($catalogs as $key => $cat) {
			$query = "SELECT p.pro_id, p.sku, p.name, p.catalog_name FROM (SELECT od.order,p.*,catl.catalog_name,NULL AS images,NULL AS cImg,
				NULL AS implode_img,null as imagen_js FROM order_categories AS od
				INNER JOIN catalog AS catl ON catl.catalog_id = od.catalog_id
				INNER JOIN categories AS c ON c.cat_id = od.category_id
				INNER JOIN products AS p ON FIND_IN_SET(od.category_id,p.category_id)
				WHERE od.company_id = $compId
				AND od.catalog_id = $cat->catalog_id
				AND p.`status` = 1
				AND c.category_status = 1
				AND p.name is not NULL
				and p.sale_price > 0
				UNION
				SELECT NULL AS `order`,p.*,c.catalog_name,NULL AS images, NULL AS cImg,
				NULL AS implode_img,null as imagen_js
				FROM products AS p
				INNER JOIN catalog AS c ON FIND_IN_SET(p.pro_id, c.product_name)
				WHERE c.company_id = $compId AND c.catalog_id = $cat->catalog_id)
				AS p WHERE p.company_id = $compId AND p.pro_id = $productId";

			$result = $this->db->query($query)->num_rows();

			if ($result > 0) {

				return [
					true,
					$cat->catalog_name
				];
			}
		}
		return [
			false
		];
	}

	public function get_imagesNotComprimed($product_id){

		return $this->mymodel->get_by("product_images", false ,
			[
				"company_id"=> $this->company->company_id,
				"product_id"=> $product_id,
				"comprimed" => 0
			]);
	}


	public function update_productoId(){

		$id_company = $this->company->company_id;
		$query = "UPDATE products SET product_id = pro_id WHERE company_id = $id_company AND product_id IS NULL ";
		$this->db->query($query);
	}

	public function get($id){
		return $this->mymodel->get_by("products",true,["company_id"=>$this->company_info->company_id, "pro_id"=>$id]);
	}

	public function have_moves($id){

		$compId = $this->company->company_id;
		$query = "SELECT o.company_id,o.order_id,od.product_id,od.qty FROM order_details AS od
				INNER JOIN orders AS o ON od.order_id = o.order_id
		        WHERE o.company_id = $compId
				and od.product_id = $id
		        ORDER BY od.product_id";

		return $this->db->query($query)->result();
	}

	public function haver_carts($id){
		$compId = (int) $this->company->company_id;
		$productId = (int) $id;
		$lines = $this->getObjectFromApi(
			"catalogcart/company/{$compId}/product/{$productId}/open-cart-lines"
		);

		if (! is_array($lines)) {
			return [];
		}

		return array_map(static function ($line) {
			return is_object($line) ? $line : (object) $line;
		}, $lines);
	}

	public function filter_productoByIds($IdsProducts,$request_post){

		$compId = $this->company_info->company_id;
		$query = "SELECT p.* FROM products as p WHERE p.company_id = $compId AND p.pro_id IN($IdsProducts)";

		$order_type = $request_post['order_type'];
		$order_sort = $request_post['sort'];

		if ($order_type!='' && $order_sort!='') {

			switch ($order_sort) {
				case 'id':
				$query .= " ORDER BY p.pro_id " . $order_type;
				break;
				case 'sku':
				$query .= " ORDER BY p.sku " . $order_type;
				break;
				case 'name':
				$query .= " ORDER BY p.name " . $order_type;
				break;
				case 'price':
				$query .= " ORDER BY p.sale_price " . $order_type;
				break;
				case 'stock':
				$query .= " ORDER BY p.stock " . $order_type;
				break;
				case 'moq':
				$query .= " ORDER BY p.minimum_stock " . $order_type;
				break;
				case 'tags':
				$query .= " ORDER BY p.tags " . $order_type;
				break;
				case 'location':
				$query .= " ORDER BY p.locations " . $order_type;
				break;
			}

		}else{
			$query .= " ORDER BY p.pro_id DESC";
		}

		return $this->db->query($query)->result();
	}

    public function update_categories($categories_updated,$product_id){

        $this->mymodel->update(array(
			'category_id' => $categories_updated),
			'products',
			array(
			'company_id'=>$this->company->company_id,
			'pro_id'=>$product_id));
	}

    public function return_category_toassgined($category_toasigned,$categories_found,$type_asign){

		$category_default = $this->CategoryModel->get_default();

		// valida si la categoria por defecto esta en las categorias del producto
		if (in_array($category_default->cat_id, $categories_found)) {

			$key = array_search($category_default->cat_id, $categories_found);
			// Elimina el elemento del array
			unset($categories_found[$key]);
		}

		if ($type_asign) {

			foreach($category_toasigned as $new_category){

					if (!in_array($new_category,$categories_found)) {
						array_push($categories_found, $new_category);
					}

			}
			$categories_found = implode(',',$categories_found);

		}else{

			foreach($category_toasigned as $new_category){
				if (in_array($new_category,$categories_found)) {

					$clave = array_search($new_category, $categories_found);
					unset($categories_found[$clave]);
				}
			}
			$categories_found = implode(',',$categories_found);
		}


		if(!empty($categories_found)){

			return $categories_found[0] ==','
					   ?substr($categories_found,1)
					   :$categories_found;
		}

		return "";
	}

    public function update_status_masive($products_id, $status){

        $id_company = $this->company->company_id;
        $this->db->query("UPDATE products SET status = $status WHERE company_id = $id_company AND pro_id IN('$products_id')");
    }

	public function add_productToCatalog($request){

	}

}
