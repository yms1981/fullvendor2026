<?php

require_once APPPATH . "core/CatalogCart.php";

/**
 * @property CI_Session $session
 */
trait HasCatalog
{
    use CatalogCart;
    use HasAuth;
    use HasPagination;

    protected $indexJs = [
        'app.js',
        'shoplist.js'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->initCart($this->getCustomerId(), $this->getCompanyId());
    }

    /**
     * Selects the given catalog Id
     * @param int $catalog
     * @return void
     */
    public function setCatalogId($catalog)
    {
        $this->setUserData('catalog', $catalog);
    }


    /**
     * Get the selected catalog Id
     * @return int
     */
    public function getCatalogId()
    {
        return $this->getUserData('catalog') ?? $this->getDefaultCatalog($this->getCompanyId())->catalog_id;
    }

    /**
     * Displays the selected catalog initial screen
     * @return void
     */
    protected function showCatalog()
    {
        $filterData = [];
        $this->benchmark->mark('show_catalog_start');

        $filterData['category_id'] = $this->input->get('cat_id') ?? null;
        $filterData['term'] = $this->input->get('search') ?? null;
        $filterData['sort'] = $this->input->get('sort') ?? null;

        $catalogId = $this->getCatalogId();
        $this->benchmark->mark('before_get_catalog_list');
        $paginatedList = $this->getPaginatedList(
            "catalog/{$catalogId}/customer/{$this->getCustomerId()}/products",
            $filterData,
            $this->input->get('page') ?? 1,
            $this->input->get('per_page') ?? 28
        );
        $this->benchmark->mark('catalog_list_got');

        $this->pagination->initialize(
            $this->getCodeIgniterPaginationConfig($paginatedList->meta->total, $paginatedList->meta->per_page, 'catalog')
        );

        $cart = $this->getCart();

        $catalog = $this->getObjectFromApi('catalog/' . $catalogId);
        $customer = $this->getObjectFromApi("customer/{$this->getCustomerId()}", ['relations' => ['customer_group']]);

        $pageData = [];
        $pageData['categorias'] = $this->getObjectFromApi("catalog/{$catalogId}/categories");
        $pageData['info_customer'] = $customer;

        $pageData['products'] = $paginatedList->data;
        $pageData['catalog_text'] = $catalog->catalog_name;
        $pageData['info_catalog'] = $catalog;
        $sellerId = $this->getSellerId() ?? $customer->sellers[0]->id;
        $pageData['info_seller'] = $this->getObjectFromApi("vendor/$sellerId", ['relations' => ['user']]); //change to 'seller_id'?

        $pageData['page'] = $paginatedList->meta;
        $pageData['product_detail'] = $this->getCartDetailes($cart->order_id);

        $pageData["js_vue"] = [
            "stores/stores_vue.js",
            "stores/search_product.js",
        ];

        $pageData['total'] = $cart->total;
        $pageData['qtyProducts'] = count($cart->order_details);

        $pageData['js'] = $this->indexJs;

        $application = $this->getApplication();
        $pageData['showSellerButtons'] = $application == 'salesman';

        $this->benchmark->mark('show_catalog_end');

        $this->addJsFile('/companyasset/antomi/assets/js/jquery.toast.js');
        $this->addJsFile('/companyasset/antomi/assets/js/main.js');

        $this->showContent('marketplace/shop', $pageData);
    }

        public function priceList()
    {
        $filterData = [];

        $filterData['category_id'] = $this->input->get('cat_id') ?? null;
        $filterData['term'] = $this->input->get('search') ?? null;
        $filterData['sort'] = $this->input->get('sort') ?? null;

        $companyId = $this->getCompanyId();
        $customerId = $this->getCustomerId();

        $export = $this->apiRawRequest("catalog/company/{$companyId}/customer/{$customerId}/price-list-export", $filterData);
        header('Content-Disposition: ' . $export->getHeader('Content-Disposition')[0]);
        header('Content-Type: ' . $export->getHeader('Content-Type')[0]);
        header('Content-Length: ' . $export->getHeader('Content-Length')[0]);
        header('Accept-Ranges: ' . $export->getHeader('Accept-Ranges')[0]);

        echo $export->getBody()->getContents();
    }


    public function getDefaultCatalog($companyId)
    {
        return $this->getObjectFromApi("company/{$companyId}/catalogs/default");
    }

    public function searchProducts(): void
    {
        $term = $this->input->get('term');

        $products = $this->getObjectFromApi("catalog/{$this->getCatalogId()}/customer/{$this->getCustomerId()}/products",
            [
                'term' => $term,
                'all' => true,
                'props' => ['pro_id','name','sku']
            ]);

        echo !$this->hasError($products) ? json_encode($products) : json_encode('error');
    }

    public function getProductById(): void
    {
        $product_id = $this->input->get('id');

        $product = $this->getObjectFromApi("product/{$product_id}",
            [
                'props' => ['pro_id','name','sku','minimum_stock','unit_type','price_reduced','sale_price', 'stock','descriptions'],
                'relations' => ['images'],
            ]);

        echo !$this->hasError($product) ? json_encode($product) : json_encode('error');
    }

}
