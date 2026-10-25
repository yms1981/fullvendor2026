<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . '/core/HasCatalog.php';

/**
 * @property Langmodel $langmodel
 */
class Home extends Salesman_Controller
{
    use HasPagination;
    protected $accessControlRole = 'seller';

    protected $forceAppChange = 'salesman';

    protected function hasAccess(): bool
    {
        return $this->getRole() == 'seller';
    }

    protected function isCustomerSelected():bool {
        return $this->getCustomerId() !== null;
    }

    function index()
    {
        if($this->isCustomerSelected()){
            redirect(seller_url('catalog'));
        }
        $this->selectCustomerView();
    }

    public function selectCustomerView()
    {
        $filterData = [];
        $filterData['props'] = ['customers.customer_id', 'business_name', 'name'];

        $paginatedList = $this->getPaginatedList(
            "vendor/{$this->getSellerId()}/customers",
            $filterData,
            $this->input->get('page') ?? 1,
            $this->input->get('per_page') ?? 20
        );

        $this->pagination->initialize(
            $this->getCodeIgniterPaginationConfig($paginatedList->total, $paginatedList->per_page)
        );

        $data = array();

        $data['info_seller'] = $this->getObjectFromApi("vendor/{$this->getSellerId()}", ['relations' => ['user']]);
        $data['info_company'] = $this->getObjectFromApi("company/{$this->getCompanyId()}");
        $data['lang_catalogo']= $this->langmodel->get_langsinceLogin('cmp-catalog');

        $data['customerSeller'] = $paginatedList->data;
        $data['totalCustomers'] = $paginatedList->total;

        $data['js_extra'] = [
            'accessseller/catalogo/change_numberFiltered.js',
            'accessseller/catalogo/clean_customer_panel.js',
            'accessseller/catalogo/click_customer.js',
            'accessseller/catalogo/create_rowcustomer.js',
            'accessseller/catalogo/doubleclick.js',
            'accessseller/catalogo/function-loader.js',
            'accessseller/catalogo/get_collapseElementList.js',
            'accessseller/catalogo/get_elemettabindex.js',
            'accessseller/catalogo/scroll-more-customers.js',
            'accessseller/catalogo/search_customer.js',
            'accessseller/catalogo/tab-index.js',
            'accessseller/catalogo/update_collapse.js',
            'accessseller/catalogo/search-filter.js'

        ];

        $data["version"] = $this->version;

        $this->showContent('salesman/select_customer', $data);
    }

    public function searchCustomer()
    {
        $text_tosearch = $this->input->post("text_tosearch");
        $page = isset($request["page"]) ? $request["page"] : 1;

        $customers = $this->getObjectFromApi("customer", [
            "company_id" => $this->getCompany(),
            "seller" => $this->getSellerId(),
            "textToSearch" => $text_tosearch,
            "status" => true,
            "page" => $page,
            "per_page" => 20
        ]);

        return $this->Helper->response_json(
            [
                "data" => $customers->data,
                "total_filtered" => $customers->total
            ]
        );
    }

    public function selectCustomer()
    {
        $customerId = $this->input->post("customer");

        if (!isset($customerId)) {
            $this->flashMessage('Debe seleccionar un Cliente'); //$this->lang_catalog['must_select']
            redirect(seller_url(),'refresh');
        }

        $this->setCustomerId($customerId);
        redirect(seller_url('catalog'));
    }

}
