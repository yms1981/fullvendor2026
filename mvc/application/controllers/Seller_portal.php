<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Portal vendedor: /sellers/login, /sellers/customer, catálogo y pedidos.
 * Login vía API v2/seller/login y token Sanctum v2/user/token.
 *
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Session $session
 * @property Helper $Helper
 */
class Seller_portal extends MY_Controller
{
    private const SELLER_COMPANY_KEY = 'seller_portal_company_id';
    private const SELLER_ID_KEY = 'seller_portal_seller_id';
    private const SELLER_LANG_KEY = 'seller_portal_language_id';
    private const CATALOG_CUSTOMER_KEY = 'seller_portal_catalog_customer_id';
    private const CATALOG_CART_KEY = 'seller_portal_catalog_cart';
    /** IDs de clientes vistos en el listado API del catálogo (última carga GET); respaldo si la API falla en POST. */
    private const CATALOG_ASSIGNABLE_IDS_KEY = 'seller_portal_catalog_assignable_customer_ids';
    /** Nombre de empresa para cabecera del portal (viene del login API; evita leer `companies` en la pestaña clientes). */
    private const SELLER_COMPANY_DISPLAY_NAME_KEY = 'seller_portal_company_display_name';
    private const SELLER_ALL_CUSTOMERS_FLAG_KEY = 'seller_portal_all_customers_flag';
    private const SELLER_DEFAULT_TERM_ID_KEY = 'seller_portal_default_term_id';
    private const SELLER_DEFAULT_GROUP_ID_KEY = 'seller_portal_default_group_id';

    /** @var array<int, array{business: string, contact: string, email: string, phone: string, cell: string, address: string}> */
    private $sellerPortalCustomerGridApiCache = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->setApplication('marketplace');

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $this->portalLoginJson();

            return;
        }

        if ($this->session->has_userdata(self::SELLER_COMPANY_KEY) && $this->session->has_userdata(self::SELLER_ID_KEY)) {
            redirect(site_url('sellers/customer'));

            return;
        }

        $this->load->clear_vars();
        $data = $this->basePortalData();
        $data['authenticate_path'] = $this->portalUriPath('sellers/login');
        $data['menu_path'] = $this->portalUriPath('sellers/customer');
        $this->load->view('seller/login', $data);
    }

    /**
     * Inicio del portal vendedor (URL /sellers/customer; no confundir con portal cliente).
     */
    public function customer(): void
    {
        $this->requireSellerSession();
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $action = strtolower(trim((string) $this->input->post('seller_customer_action')));
            if ($action === 'delete') {
                $result = $this->deleteSellerPortalCustomerAssignmentFromPost($sellerId, $companyId);
            } elseif ($action === 'update') {
                $result = $this->updateSellerPortalCustomerFromPost($sellerId, $companyId);
            } else {
                $result = $this->createSellerPortalCustomerFromPost($sellerId, $companyId);
            }
            $this->session->set_flashdata('seller_customer_flash', $result['code'] ?? 'seller_customer_create_error');
            if (! empty($result['extra']) && is_string($result['extra'])) {
                $this->session->set_flashdata('seller_customer_flash_extra', $result['extra']);
            }
            redirect(site_url('sellers/customer'));

            return;
        }

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'home';
        $data = array_merge($data, $this->sellerPortalCustomerFormFieldsDataForCustomerTab($sellerId, $companyId));
        $data['seller_customer_table_columns'] = $this->sellerPortalCustomerTableColumnVisibilityForCustomerTab();
        $data['seller_all_customers_mode'] = $this->sellerHasAllCustomersMode($sellerId);
        $data['seller_customer_flash'] = $this->session->flashdata('seller_customer_flash');
        $data['seller_customer_flash_extra'] = $this->session->flashdata('seller_customer_flash_extra');
        $allCustomers = $this->fetchAssignedCustomersForSellerPortalCustomerTabApiOnly($sellerId, $companyId);
        $listFilters = $this->sellerPortalCustomerListFiltersFromInput();
        $filteredCustomers = $this->sellerPortalFilterCustomersForPortal($allCustomers, $listFilters);
        $perPage = 30;
        $totalFiltered = count($filteredCustomers);
        $page = max(1, $listFilters['page']);
        $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $data['seller_customers'] = array_slice($filteredCustomers, $offset, $perPage);
        $data['seller_customers_total_all'] = count($allCustomers);
        $data['seller_customers_total_filtered'] = $totalFiltered;
        $data['seller_customers_page'] = $page;
        $data['seller_customers_per_page'] = $perPage;
        $data['seller_customers_total_pages'] = $totalPages;
        $data['seller_customer_filter'] = $listFilters;
        $data['portal_page_title'] = (string) (($data['pageTexts']['customer_tab_title'] ?? null) ?: ($data['pageTexts']['menu_tab_home'] ?? ''));
        $this->load->view('seller/customer', $data);
    }

    public function customer_new(): void
    {
        $this->requireSellerSession();
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();
        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'home';
        $data = array_merge($data, $this->sellerPortalCustomerFormFieldsDataForCustomerTab($sellerId, $companyId));
        $data['seller_customer_form_page_mode'] = 'create';
        $data['seller_customer_form_page_seed'] = null;
        $data['seller_customer_form_back_url'] = site_url('sellers/customer');
        $pt = is_array($data['pageTexts'] ?? null) ? $data['pageTexts'] : [];
        $data['portal_page_title'] = (string) (($pt['customer_form_title_add'] ?? null) ?: ($pt['customer_tab_title'] ?? ''));
        $this->load->view('seller/customer_form', $data);
    }

    public function customer_edit($customerId = null): void
    {
        $this->requireSellerSession();
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();
        $cid = (int) $customerId;
        if ($cid <= 0) {
            redirect(site_url('sellers/customer'));

            return;
        }
        $seed = $this->sellerPortalCustomerFormSeedById($sellerId, $companyId, $cid);
        if ($seed === null) {
            $this->session->set_flashdata('seller_customer_flash', 'seller_customer_form_not_found');
            redirect(site_url('sellers/customer'));

            return;
        }
        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'home';
        $data = array_merge($data, $this->sellerPortalCustomerFormFieldsDataForCustomerTab($sellerId, $companyId));
        $data['seller_customer_form_page_mode'] = 'edit';
        $data['seller_customer_form_page_seed'] = $seed;
        $data['seller_customer_form_back_url'] = site_url('sellers/customer');
        $pt = is_array($data['pageTexts'] ?? null) ? $data['pageTexts'] : [];
        $data['portal_page_title'] = (string) (($pt['customer_form_title_edit'] ?? null) ?: ($pt['customer_tab_title'] ?? ''));
        $this->load->view('seller/customer_form', $data);
    }

    public function customer_view($customerId = null): void
    {
        $this->requireSellerSession();
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();
        $cid = (int) $customerId;
        if ($cid <= 0) {
            redirect(site_url('sellers/customer'));

            return;
        }
        $seed = $this->sellerPortalCustomerFormSeedById($sellerId, $companyId, $cid);
        if ($seed === null) {
            $this->session->set_flashdata('seller_customer_flash', 'seller_customer_form_not_found');
            redirect(site_url('sellers/customer'));

            return;
        }
        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'home';
        $data = array_merge($data, $this->sellerPortalCustomerFormFieldsDataForCustomerTab($sellerId, $companyId));
        $data['seller_customer_form_page_mode'] = 'view';
        $data['seller_customer_form_page_seed'] = $seed;
        $data['seller_customer_form_back_url'] = site_url('sellers/customer');
        $pt = is_array($data['pageTexts'] ?? null) ? $data['pageTexts'] : [];
        $data['portal_page_title'] = (string) (($pt['customer_form_title_view'] ?? null) ?: ($pt['customer_tab_title'] ?? ''));
        $this->load->view('seller/customer_form', $data);
    }

    /**
     * Maestros del formulario cliente (pestaña Inicio / clientes): solo API v2.
     *
     * @return array<string, mixed>
     */
    private function sellerPortalCustomerFormFieldsDataForCustomerTab(int $sellerId, int $companyId): array
    {
        $data = [];
        $data['seller_customer_groups'] = $this->sellerPortalCustomerGroupsListFromApi($companyId);
        $gdef = (int) ($this->session->userdata(self::SELLER_DEFAULT_GROUP_ID_KEY) ?? 0);
        if ($data['seller_customer_groups'] === [] && $gdef > 0) {
            $data['seller_customer_groups'] = [['group_id' => $gdef, 'name' => '#' . (string) $gdef]];
        }
        $data['seller_create_terms'] = $this->sellerPortalTermsListFromApi($companyId);
        $defaultTermIdForForm = (int) ($this->session->userdata(self::SELLER_DEFAULT_TERM_ID_KEY) ?? 0);
        if ($data['seller_create_terms'] === [] && $defaultTermIdForForm > 0) {
            $data['seller_create_terms'] = [['term_id' => $defaultTermIdForForm, 'name' => '#' . (string) $defaultTermIdForForm]];
        }
        $sellerList = $this->sellerPortalSellersListRowsFromApi($companyId);
        $currentName = '#' . (string) $sellerId;
        foreach ($sellerList as $sr) {
            if ((int) ($sr['id'] ?? 0) === $sellerId) {
                $currentName = (string) ($sr['name'] ?? $currentName);

                break;
            }
        }
        $data['seller_create_current_seller_name'] = $currentName;
        $data['seller_create_current_seller_id'] = $sellerId;
        if ($sellerList === []) {
            $sellerList = [['id' => $sellerId, 'name' => $currentName]];
        }
        $data['seller_create_sellers'] = $sellerList;
        $data['seller_create_countries'] = $this->sellerPortalCountriesSelectListForForms();
        $data['seller_create_default_country_id'] = $this->sellerPortalCompanyDefaultCountryId($companyId);
        $data['seller_create_default_term_id'] = $defaultTermIdForForm;
        $data['seller_create_default_group_id'] = $gdef;

        return $data;
    }

    /**
     * @return list<array{group_id: int, name: string}>
     */
    private function sellerPortalCustomerGroupsListFromApi(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }
        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/form-masters/customer-groups');
        $resp = is_array($json) ? ($json['data'] ?? []) : [];
        $out = [];
        foreach ($this->sellerPortalCoerceApiRowsList($resp) as $row) {
            $gid = (int) ($row['group_id'] ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $out[] = [
                'group_id' => $gid,
                'name' => trim((string) ($row['name'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{term_id: int, name: string}>
     */
    private function sellerPortalTermsListFromApi(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }
        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/form-masters/terms');
        $resp = is_array($json) ? ($json['data'] ?? []) : [];
        $out = [];
        foreach ($this->sellerPortalCoerceApiRowsList($resp) as $row) {
            $tid = (int) ($row['term_id'] ?? 0);
            if ($tid <= 0) {
                continue;
            }
            $out[] = [
                'term_id' => $tid,
                'name' => trim((string) ($row['name'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function sellerPortalSellersListRowsFromApi(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }
        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/form-masters/sellers');
        $resp = is_array($json) ? ($json['data'] ?? []) : [];
        $out = [];
        foreach ($this->sellerPortalCoerceApiRowsList($resp) as $row) {
            $sid = (int) ($row['id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $parts = [];
            foreach (['first_name', 'last_name', 'name'] as $k) {
                $t = trim((string) ($row[$k] ?? ''));
                if ($t !== '') {
                    $parts[] = $t;
                }
            }
            $u = $row['user'] ?? null;
            if (is_array($u)) {
                $un = trim((string) ($u['name'] ?? ''));
                if ($un !== '') {
                    $parts[] = $un;
                }
            }
            $nm = $parts !== [] ? implode(' ', array_unique($parts)) : ('#' . (string) $sid);
            $out[] = ['id' => $sid, 'name' => $nm];
        }

        return $out;
    }

    /**
     * Países para desplegables de cliente: misma petición y resolución de datos que `company/customers/Add::run`.
     * Solo API; sin fallback local (igual que alta/edición cliente en company).
     *
     * @return list<array{id: int, name: string, sortname: string, phonecode: int, phonemask: string}>
     */
    private function sellerPortalCountriesSelectListForForms(): array
    {
        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/form-masters/countries');
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            return [];
        }
        $rawList = $json['data'];
        $out = [];
        foreach ($rawList as $row) {
            $row = $this->sellerPortalCountryApiRowToAssocArray($row);
            if ($row === null) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => trim((string) ($row['name'] ?? '')),
                'sortname' => strtolower((string) ($row['sortname'] ?? '')),
                'phonecode' => (int) ($row['phonecode'] ?? 0),
                'phonemask' => trim((string) ($row['phonemask'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @param mixed $row Fila de la API (array o stdClass; JSON:API con attributes).
     *
     * @return array<string, mixed>|null
     */
    private function sellerPortalCountryApiRowToAssocArray($row): ?array
    {
        if ($row === null) {
            return null;
        }
        if (is_array($row)) {
            if (isset($row['attributes']) && is_array($row['attributes'])) {
                $base = $row['attributes'];
                if (! isset($base['id']) && isset($row['id'])) {
                    $base['id'] = $row['id'];
                }

                return $base;
            }

            return $row;
        }
        if (is_object($row)) {
            $decoded = json_decode(json_encode($row), true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /**
     * @param mixed $resp
     *
     * @return list<array<string, mixed>>
     */
    private function sellerPortalCoerceApiRowsList($resp): array
    {
        if (! is_array($resp) || isset($resp['error'])) {
            return [];
        }
        $d = $resp['data'] ?? null;
        if (! is_array($d) && isset($resp[0])) {
            $d = $resp;
        }
        if (! is_array($d)) {
            return [];
        }
        $out = [];
        foreach ($d as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (isset($row['attributes']) && is_array($row['attributes'])) {
                $base = $row['attributes'];
                if (! isset($base['id']) && isset($row['id'])) {
                    $base['id'] = $row['id'];
                }
                $out[] = $base;
            } else {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Listado de clientes de la pestaña Inicio: API v2 y, si aplica, fallback a BD local.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchAssignedCustomersForSellerPortalCustomerTabApiOnly(int $sellerId, int $companyId): array
    {
        return $this->fetchAssignedCustomersForSellerPortal($sellerId, $companyId);
    }

    /**
     * IDs de clientes asignados para el flujo Catálogo (API v2 con respeto a {@see fetchAssignedCustomersForSellerPortal} / BD local).
     *
     * @return list<int>
     */
    private function fetchAssignedCustomerIdsForSellerPortalCatalogApiOnly(int $sellerId, int $companyId): array
    {
        $ids = [];
        foreach ($this->fetchAssignedCustomersForSellerPortalCustomerTabApiOnly($sellerId, $companyId) as $c) {
            if (! is_array($c)) {
                continue;
            }
            $id = (int) ($c['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Columnas de tabla cliente sin introspección a la BD local.
     *
     * @return array<string, bool>
     */
    private function sellerPortalCustomerTableColumnVisibilityForCustomerTab(): array
    {
        return [
            'business' => true,
            'contact' => true,
            'email' => true,
            'phone' => true,
            'mobile' => true,
            'tax_id' => true,
            'erp' => true,
            'status' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sellerPortalCustomerFormFieldsData(int $sellerId, int $companyId): array
    {
        $data = [];
        $data['seller_customer_groups'] = $this->sellerPortalCustomerGroupsListFromApi($companyId);
        $gdef = (int) ($this->session->userdata(self::SELLER_DEFAULT_GROUP_ID_KEY) ?? 0);
        if ($data['seller_customer_groups'] === [] && $gdef > 0) {
            $data['seller_customer_groups'] = [['group_id' => $gdef, 'name' => '#' . (string) $gdef]];
        }
        $data['seller_create_terms'] = $this->sellerPortalTermsListFromApi($companyId);
        $defaultTermIdForForm = (int) ($this->session->userdata(self::SELLER_DEFAULT_TERM_ID_KEY) ?? 0);
        if ($data['seller_create_terms'] === [] && $defaultTermIdForForm > 0) {
            $data['seller_create_terms'] = [['term_id' => $defaultTermIdForForm, 'name' => '#' . (string) $defaultTermIdForForm]];
        }
        $sn = [];
        foreach ($this->sellerPortalSellersListRowsFromApi($companyId) as $sr) {
            if (! is_array($sr)) {
                continue;
            }
            $sid = (int) ($sr['id'] ?? 0);
            if ($sid > 0) {
                $sn[$sid] = trim((string) ($sr['name'] ?? ''));
            }
        }
        $data['seller_create_current_seller_name'] = $sn[$sellerId] ?? ('#' . (string) $sellerId);
        $data['seller_create_current_seller_id'] = $sellerId;
        $sellerList = $this->sellerPortalSellersListRowsFromApi($companyId);
        if ($sellerList === []) {
            $sellerList = [['id' => $sellerId, 'name' => $data['seller_create_current_seller_name']]];
        }
        $data['seller_create_sellers'] = $sellerList;
        $data['seller_create_countries'] = $this->sellerPortalCountriesSelectListForForms();
        $data['seller_create_default_country_id'] = $this->sellerPortalCompanyDefaultCountryId($companyId);
        $data['seller_create_default_term_id'] = $defaultTermIdForForm;
        $data['seller_create_default_group_id'] = $gdef;

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sellerPortalCustomerFormSeedById(int $sellerId, int $companyId, int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }
        foreach ($this->fetchAssignedCustomersForSellerPortalCustomerTabApiOnly($sellerId, $companyId) as $c) {
            if (! is_array($c) || (int) ($c['id'] ?? 0) !== $customerId) {
                continue;
            }
            $form = $c['form'] ?? null;

            return is_array($form) ? $form : null;
        }

        return null;
    }

    public function catalog(): void
    {
        $this->requireSellerSession();
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();

        if ($this->input->get('reset') !== null && $this->input->get('reset') !== '') {
            $this->session->unset_userdata([self::CATALOG_CUSTOMER_KEY, self::CATALOG_CART_KEY, self::CATALOG_ASSIGNABLE_IDS_KEY]);
            redirect(site_url('sellers/catalog'));

            return;
        }

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $cid = (int) $this->input->post('customer_id');
            if (! $this->sellerPortalCatalogCustomerIsAssigned($cid)) {
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_customer_invalid');
                redirect(site_url('sellers/catalog'));

                return;
            }
            $prev = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
            if ($prev !== $cid) {
                $this->session->unset_userdata(self::CATALOG_CART_KEY);
            }
            $this->session->set_userdata(self::CATALOG_CUSTOMER_KEY, $cid);
            $this->sellerPortalRedirectToCatalogoProductoAfterCustomerPick($companyId, $cid);

            return;
        }

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'catalog';
        $data['catalog_flash'] = $this->session->flashdata('seller_catalog_flash');
        $data['catalog_selected_customer_id'] = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
        $data['portal_page_title'] = (string) (($data['pageTexts']['catalog_select_customer_title'] ?? null) ?: ($data['pageTexts']['catalog_page_title'] ?? ''));
        $data['url_catalog_products'] = site_url('sellers/catalog/products');
        $data['url_catalog_reset'] = site_url('sellers/catalog?reset=1');

        $catalogMasters = $this->sellerPortalCustomerFormFieldsDataForCustomerTab($sellerId, $companyId);
        $data['seller_customer_groups'] = $catalogMasters['seller_customer_groups'] ?? [];
        $data['seller_create_terms'] = $catalogMasters['seller_create_terms'] ?? [];
        $data['seller_create_sellers'] = $catalogMasters['seller_create_sellers'] ?? [];

        $allCatalogCustomers = $this->fetchAssignedCustomersForSellerPortalCustomerTabApiOnly($sellerId, $companyId);
        $listFilters = $this->sellerPortalCustomerListFiltersFromInput();
        $data['catalog_customer_filter'] = $listFilters;
        $data['catalog_customers_all_count'] = count($allCatalogCustomers);
        $data['catalog_customers'] = $this->sellerPortalFilterCustomersForPortal($allCatalogCustomers, $listFilters);
        $this->sellerPortalCatalogRefreshAssignableIdsSession($allCatalogCustomers);

        $this->load->view('seller/catalog_customers', $data);
    }

    /**
     * Alternativa GET a POST del formulario de catálogo (misma validación de cliente asignado).
     */
    public function catalog_pick_customer(int $customerId): void
    {
        $this->requireSellerSession();
        $cid = $customerId;
        if (! $this->sellerPortalCatalogCustomerIsAssigned($cid)) {
            $this->session->set_flashdata('seller_catalog_flash', 'catalog_customer_invalid');
            redirect(site_url('sellers/catalog'));

            return;
        }
        $prev = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
        if ($prev !== $cid) {
            $this->session->unset_userdata(self::CATALOG_CART_KEY);
        }
        $this->session->set_userdata(self::CATALOG_CUSTOMER_KEY, $cid);
        $companyId = $this->getSellerCompanyId();
        $this->sellerPortalRedirectToCatalogoProductoAfterCustomerPick($companyId, $cid);
    }

    public function catalog_products(): void
    {
        $this->requireSellerSession();
        $cid = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
        if (! $this->sellerPortalCatalogCustomerIsAssigned($cid)) {
            $this->session->set_flashdata('seller_catalog_flash', 'catalog_customer_invalid');
            redirect(site_url('sellers/catalog'));

            return;
        }

        $companyId = $this->getSellerCompanyId();
        $sellerId = $this->getSellerPortalSellerId();
        $catalogImgToken = $this->sellerPortalCatalogImageToken($companyId, $cid);
        $redirectQuery = $this->sellerPortalCatalogProductsRedirectQueryFromPost();

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $allProducts = $this->fetchSellerCatalogProductsFromApi($companyId, $cid, $catalogImgToken);
            if ($allProducts === null) {
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_products_load_error');
                redirect(site_url('sellers/catalog/products') . $redirectQuery);

                return;
            }
            $map = $this->sellerPortalCatalogProductsKeyedByProId($allProducts);
            $proId = (int) $this->input->post('pro_id');
            $row = $map[$proId] ?? null;
            if ($row === null || ! $row['orderable']) {
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_product_not_available');
                redirect(site_url('sellers/catalog/products') . $redirectQuery);

                return;
            }

            $addMode = (string) $this->input->post('catalog_add_mode');
            $minStep = $this->sellerPortalCatalogMinStepQty($row);
            $cart = $this->getSellerCatalogCart();
            $key = (string) $proId;

            if ($addMode === 'quick') {
                $newQty = isset($cart[$key]) ? (float) $cart[$key]['qty'] + $minStep : $minStep;
            } else {
                $qty = (float) $this->input->post('qty');
                if ($qty <= 0) {
                    $qty = $minStep;
                }
                $newQty = isset($cart[$key]) ? (float) $cart[$key]['qty'] + $qty : $qty;
            }

            $sellInv = $this->getSellerPortalCompanySellWithInventory($companyId);
            if ($this->sellerPortalMoqApplies($row) && ! $this->sellerPortalQtyIsMoqCompliant($newQty, (float) $row['minimum_stock'])) {
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_moq_invalid');
                $this->session->set_flashdata('seller_catalog_flash_min', (string) (int) $row['minimum_stock']);
                redirect(site_url('sellers/catalog/products') . $redirectQuery);

                return;
            }
            if ($sellInv && $newQty > (float) ($row['stock'] ?? 0)) {
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_stock_exceeded');
                redirect(site_url('sellers/catalog/products') . $redirectQuery);

                return;
            }

            $cart[$key] = $this->sellerPortalCatalogCartLineFromProductRow($row, $newQty);
            $this->session->set_userdata(self::CATALOG_CART_KEY, $cart);
            redirect(site_url('sellers/catalog/products') . $redirectQuery);

            return;
        }

        $catIdGet = $this->input->get('cat_id');
        $searchGet = $this->input->get('search');
        $shortGet = $this->input->get('short');
        $categoryId = $catIdGet !== null && $catIdGet !== '' ? (int) $catIdGet : null;
        $search = $searchGet !== null && trim((string) $searchGet) !== '' ? trim((string) $searchGet) : null;
        $short = $shortGet !== null && $shortGet !== '' ? (int) $shortGet : null;

        $allProducts = $this->fetchSellerCatalogProductsFromApi($companyId, $cid, $catalogImgToken);
        $categoryFilter = $allProducts !== null ? $this->buildSellerPortalCatalogCategoryFilterList($allProducts) : [];
        $filtered = $allProducts !== null
            ? $this->filterAndSortSellerPortalCatalogProducts($allProducts, $categoryId, $search, $short)
            : null;

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'catalog';
        $data['orders_money'] = $this->portalOrdersMoneyFormat($companyId);
        $data['catalog_customer_label'] = $this->resolveCatalogCustomerLabel($cid, $sellerId, $companyId);
        $data['catalog_products'] = $filtered === null ? [] : $filtered;
        $data['catalog_products_error'] = $allProducts === null;
        $data['catalog_categories_filter'] = $categoryFilter;
        $data['catalog_filter_cat_id'] = $categoryId;
        $data['catalog_filter_search'] = $search ?? '';
        $data['catalog_filter_short'] = $short;
        $data['catalog_cart_count'] = $this->sellerCatalogCartLineCount();
        $data['catalog_flash'] = $this->session->flashdata('seller_catalog_flash');
        $data['catalog_flash_min'] = $this->session->flashdata('seller_catalog_flash_min');
        $data['catalog_show_stock'] = $this->getSellerPortalCompanyShowInventory($companyId);
        $data['catalog_sell_with_inventory'] = $this->getSellerPortalCompanySellWithInventory($companyId);
        $data['portal_page_title'] = (string) ($data['pageTexts']['catalog_products_title'] ?? $data['pageTexts']['catalog_page_title'] ?? '');
        $data['url_catalog_home'] = site_url('sellers/catalog');
        $data['url_catalog_products'] = site_url('sellers/catalog/products');
        $data['url_catalog_cart'] = site_url('sellers/catalog/cart');
        $data['url_catalog_checkout'] = site_url('sellers/catalog/checkout');
        $data['catalog_image_base'] = $this->sellerCatalogPublicImageBase();
        $data['catalog_noimg_url'] = base_url('uploads/noimg.png');
        $data['catalog_image_version'] = (string) time();
        $data['catalog_image_token_present'] = $catalogImgToken !== '';
        $data['catalog_all_products_total'] = $allProducts === null ? 0 : count($allProducts);
        $data['catalog_cart_total_amount'] = $this->sellerCatalogCartGrandTotal();
        $data['catalog_marketing_catalog_url'] = 'https://fullvendor.com';
        $this->load->view('seller/catalog_products', $data);
    }

    public function catalog_cart(): void
    {
        $this->requireSellerSession();
        $cid = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
        if (! $this->sellerPortalCatalogCustomerIsAssigned($cid)) {
            $this->session->set_flashdata('seller_catalog_flash', 'catalog_customer_invalid');
            redirect(site_url('sellers/catalog'));

            return;
        }

        $companyId = $this->getSellerCompanyId();
        $sellerId = $this->getSellerPortalSellerId();
        $catalogImgToken = $this->sellerPortalCatalogImageToken($companyId, $cid);

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $action = (string) $this->input->post('cart_action');
            $cart = $this->getSellerCatalogCart();
            if ($action === 'remove') {
                $rk = (string) $this->input->post('pro_id');
                unset($cart[$rk]);
                $this->session->set_userdata(self::CATALOG_CART_KEY, $cart);
            } elseif ($action === 'update') {
                $rk = (string) $this->input->post('pro_id');
                $nq = (float) $this->input->post('qty');
                $companyId = $this->getSellerCompanyId();
                if (isset($cart[$rk])) {
                    if ($nq <= 0) {
                        unset($cart[$rk]);
                        $this->session->set_userdata(self::CATALOG_CART_KEY, $cart);
                    } else {
                        $all = $this->fetchSellerCatalogProductsFromApi($companyId, $cid, $catalogImgToken);
                        if ($all === null) {
                            $this->session->set_flashdata('seller_catalog_cart_flash', 'catalog_products_load_error');
                        } else {
                            $map = $this->sellerPortalCatalogProductsKeyedByProId($all);
                            $pid = (int) $rk;
                            $pr = $map[$pid] ?? null;
                            if ($pr === null || ! $pr['orderable']) {
                                $this->session->set_flashdata('seller_catalog_cart_flash', 'catalog_product_not_available');
                            } elseif ($this->sellerPortalMoqApplies($pr) && ! $this->sellerPortalQtyIsMoqCompliant($nq, (float) $pr['minimum_stock'])) {
                                $this->session->set_flashdata('seller_catalog_cart_flash', 'catalog_moq_invalid');
                                $this->session->set_flashdata('seller_catalog_cart_flash_min', (string) (int) $pr['minimum_stock']);
                            } elseif ($this->getSellerPortalCompanySellWithInventory($companyId) && $nq > (float) ($pr['stock'] ?? 0)) {
                                $this->session->set_flashdata('seller_catalog_cart_flash', 'catalog_stock_exceeded');
                            } else {
                                $cart[$rk]['qty'] = $nq;
                                $cart[$rk]['minimum_stock'] = (float) ($pr['minimum_stock'] ?? 0);
                                $cart[$rk]['notify_minimum_stock'] = ! empty($pr['notify_minimum_stock']);
                                $cart[$rk]['stock'] = isset($pr['stock']) ? (float) $pr['stock'] : null;
                                $this->session->set_userdata(self::CATALOG_CART_KEY, $cart);
                            }
                        }
                    }
                }
            } elseif ($action === 'clear') {
                $this->session->unset_userdata(self::CATALOG_CART_KEY);
            }
            redirect(site_url('sellers/catalog/cart'));

            return;
        }

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'catalog';
        $data['orders_money'] = $this->portalOrdersMoneyFormat($companyId);
        $data['catalog_customer_label'] = $this->resolveCatalogCustomerLabel($cid, $sellerId, $companyId);
        $data['catalog_cart_lines'] = array_values($this->getSellerCatalogCart());
        $data['catalog_cart_total'] = $this->sellerCatalogCartGrandTotal();
        $data['catalog_cart_flash'] = $this->session->flashdata('seller_catalog_cart_flash');
        $data['catalog_cart_flash_min'] = $this->session->flashdata('seller_catalog_cart_flash_min');
        $data['portal_page_title'] = (string) ($data['pageTexts']['catalog_cart_title'] ?? $data['pageTexts']['catalog_page_title'] ?? '');
        $data['url_catalog_home'] = site_url('sellers/catalog');
        $data['url_catalog_products'] = site_url('sellers/catalog/products');
        $data['url_catalog_cart'] = site_url('sellers/catalog/cart');
        $data['url_catalog_checkout'] = site_url('sellers/catalog/checkout');
        $this->load->view('seller/catalog_cart', $data);
    }

    public function catalog_checkout(): void
    {
        $this->requireSellerSession();
        $cid = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
        if (! $this->sellerPortalCatalogCustomerIsAssigned($cid)) {
            redirect(site_url('sellers/catalog'));

            return;
        }

        $cart = $this->getSellerCatalogCart();
        if ($cart === []) {
            redirect(site_url('sellers/catalog/cart'));

            return;
        }

        $companyId = $this->getSellerCompanyId();
        $sellerId = $this->getSellerPortalSellerId();

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $biz = $this->validateSellerPortalCartBusinessRules($cart, $companyId);
            if ($biz !== null) {
                $this->session->set_flashdata('seller_catalog_checkout_error', $biz);
                redirect(site_url('sellers/catalog/checkout'));

                return;
            }
            $result = $this->postSellerPortalOrderFromApi($cid, $sellerId, array_values($cart));
            if ($result['ok']) {
                $this->session->unset_userdata(self::CATALOG_CART_KEY);
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_order_success');
                redirect(site_url('sellers/catalog/products'));

                return;
            }
            $this->session->set_flashdata('seller_catalog_checkout_error', $result['code'] ?? 'catalog_order_error_generic');
            redirect(site_url('sellers/catalog/checkout'));

            return;
        }

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'catalog';
        $data['orders_money'] = $this->portalOrdersMoneyFormat($companyId);
        $data['catalog_customer_label'] = $this->resolveCatalogCustomerLabel($cid, $sellerId, $companyId);
        $data['catalog_cart_lines'] = array_values($cart);
        $data['catalog_cart_total'] = $this->sellerCatalogCartGrandTotal();
        $data['catalog_checkout_error'] = $this->session->flashdata('seller_catalog_checkout_error');
        $data['catalog_checkout_error_min'] = $this->session->flashdata('seller_catalog_checkout_error_min');
        $data['portal_page_title'] = (string) ($data['pageTexts']['catalog_checkout_title'] ?? $data['pageTexts']['catalog_page_title'] ?? '');
        $data['url_catalog_home'] = site_url('sellers/catalog');
        $data['url_catalog_products'] = site_url('sellers/catalog/products');
        $data['url_catalog_cart'] = site_url('sellers/catalog/cart');
        $data['url_catalog_checkout'] = site_url('sellers/catalog/checkout');
        $this->load->view('seller/catalog_checkout', $data);
    }

    public function catalog_image(string $imageRef = ''): void
    {
        $this->requireSellerSession();
        $ref = trim($imageRef);
        // Mismo patrón que company/Image::run: el MVC pide la imagen a la API con Bearer (no el navegador).
        if ($ref === '' || $ref === 'index') {
            $response = $this->apiRawRequest('images/no-image', []);
        } elseif (strcasecmp($ref, 'no-image') === 0) {
            $response = $this->apiRawRequest('images/no-image', []);
        } elseif (preg_match('/^[A-Za-z0-9._-]+\.(?:webp|png|jpg|jpeg|gif)$/i', $ref) === 1) {
            $response = $this->apiRawRequest('images/' . $ref, []);
        } else {
            show_404();

            return;
        }
        if (is_array($response)) {
            log_message('error', 'Seller_portal catalog_image: API error ' . json_encode($response));

            show_404();

            return;
        }
        $contentType = (string) ($response->getHeaderLine('Content-Type') ?: 'image/webp');
        $body = $response->getBody()->getContents();
        $this->output
            ->set_status_header(200)
            ->set_content_type($contentType)
            ->set_output($body);
    }

    public function orders(): void
    {
        $this->requireSellerSession();
        $sellerId = $this->getSellerPortalSellerId();
        $langId = $this->getPortalLanguageId();
        $companyId = $this->getSellerCompanyId();

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'orders';
        $data['orders_money'] = $this->portalOrdersMoneyFormat($companyId);

        $filters = $this->readSellerPortalOrdersListFilters();
        $allOrders = $this->fetchSellerPortalOrdersFromApi($sellerId, $langId);
        $data['orders_list_all_count'] = count($allOrders);
        $filtered = $this->applySellerPortalOrdersListFilters($allOrders, $filters);
        $total = count($filtered);
        $perPage = 30;
        $page = (int) ($this->input->get('page') ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $maxPage = max(1, (int) ceil($total / $perPage));
        if ($page > $maxPage) {
            $page = $maxPage;
        }
        $offset = ($page - 1) * $perPage;
        $data['orders'] = array_slice($filtered, $offset, $perPage);
        $data['orders_list_total'] = $total;
        $data['orders_list_page'] = $page;
        $data['orders_list_per_page'] = $perPage;
        $data['orders_list_filters'] = $filters;
        $data['orders_list_status_options'] = $this->sellerPortalOrdersStatusFilterOptions($allOrders);
        $sellerOpts = [['id' => 0, 'name' => '']];
        $sellerListOrders = $this->sellerPortalSellersListRowsFromApi($companyId);
        if ($sellerListOrders === []) {
            $sellerListOrders = [['id' => $sellerId, 'name' => '#' . (string) $sellerId]];
        }
        foreach ($sellerListOrders as $sr) {
            if (isset($sr['id'], $sr['name']) && (int) $sr['id'] > 0) {
                $sellerOpts[] = ['id' => (int) $sr['id'], 'name' => (string) $sr['name']];
            }
        }
        $data['orders_list_seller_options'] = $sellerOpts;
        $pt = is_array($data['pageTexts'] ?? null) ? $data['pageTexts'] : [];
        $data['portal_page_title'] = (string) (($pt['orders_page_title'] ?? null) ?: ($pt['menu_orders'] ?? ''));

        $this->load->view('seller/orders', $data);
    }

    /**
     * Detalle de una orden (API order/{id}/full) con líneas y productos; solo si el seller puede verla.
     */
    public function order_view($orderId = null): void
    {
        $this->requireSellerSession();
        $oid = (int) ($orderId ?? 0);
        if ($oid <= 0) {
            redirect(site_url('sellers/orders'));

            return;
        }

        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();
        $langId = $this->getPortalLanguageId();

        $this->load->clear_vars();
        $data = $this->sellerPortalShellData();
        $data['active_portal_tab'] = 'orders';
        $ptOd = is_array($data['pageTexts'] ?? null) ? $data['pageTexts'] : [];
        $data['portal_page_title'] = (string) (($ptOd['order_detail_title'] ?? null) ?: ($ptOd['orders_page_title'] ?? $ptOd['menu_orders'] ?? ''));
        $data['orders_money'] = $this->portalOrdersMoneyFormat($companyId);
        $data['order_detail_error'] = false;
        $data['order_detail'] = null;
        $data['order_lines'] = [];

        $payload = $this->fetchSellerOrderFullFromApi($oid);
        if ($payload === null || ! $this->sellerPortalCanAccessOrderPayload($payload, $sellerId, $companyId)) {
            $data['order_detail_error'] = true;
            $this->load->view('seller/order_detail', $data);

            return;
        }

        $data['order_detail'] = $this->mapSellerPortalOrderSummary($payload, $langId);
        $data['order_lines'] = $this->buildSellerPortalOrderDetailLines($payload, $companyId);
        $this->load->view('seller/order_detail', $data);
    }

    public function logout(): void
    {
        $this->removeUserData(self::SELLER_COMPANY_KEY);
        $this->removeUserData(self::SELLER_ID_KEY);
        $this->removeUserData(self::SELLER_LANG_KEY);
        $this->removeUserData('seller_portal_user_id');
        $this->removeUserData('seller_portal_lang_id');
        $this->removeUserData(self::SELLER_COMPANY_DISPLAY_NAME_KEY);
        $this->removeUserData(self::SELLER_ALL_CUSTOMERS_FLAG_KEY);
        $this->removeUserData(self::SELLER_DEFAULT_TERM_ID_KEY);
        $this->removeUserData(self::SELLER_DEFAULT_GROUP_ID_KEY);
        $this->removeUserData('role');
        $this->removeUserData('compId');
        $this->removeUserData('token');
        $this->removeUserData(self::CATALOG_CUSTOMER_KEY);
        $this->removeUserData(self::CATALOG_CART_KEY);
        $this->removeUserData(self::CATALOG_ASSIGNABLE_IDS_KEY);
        $this->setUserAPIToken('');
        redirect(site_url('sellers/login'));
    }

    public function authenticate(): void
    {
        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') !== 'POST') {
            redirect(site_url('sellers/login'));

            return;
        }

        $this->portalLoginJson();
    }

    private function portalLoginJson(): void
    {
        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        if ($email === '' || $password === '') {
            $this->respondJson(['success' => false, 'code' => 'seller_login_validation_required'], 422);

            return;
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->respondJson(['success' => false, 'code' => 'seller_login_validation_email'], 422);

            return;
        }

        $apiUrl = rtrim($this->getApiV2EntryPoint(), '/') . '/v2/seller/login';

        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 15]);
            $response = $client->request('POST', $apiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'company_id' => $payload['company_id'] ?? null,
                    'seller_id' => $payload['seller_id'] ?? null,
                ],
            ]);
            $status = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            if (! is_array($body)) {
                $this->respondJson(['success' => false, 'code' => 'seller_login_network_error'], 502);

                return;
            }

            if ($status === 429) {
                $this->respondJson(['success' => false, 'code' => 'seller_login_too_many_attempts'], 429);

                return;
            }

            if ($status >= 500 && empty($body['code'])) {
                $this->respondJson(['success' => false, 'code' => 'seller_login_server_error'], 502);

                return;
            }

            if (! empty($body['success']) && ! empty($body['data'])) {
                $d = $body['data'];
                $isSeller = ! empty($d['is_seller']) || (($d['user_type'] ?? '') === 'seller');
                if (! $isSeller) {
                    $this->respondJson(['success' => false, 'code' => 'seller_login_not_seller'], 403);

                    return;
                }
                $this->establishSellerSession(
                    (int) $d['company_id'],
                    (int) $d['seller_id'],
                    (int) ($d['language_id'] ?? 1),
                    [
                        'company_name' => (string) ($d['company_name'] ?? ''),
                        'all_customers' => (int) ($d['all_customers'] ?? 0),
                        'default_term_id' => (int) ($d['default_term_id'] ?? 0),
                        'default_customer_group_id' => (int) ($d['default_customer_group_id'] ?? 0),
                    ]
                );
                $this->syncPortalSanctumToken($email, $password);
                $uid = isset($d['user_id']) ? (int) $d['user_id'] : 0;
                if ($uid > 0) {
                    $this->setUserData('seller_portal_user_id', $uid);
                }
                $this->respondJson([
                    'success' => true,
                    'authenticated' => true,
                    'redirect' => $this->portalUriPath('sellers/customer'),
                ]);

                return;
            }

            $this->respondJson($body, $status >= 400 && $status < 600 ? $status : 400);
        } catch (\Throwable $e) {
            log_message('error', 'Seller_portal portalLoginJson: ' . $e->getMessage());
            $this->respondJson(['success' => false, 'code' => 'seller_login_network_error'], 502);
        }
    }

    private function portalUriPath(string $segments): string
    {
        $path = parse_url(site_url($segments), PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return '/' . ltrim($segments, '/');
        }

        return $path;
    }

    private function syncPortalSanctumToken(string $email, string $password): void
    {
        $this->setUserAPIToken('');
        $this->removeUserData('token');

        $url = rtrim($this->getApiV2EntryPoint(), '/') . '/v2/user/token';
        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 15]);
            $response = $client->request('GET', $url, [
                'query' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);
            if ($response->getStatusCode() !== 200) {
                return;
            }
            $token = trim((string) $response->getBody()->getContents());
            if ($token !== '') {
                $this->setUserAPIToken($token);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Seller_portal syncPortalSanctumToken: ' . $e->getMessage());
        }
    }

    /**
     * @param array{company_name?: string, all_customers?: int, default_term_id?: int, default_customer_group_id?: int} $portalExtras
     */
    private function establishSellerSession(int $companyId, int $sellerId, int $languageId, array $portalExtras = []): void
    {
        $this->removeUserData(self::SELLER_COMPANY_KEY);
        $this->removeUserData(self::SELLER_ID_KEY);
        $this->removeUserData(self::SELLER_LANG_KEY);
        $this->removeUserData(self::SELLER_COMPANY_DISPLAY_NAME_KEY);
        $this->removeUserData(self::SELLER_ALL_CUSTOMERS_FLAG_KEY);
        $this->removeUserData(self::SELLER_DEFAULT_TERM_ID_KEY);
        $this->removeUserData(self::SELLER_DEFAULT_GROUP_ID_KEY);

        $this->flash('loader', true);
        $this->setUserData(self::SELLER_COMPANY_KEY, $companyId);
        $this->setUserData(self::SELLER_ID_KEY, $sellerId);
        $this->setUserData(self::SELLER_LANG_KEY, $languageId);
        $this->setApplication('marketplace');
        $this->setUserData('role', 'seller');
        $this->setUserData('compId', $companyId);

        $this->setUserData(self::SELLER_COMPANY_DISPLAY_NAME_KEY, trim((string) ($portalExtras['company_name'] ?? '')));
        $this->setUserData(self::SELLER_ALL_CUSTOMERS_FLAG_KEY, (int) ($portalExtras['all_customers'] ?? 0));
        $this->setUserData(self::SELLER_DEFAULT_TERM_ID_KEY, (int) ($portalExtras['default_term_id'] ?? 0));
        $this->setUserData(self::SELLER_DEFAULT_GROUP_ID_KEY, (int) ($portalExtras['default_customer_group_id'] ?? 0));
    }

    private function requireSellerSession(): void
    {
        if (! $this->session->has_userdata(self::SELLER_COMPANY_KEY) || ! $this->session->has_userdata(self::SELLER_ID_KEY)) {
            redirect(site_url('sellers/login'));
        }
    }

    private function getSellerCompanyId(): int
    {
        return (int) ($this->session->userdata(self::SELLER_COMPANY_KEY) ?? 0);
    }

    private function getSellerPortalSellerId(): int
    {
        return (int) ($this->session->userdata(self::SELLER_ID_KEY) ?? 0);
    }

    private function getPortalCompanyDisplayName(int $companyId): string
    {
        $fromSession = trim((string) ($this->session->userdata(self::SELLER_COMPANY_DISPLAY_NAME_KEY) ?? ''));
        if ($fromSession !== '') {
            return $fromSession;
        }
        if ($companyId > 0) {
            $ctx = $this->sellerPortalContextPayload();
            $nm = trim((string) ($ctx['company_name'] ?? ''));
            if ($nm !== '') {
                return $nm;
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function sellerPortalShellData(): array
    {
        $companyId = $this->getSellerCompanyId();
        $data = $this->basePortalData();
        $data['url_logout'] = site_url('sellers/logout');
        $data['url_home'] = site_url('sellers/customer');
        $data['url_catalog'] = site_url('sellers/catalog');
        $data['url_orders'] = site_url('sellers/orders');
        $data['portal_company_name'] = $this->getPortalCompanyDisplayName($companyId);

        return $data;
    }

    /**
     * @param list<int> $restrictCustomerIds Si no está vacío y el tamaño es acotado, se envía a la API para filtrar en BD.
     *
     * @return list<array<string, mixed>>
     */
    /** @var array<string, mixed>|null */
    private $sellerPortalContextCache = null;

    /**
     * @return array<string, mixed>
     */
    private function sellerPortalContextPayload(): array
    {
        if (is_array($this->sellerPortalContextCache)) {
            return $this->sellerPortalContextCache;
        }
        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/context');
        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            $this->sellerPortalContextCache = $json['data'];

            return $this->sellerPortalContextCache;
        }

        return [];
    }

    private function fetchSellerOrdersRawFromApi(int $sellerId = 0, array $restrictCustomerIds = []): array
    {
        $query = [
            'seller_id' => $sellerId > 0 ? $sellerId : null,
            'include_order_details' => '0',
        ];
        $maxCustomersInQuery = 400;
        if ($restrictCustomerIds !== [] && count($restrictCustomerIds) <= $maxCustomersInQuery) {
            $ids = [];
            foreach ($restrictCustomerIds as $cid) {
                $cid = (int) $cid;
                if ($cid > 0) {
                    $ids[] = $cid;
                }
            }
            $ids = array_values(array_unique($ids));
            if ($ids !== [] && count($ids) <= $maxCustomersInQuery) {
                $query['customer_ids'] = $ids;
            }
        }

        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/orders', array_filter($query, static function ($v) {
            return $v !== null && $v !== '';
        }));
        if (! is_array($json)) {
            return [];
        }
        $rows = $json['data'] ?? null;
        if (is_array($rows) && isset($rows['data']) && is_array($rows['data'])) {
            $rows = $rows['data'];
        }

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return list<object>
     */
    private function fetchSellerPortalOrdersFromApi(int $sellerId, int $langId): array
    {
        if ($sellerId <= 0) {
            return [];
        }

        $companyId = $this->getSellerCompanyId();
        $assignedCustomerIds = $this->fetchAssignedCustomerIdsForSellerPortalCatalogApiOnly($sellerId, $companyId);
        if ($assignedCustomerIds === []) {
            return [];
        }
        $assignedCustomerLookup = array_fill_keys($assignedCustomerIds, true);

        $out = [];
        foreach ($this->fetchSellerOrdersRawFromApi($sellerId, $assignedCustomerIds) as $row) {
            $row = $this->normalizeSellerApiOrderRow($row);
            if ($row === null) {
                continue;
            }
            $customerId = $this->extractOrderCustomerId($row);
            if ($customerId <= 0 || ! isset($assignedCustomerLookup[$customerId])) {
                continue;
            }
            $rid = (int) ($row['seller_id'] ?? 0);
            if ($rid === 0 && isset($row['seller']) && is_array($row['seller'])) {
                $rid = (int) ($row['seller']['seller_id'] ?? $row['seller']['id'] ?? 0);
            }
            if ($rid > 0 && $rid !== $sellerId) {
                continue;
            }

            $out[] = $this->mapSellerPortalOrderSummary($row, $langId);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchSellerOrderFullFromApi(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/orders/' . rawurlencode((string) $orderId));
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            return null;
        }
        $row = $json['data'];
        if (isset($row['data']) && is_array($row['data'])) {
            $row = $row['data'];
        }

        $normalized = $this->normalizeSellerApiOrderRow($row);

        return $normalized ?? $row;
    }

    /**
     * @param array<string, mixed> $order
     */
    private function sellerPortalCanAccessOrderPayload(array $order, int $sellerId, int $companyId): bool
    {
        if ($sellerId <= 0) {
            return false;
        }
        if ($companyId > 0 && (int) ($order['company_id'] ?? 0) !== $companyId) {
            return false;
        }
        $rid = (int) ($order['seller_id'] ?? 0);
        if ($rid === 0 && isset($order['seller']) && is_array($order['seller'])) {
            $rid = (int) ($order['seller']['seller_id'] ?? $order['seller']['id'] ?? 0);
        }
        if ($rid > 0 && $rid === $sellerId) {
            return true;
        }

        $assigned = $this->fetchAssignedCustomerIdsForSellerPortalCatalogApiOnly($sellerId, $companyId);
        if ($assigned === []) {
            return false;
        }
        $lookup = array_fill_keys($assigned, true);
        $customerId = $this->extractOrderCustomerId($order);
        if ($customerId <= 0 || ! isset($lookup[$customerId])) {
            return false;
        }
        if ($rid > 0 && $rid !== $sellerId) {
            return false;
        }

        return true;
    }

    /**
     * @return array{business: string, contact: string, email: string, phone: string, cell: string, address: string}
     */
    private function extractSellerPortalCustomerGridFields(array $row): array
    {
        $empty = ['business' => '', 'contact' => '', 'email' => '', 'phone' => '', 'cell' => '', 'address' => ''];
        $cust = $row['customer'] ?? null;
        if (is_array($cust)) {
            $business = trim((string) ($cust['business_name'] ?? $cust['company_name'] ?? ''));
            $contact = trim((string) ($cust['name'] ?? ''));
            if ($contact === '') {
                $fn = trim((string) ($cust['first_name'] ?? ''));
                $ln = trim((string) ($cust['last_name'] ?? ''));
                $contact = trim($fn . ' ' . $ln);
            }
            $email = trim((string) ($cust['email'] ?? ''));
            $phone = trim((string) ($cust['phone'] ?? $cust['telephone'] ?? $cust['tel'] ?? ''));
            $cell = trim((string) ($cust['cell_phone'] ?? $cust['mobile'] ?? $cust['cell'] ?? ''));
            $address = '';
            $addrs = $cust['commercialAddresses'] ?? $cust['commercial_addresses'] ?? null;
            if (is_array($addrs) && isset($addrs[0]) && is_array($addrs[0])) {
                $a = $addrs[0];
                $parts = array_filter([
                    trim((string) ($a['address'] ?? $a['street'] ?? '')),
                    trim((string) ($a['city'] ?? '')),
                    trim((string) ($a['state'] ?? '')),
                    trim((string) ($a['zip_code'] ?? $a['zip'] ?? '')),
                    trim((string) ($a['country'] ?? '')),
                ], static function ($p) {
                    return $p !== '';
                });
                $address = implode(', ', $parts);
            }
            if ($address === '') {
                $address = trim((string) ($cust['address'] ?? $cust['full_address'] ?? ''));
            }

            return [
                'business' => $business,
                'contact' => $contact,
                'email' => $email,
                'phone' => $phone,
                'cell' => $cell,
                'address' => $address,
            ];
        }

        $cid = (int) ($row['customer_id'] ?? $row['customerId'] ?? 0);

        return $this->sellerPortalCustomerGridFromApi($cid);
    }

    /**
     * Datos de cliente para filas de pedido vía GET customer/{id} (sin BD local).
     *
     * @return array{business: string, contact: string, email: string, phone: string, cell: string, address: string}
     */
    private function sellerPortalCustomerGridFromApi(int $customerId): array
    {
        $empty = ['business' => '', 'contact' => '', 'email' => '', 'phone' => '', 'cell' => '', 'address' => ''];
        if ($customerId <= 0) {
            return $empty;
        }
        if (isset($this->sellerPortalCustomerGridApiCache[$customerId])) {
            return $this->sellerPortalCustomerGridApiCache[$customerId];
        }
        try {
            $row = $this->sellerPortalCustomerRowFromApi($customerId);
            if (! is_array($row) || $row === []) {
                $this->sellerPortalCustomerGridApiCache[$customerId] = $empty;

                return $empty;
            }
            $business = trim((string) ($row['business_name'] ?? $row['company_name'] ?? ''));
            $contact = trim((string) ($row['name'] ?? ''));
            if ($contact === '') {
                $fn = trim((string) ($row['first_name'] ?? ''));
                $ln = trim((string) ($row['last_name'] ?? ''));
                $contact = trim($fn . ' ' . $ln);
            }
            $email = trim((string) ($row['email'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? $row['phone_number'] ?? ''));
            $cell = trim((string) ($row['cell_phone'] ?? $row['mobile'] ?? ''));
            $addrParts = array_filter([
                trim((string) ($row['commercial_address'] ?? '')),
                trim((string) ($row['commercial_state'] ?? '')),
                trim((string) ($row['commercial_city'] ?? '')),
                trim((string) ($row['commercial_zone'] ?? '')),
                trim((string) ($row['commercial_zip_code'] ?? '')),
                trim((string) ($row['commercial_country'] ?? '')),
            ], static function ($p) {
                return $p !== '';
            });
            $address = implode(', ', $addrParts);
            if ($address === '') {
                $address = trim((string) ($row['address'] ?? $row['full_address'] ?? ''));
            }
            $out = [
                'business' => $business,
                'contact' => $contact,
                'email' => $email,
                'phone' => $phone,
                'cell' => $cell,
                'address' => $address,
            ];
            $this->sellerPortalCustomerGridApiCache[$customerId] = $out;

            return $out;
        } catch (\Throwable $e) {
            log_message('debug', 'Seller_portal sellerPortalCustomerGridFromApi: ' . $e->getMessage());
            $this->sellerPortalCustomerGridApiCache[$customerId] = $empty;

            return $empty;
        }
    }

    private function extractSellerPortalSellerLabel(array $row): string
    {
        $s = $row['seller'] ?? null;
        if (is_array($s)) {
            $u = $s['user'] ?? null;
            if (is_array($u)) {
                $fn = trim((string) ($u['first_name'] ?? ''));
                $ln = trim((string) ($u['last_name'] ?? ''));
                $full = trim($fn . ' ' . $ln);
                if ($full !== '') {
                    return $full;
                }
                $n = trim((string) ($u['name'] ?? ''));
                if ($n !== '') {
                    return $n;
                }
                $em = trim((string) ($u['email'] ?? ''));
                if ($em !== '') {
                    return $em;
                }
            }
            foreach (['seller_name', 'name', 'display_name'] as $k) {
                if (! empty($s[$k])) {
                    return trim((string) $s[$k]);
                }
            }
        }

        return '';
    }

    /**
     * Fecha de actualización para listado (misma lógica que creación: parte fecha).
     */
    private function portalOrderRowUpdatedDate(array $row): string
    {
        $raw = $row['updated'] ?? $row['updated_at'] ?? $row['updatedAt'] ?? null;
        if (is_array($raw)) {
            $raw = $raw['date'] ?? $raw['formatted'] ?? null;
        }
        $s = trim((string) ($raw ?? ''));
        if ($s === '') {
            return '';
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $s, $m)) {
            return $m[1];
        }
        $ts = strtotime($s);

        return $ts !== false ? date('Y-m-d', $ts) : '';
    }

    private function extractSellerPortalOrderSource(array $row): string
    {
        return strtolower(trim((string) ($row['source'] ?? $row['order_source'] ?? $row['origin'] ?? '')));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return object{order_id: mixed, order_number: ?string, created_date: string, updated_date: string, customer_name: string, customer_business: string, customer_contact: string, customer_email: string, customer_phone: string, customer_cell: string, customer_address: string, seller_label: string, seller_id: int, order_source: string, portal_vendor: bool, order_status: int, status_label: string, status_badge_class: string, status_dot_color: string, total_amount: float, subtotal_amount: float, discount_amount: float, discount_percent_label: float, approved: bool, warehouse_user_label: string, comments_preview: string, email_sent: bool}
     */
    private function mapSellerPortalOrderSummary(array $payload, int $langId): object
    {
        $grid = $this->extractSellerPortalCustomerGridFields($payload);
        $customerName = $this->extractSellerPortalCustomerDisplayName($payload);
        if ($customerName === '' && $grid['business'] !== '') {
            $customerName = $grid['business'];
        }
        if ($customerName === '' && $grid['contact'] !== '') {
            $customerName = $grid['contact'];
        }
        $so = $payload['status_order'] ?? $payload['statusOrder'] ?? null;
        $statusCod = null;
        $statusLabel = '';
        if (is_array($so)) {
            $codRaw = $so['cod_status'] ?? $so['codStatus'] ?? null;
            if (is_numeric($codRaw)) {
                $statusCod = (int) $codRaw;
            }
            if ($langId === 2) {
                $statusLabel = (string) ($so['name_status_spanish'] ?? $so['name_status_english'] ?? '');
            } else {
                $statusLabel = (string) ($so['name_status_english'] ?? $so['name_status_spanish'] ?? '');
            }
        }
        if ($statusCod === null && isset($payload['order_status']) && is_numeric($payload['order_status'])) {
            $statusCod = (int) $payload['order_status'];
        }
        if ($statusLabel === '') {
            $statusLabel = trim((string) ($payload['order_status_label'] ?? $payload['status_name'] ?? ''));
        }
        $statusBadgeClass = $this->portalOrderStatusBadgeClass($statusCod);
        $statusDotColor = '';
        if (is_array($so)) {
            $statusDotColor = $this->sellerPortalSanitizeStatusColor(isset($so['color']) ? (string) $so['color'] : null);
        }
        $total = $this->extractSellerPortalOrderTotal($payload);
        [$subtotalAmt, $discountAmt, $discountPctLabel] = $this->extractSellerPortalOrderSubtotalAndDiscount($payload, $total);
        $oid = $payload['order_id'] ?? $payload['orderId'] ?? $payload['id'] ?? null;
        $orderNumber = $payload['order_number'] ?? $payload['orderNumber'] ?? $payload['order_no'] ?? $payload['orderNo'] ?? null;
        if ($orderNumber === null || trim((string) $orderNumber) === '') {
            $orderNumber = $oid !== null && $oid !== '' ? (string) $oid : null;
        }
        if (($orderNumber === null || trim((string) $orderNumber) === '') && ! empty($payload['UUID'])) {
            $orderNumber = (string) $payload['UUID'];
        }
        [$createdDate] = $this->portalOrderRowCreatedParts($payload);
        $updatedDate = $this->portalOrderRowUpdatedDate($payload);
        $sellerLabel = $this->extractSellerPortalSellerLabel($payload);
        $sellerIdRow = (int) ($payload['seller_id'] ?? 0);
        $orderSource = $this->extractSellerPortalOrderSource($payload);
        $portalVendor = ! empty($payload['portal_vendor']) || ! empty($payload['portalVendor']);
        $orderStatus = isset($payload['order_status']) ? (int) $payload['order_status'] : (isset($payload['orderStatus']) ? (int) $payload['orderStatus'] : 0);

        $warehouseUserLabel = trim((string) ($payload['warehouse_user_display'] ?? ''));
        if ($warehouseUserLabel === '') {
            $wh = $payload['warehouse'] ?? null;
            if (is_array($wh)) {
                $warehouseUserLabel = trim((string) ($wh['name'] ?? ''));
            }
        }
        $commentsPreview = $this->sellerPortalOrderCommentsPreview($payload);
        $approvedBy = trim((string) ($payload['approved_by'] ?? ''));
        $approved = $approvedBy !== '';
        $sendEmailRaw = $payload['send_email_order'] ?? null;
        $emailSent = false;
        if (is_string($sendEmailRaw)) {
            $tse = trim($sendEmailRaw);
            $emailSent = $tse !== '' && strcasecmp($tse, '0000-00-00 00:00:00') !== 0;
        } elseif ($sendEmailRaw !== null && $sendEmailRaw !== '') {
            $emailSent = true;
        }

        return (object) [
            'order_id' => $oid,
            'order_number' => $orderNumber,
            'created_date' => $createdDate,
            'updated_date' => $updatedDate,
            'customer_name' => $customerName,
            'customer_business' => $grid['business'],
            'customer_contact' => $grid['contact'],
            'customer_email' => $grid['email'],
            'customer_phone' => $grid['phone'],
            'customer_cell' => $grid['cell'],
            'customer_address' => $grid['address'],
            'seller_label' => $sellerLabel,
            'seller_id' => $sellerIdRow,
            'order_source' => $orderSource,
            'portal_vendor' => $portalVendor,
            'order_status' => $orderStatus,
            'status_label' => $statusLabel,
            'status_badge_class' => $statusBadgeClass,
            'status_dot_color' => $statusDotColor,
            'total_amount' => $total,
            'subtotal_amount' => $subtotalAmt,
            'discount_amount' => $discountAmt,
            'discount_percent_label' => $discountPctLabel,
            'approved' => $approved,
            'warehouse_user_label' => $warehouseUserLabel,
            'comments_preview' => $commentsPreview,
            'email_sent' => $emailSent,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sellerPortalOrderCommentsPreview(array $payload): string
    {
        $parts = array_filter([
            trim((string) ($payload['order_comments'] ?? '')),
            trim((string) ($payload['internal_notes'] ?? '')),
            trim((string) ($payload['delivery_notes'] ?? '')),
        ], static function ($p) {
            return $p !== '';
        });
        $full = implode(' ', $parts);
        if ($full === '') {
            return '';
        }
        $max = 160;
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($full, 'UTF-8') <= $max) {
                return $full;
            }

            return rtrim(mb_substr($full, 0, $max - 1, 'UTF-8')) . '…';
        }
        if (strlen($full) <= $max) {
            return $full;
        }

        return rtrim(substr($full, 0, $max - 1)) . '…';
    }

    /**
     * @return array{date_from: string, date_to: string, filter_seller: string, filter_origin: string, filter_status: string, q: string}
     */
    private function readSellerPortalOrdersListFilters(): array
    {
        return [
            'date_from' => trim((string) $this->input->get('date_from', true)),
            'date_to' => trim((string) $this->input->get('date_to', true)),
            'filter_seller' => trim((string) $this->input->get('filter_seller', true)),
            'filter_origin' => strtolower(trim((string) $this->input->get('filter_origin', true))),
            'filter_status' => trim((string) $this->input->get('filter_status', true)),
            'q' => trim((string) $this->input->get('q', true)),
        ];
    }

    /**
     * @param list<object> $orders
     *
     * @return list<object>
     */
    private function applySellerPortalOrdersListFilters(array $orders, array $filters): array
    {
        $out = [];
        $df = (string) ($filters['date_from'] ?? '');
        $dt = (string) ($filters['date_to'] ?? '');
        $fs = (string) ($filters['filter_seller'] ?? '');
        $fo = (string) ($filters['filter_origin'] ?? '');
        $fst = (string) ($filters['filter_status'] ?? '');
        $q = (string) ($filters['q'] ?? '');
        $qNorm = $q !== '' ? strtolower($q) : '';

        foreach ($orders as $o) {
            if (! is_object($o)) {
                continue;
            }
            $created = trim((string) ($o->created_date ?? ''));
            if ($df !== '' && ($created === '' || strcmp($created, $df) < 0)) {
                continue;
            }
            if ($dt !== '' && ($created === '' || strcmp($created, $dt) > 0)) {
                continue;
            }
            if ($fs !== '' && (int) $fs > 0 && (int) ($o->seller_id ?? 0) !== (int) $fs) {
                continue;
            }
            if ($fo !== '' && strtolower(trim((string) ($o->order_source ?? ''))) !== $fo) {
                continue;
            }
            if ($fst !== '' && (string) (int) ($o->order_status ?? 0) !== (string) (int) $fst) {
                continue;
            }
            if ($qNorm !== '') {
                $hay = strtolower(implode(' ', array_filter([
                    (string) ($o->order_number ?? ''),
                    (string) ($o->customer_name ?? ''),
                    (string) ($o->customer_business ?? ''),
                    (string) ($o->customer_contact ?? ''),
                    (string) ($o->customer_email ?? ''),
                    (string) ($o->customer_phone ?? ''),
                    (string) ($o->customer_cell ?? ''),
                    (string) ($o->customer_address ?? ''),
                    (string) ($o->seller_label ?? ''),
                    (string) ($o->warehouse_user_label ?? ''),
                    (string) ($o->comments_preview ?? ''),
                    (string) ($o->status_label ?? ''),
                ], static function ($p) {
                    return $p !== '';
                })));
                if ($hay === '' || strpos($hay, $qNorm) === false) {
                    continue;
                }
            }
            $out[] = $o;
        }

        return $out;
    }

    /**
     * @param list<object> $orders
     *
     * @return list<array{value: string, label: string}>
     */
    private function sellerPortalOrdersStatusFilterOptions(array $orders): array
    {
        $map = [];
        foreach ($orders as $o) {
            if (! is_object($o)) {
                continue;
            }
            $cod = (int) ($o->order_status ?? 0);
            $key = (string) $cod;
            if (isset($map[$key])) {
                continue;
            }
            $lab = trim((string) ($o->status_label ?? ''));
            $map[$key] = $lab !== '' ? $lab : ('#' . $key);
        }
        ksort($map, SORT_NATURAL);
        $opts = [['value' => '', 'label' => '']];
        foreach ($map as $k => $lab) {
            $opts[] = ['value' => $k, 'label' => $lab];
        }

        return $opts;
    }

    /**
     * URL de miniatura para una fila de producto devuelta por la API (default_image / images / URLs absolutas).
     * La ruta API v2 es images/{uuid}.{ext}: el segmento debe ser el UUID del registro images, no necesariamente name.
     *
     * @param array<string, mixed> $prod
     */
    private function sellerPortalOrderLineProductImageUrl(array $prod): string
    {
        if (isset($prod['defaultImage']) && is_array($prod['defaultImage']) && ! isset($prod['default_image'])) {
            $prod['default_image'] = $prod['defaultImage'];
        }
        if (isset($prod['Images']) && is_array($prod['Images']) && ! isset($prod['images'])) {
            $prod['images'] = $prod['Images'];
        }

        $pim = trim((string) ($prod['product_image'] ?? ''));
        if ($pim !== '' && preg_match('/^[A-Za-z0-9-]{8,}$/', $pim) === 1) {
            return site_url('sellers/catalog/image/' . rawurlencode($pim . '.webp'));
        }

        $imageRefFromRow = static function (?array $imgRow): string {
            if (! is_array($imgRow)) {
                return '';
            }
            $uuid = trim((string) ($imgRow['uuid'] ?? $imgRow['UUID'] ?? ''));
            $ext = strtolower(preg_replace('/[^a-z0-9]/', '', (string) ($imgRow['extension'] ?? '')));
            if ($ext === '') {
                $ext = 'webp';
            }
            if ($uuid !== '' && preg_match('/^[A-Za-z0-9-]{8,}$/', $uuid) === 1) {
                return $uuid . '.' . $ext;
            }
            $fn = trim((string) ($imgRow['name'] ?? ''));
            if ($fn !== '') {
                return $fn;
            }

            return trim((string) ($imgRow['original_name'] ?? ''));
        };

        $ref = '';
        $default = isset($prod['default_image']) && is_array($prod['default_image']) ? $prod['default_image'] : null;
        $ref = $imageRefFromRow($default);
        if ($ref === '') {
            $imgs = $prod['images'] ?? null;
            if (is_array($imgs)) {
                foreach ($imgs as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $inner = isset($row['image']) && is_array($row['image']) ? $row['image'] : $row;
                    $ref = $imageRefFromRow($inner);
                    if ($ref !== '') {
                        break;
                    }
                }
            }
        }
        if ($ref !== '') {
            return site_url('sellers/catalog/image/' . rawurlencode($ref));
        }

        foreach (['simg_url', 'simgURL', 'img_url', 'imgURL', 'Img'] as $k) {
            $u = trim((string) ($prod[$k] ?? ''));
            if ($u === '') {
                continue;
            }
            if (preg_match('#/images/([A-Za-z0-9._-]+)\.(webp|png|jpg|jpeg|gif)$#i', $u, $m) === 1) {
                return site_url('sellers/catalog/image/' . rawurlencode($m[1] . '.' . strtolower($m[2])));
            }
            if (preg_match('#/images/no-image#i', $u) === 1) {
                return base_url('uploads/noimg.png');
            }
            if (strncmp($u, 'http', 4) === 0) {
                return $u;
            }
        }

        return base_url('uploads/noimg.png');
    }

    private function sellerPortalIsPlaceholderNoimgUrl(string $url): bool
    {
        $u = strtolower(trim($url));

        return $u === '' || strpos($u, 'noimg.png') !== false;
    }

    /**
     * Producto con relaciones para miniatura cuando order/full no trae el mismo shape que catálogo.
     *
     * @return array<string, mixed>|null
     */
    private function fetchSellerPortalProductByProIdForOrderImage(int $proId): ?array
    {
        if ($proId <= 0) {
            return null;
        }

        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/products/' . rawurlencode((string) $proId));
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            return null;
        }
        $row = $json['data'];
        if (isset($row['data']) && is_array($row['data'])) {
            $row = $row['data'];
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<object{sku: string, product_name: string, qty: float, unit_price: float, line_total: float, image_url: string, stock: float|null, line_comments: string}>
     */
    private function buildSellerPortalOrderDetailLines(array $payload, int $companyId): array
    {
        $raw = $payload['order_details'] ?? $payload['orderDetails'] ?? [];
        if (! is_array($raw)) {
            return [];
        }
        $customerId = $this->extractOrderCustomerId($payload);
        $catalogByProId = [];
        if ($companyId > 0 && $customerId > 0) {
            $imgTok = $this->sellerPortalCatalogImageToken($companyId, $customerId);
            $catRows = $this->fetchSellerCatalogProductsFromApi($companyId, $customerId, $imgTok);
            if (is_array($catRows)) {
                foreach ($catRows as $crow) {
                    if (! is_array($crow)) {
                        continue;
                    }
                    $cpid = (int) ($crow['pro_id'] ?? 0);
                    if ($cpid > 0) {
                        $catalogByProId[$cpid] = $crow;
                    }
                }
            }
        }

        $productFetchCache = [];
        $out = [];
        foreach ($raw as $item) {
            $d = $this->normalizeSellerApiDetailRow($item);
            if ($d === null) {
                continue;
            }
            $prod = [];
            if (isset($d['product']) && is_array($d['product'])) {
                $prod = $d['product'];
            } elseif (isset($d['Product']) && is_array($d['Product'])) {
                $prod = $d['Product'];
            }
            $sku = trim((string) ($prod['sku'] ?? $d['sku'] ?? ''));
            $productName = trim((string) ($prod['name'] ?? $d['product_name'] ?? $d['description'] ?? ''));
            $qty = isset($d['qty']) ? (float) $d['qty'] : (isset($d['quantity']) ? (float) $d['quantity'] : 0.0);
            $unit = isset($d['sale_price']) ? (float) $d['sale_price'] : (isset($d['unit_price']) ? (float) $d['unit_price'] : (isset($d['price']) ? (float) $d['price'] : 0.0));
            $lineTotal = null;
            foreach (['total', 'total_amount', 'totalAmount', 'amount', 'totalprice'] as $k) {
                if (isset($d[$k]) && $d[$k] !== '' && $d[$k] !== null) {
                    $lineTotal = (float) $d[$k];
                    break;
                }
            }
            if ($lineTotal === null) {
                $lineTotal = $qty * $unit;
            }
            $commentsRaw = trim((string) ($d['comments'] ?? ''));
            $commentRaw = trim((string) ($d['comment'] ?? ''));
            $lineComments = $commentsRaw !== '' ? $commentsRaw : $commentRaw;
            $stockVal = $prod['stock'] ?? null;
            $stock = ($stockVal !== null && $stockVal !== '') ? (float) $stockVal : null;

            $proId = (int) ($d['product_id'] ?? $prod['pro_id'] ?? 0);
            $imageUrl = $this->sellerPortalOrderLineProductImageUrl($prod);
            $catUrl = ($proId > 0 && isset($catalogByProId[$proId])) ? trim((string) ($catalogByProId[$proId]['image_url'] ?? '')) : '';
            if ($catUrl !== '') {
                $imageUrl = $catUrl;
            } elseif ($this->sellerPortalIsPlaceholderNoimgUrl($imageUrl) && $proId > 0) {
                if (! isset($productFetchCache[$proId])) {
                    $productFetchCache[$proId] = $this->fetchSellerPortalProductByProIdForOrderImage($proId);
                }
                $snap = $productFetchCache[$proId];
                if (is_array($snap)) {
                    $imageUrl = $this->sellerPortalOrderLineProductImageUrl($snap);
                }
            }

            $out[] = (object) [
                'sku' => $sku,
                'product_name' => $productName,
                'qty' => $qty,
                'unit_price' => $unit,
                'line_total' => max(0.0, (float) $lineTotal),
                'image_url' => $imageUrl,
                'stock' => $stock,
                'line_comments' => $lineComments,
            ];
        }

        return $out;
    }

    /**
     * @param mixed $item
     *
     * @return array<string, mixed>|null
     */
    private function normalizeSellerApiDetailRow($item): ?array
    {
        if (! is_array($item)) {
            return null;
        }
        if (isset($item['data']) && is_array($item['data'])) {
            $item = $item['data'];
        }
        if (isset($item['attributes']) && is_array($item['attributes'])) {
            $merged = $item['attributes'];
            foreach (['id', 'detail_id', 'product_id', 'order_id'] as $top) {
                if (! array_key_exists($top, $merged) && array_key_exists($top, $item)) {
                    $merged[$top] = $item[$top];
                }
            }

            return $merged;
        }

        return $item;
    }

    /**
     * @return list<int>
     */
    private function sellerHasAllCustomersMode(int $sellerId): bool
    {
        if ($sellerId <= 0) {
            return false;
        }
        if ($this->session->has_userdata(self::SELLER_ALL_CUSTOMERS_FLAG_KEY)) {
            return (int) ($this->session->userdata(self::SELLER_ALL_CUSTOMERS_FLAG_KEY) ?? 0) === 1;
        }

        $ctx = $this->sellerPortalContextPayload();

        return (int) ($ctx['all_customers'] ?? 0) === 1;
    }

    private function sellerPortalApiMutationResultOk($resp): bool
    {
        if ($resp === null) {
            return false;
        }
        if (! is_array($resp)) {
            return false;
        }

        return ! isset($resp['error']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sellerPortalCustomerRowFromApi(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }
        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/customers/' . $customerId);
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            return null;
        }
        $row = $json['data'];

        return $row;
    }

    private function sellerPortalCustomerRowAllowedForSeller(array $row, int $companyId, int $sellerId): bool
    {
        if ((int) ($row['company_id'] ?? 0) !== $companyId) {
            return false;
        }
        if ($this->sellerHasAllCustomersMode($sellerId)) {
            return true;
        }
        $sellers = $row['sellers'] ?? [];
        if (! is_array($sellers)) {
            return false;
        }
        foreach ($sellers as $sv) {
            if (! is_array($sv)) {
                continue;
            }
            if ((int) ($sv['id'] ?? 0) === $sellerId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<int> $validSellerIds
     *
     * @return list<int>
     */
    private function sellerPortalNormalizeSellerIdsFromPostUsingValid(int $loggedSellerId, array $validSellerIds): array
    {
        $validSet = array_fill_keys($validSellerIds, true);
        $raw = $this->input->post('sellers');
        if (! is_array($raw)) {
            $raw = $raw !== null && $raw !== '' ? [$raw] : [];
        }
        $out = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0 && isset($validSet[$id])) {
                $out[$id] = true;
            }
        }
        if ($loggedSellerId > 0) {
            $out[$loggedSellerId] = true;
        }

        return array_values(array_keys($out));
    }

    private function sellerPortalClipPhoneForApi(?string $s, int $max = 20): ?string
    {
        if ($s === null) {
            return null;
        }
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        if (function_exists('mb_strlen') && mb_strlen($s, 'UTF-8') > $max) {
            return mb_substr($s, 0, $max, 'UTF-8');
        }
        if (strlen($s) > $max) {
            return substr($s, 0, $max);
        }

        return $s;
    }

    private function sellerPortalApiValidationExtra($resp): string
    {
        if (! is_array($resp)) {
            return '';
        }
        $m = $resp['message'] ?? null;
        if (is_string($m)) {
            return trim($m);
        }
        if (is_array($m) && isset($m['message']) && is_string($m['message'])) {
            return trim($m['message']);
        }
        $e = $resp['errors'] ?? null;
        if (is_array($e)) {
            $flat = [];
            foreach ($e as $v) {
                if (is_array($v)) {
                    foreach ($v as $msg) {
                        if (is_string($msg)) {
                            $flat[] = $msg;
                        }
                    }
                } elseif (is_string($v)) {
                    $flat[] = $v;
                }
            }
            if ($flat !== []) {
                return implode(' ', $flat);
            }
        }

        return '';
    }

    /**
     * Alta cliente (pestaña Inicio): POST /v2/customer.
     *
     * @return array{ok: bool, code: string, extra?: string}
     */
    private function createSellerPortalCustomerFromPost(int $sellerId, int $companyId): array
    {
        if ($sellerId <= 0 || $companyId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_create_error'];
        }
        $businessName = trim((string) $this->input->post('business_name'));
        $contactName = trim((string) $this->input->post('name'));
        $email = trim((string) $this->input->post('email'));
        $phone = trim((string) $this->input->post('phone'));
        $cellPhone = trim((string) $this->input->post('cell_phone'));
        $taxId = trim((string) $this->input->post('tax_id'));
        $erpCode = trim((string) $this->input->post('erp_code'));
        if ($erpCode === '') {
            $erpCode = trim((string) $this->input->post('erp_id'));
        }
        $idKorPost = (int) $this->input->post('id_kor');
        $discount = (float) str_replace(',', '.', (string) $this->input->post('discount'));
        $catalogEmails = (int) $this->input->post('catalog_emails') === 1 ? 1 : 0;

        $commercialAddress = trim((string) $this->input->post('commercial_address'));
        $commercialDeliveryAddr = trim((string) $this->input->post('commercial_delivery_address'));
        $commercialCountryId = (int) $this->input->post('commercial_country_id');
        $commercialState = trim((string) $this->input->post('commercial_state'));
        $commercialCity = trim((string) $this->input->post('commercial_city'));
        $commercialZip = trim((string) $this->input->post('commercial_zip_code'));
        $commercialZone = trim((string) $this->input->post('commercial_zone'));

        $sameAs = $this->input->post('same_as_commercial');
        $sameAs = $sameAs === '1' || $sameAs === 'on' || $sameAs === 'true';

        $dispatchDeliveryFull = trim((string) $this->input->post('dispatch_delivery_address'));
        $dispatchAddress = trim((string) $this->input->post('dispatch_address'));
        $dispatchCountryId = (int) $this->input->post('dispatch_country_id');
        $dispatchState = trim((string) $this->input->post('dispatch_state'));
        $dispatchCity = trim((string) $this->input->post('dispatch_city'));
        $dispatchZip = trim((string) $this->input->post('dispatch_zip_code'));
        $dispatchZone = trim((string) $this->input->post('dispatch_zone'));

        if ($sameAs) {
            $dispatchDeliveryFull = $dispatchDeliveryFull !== '' ? $dispatchDeliveryFull : $commercialDeliveryAddr;
            $dispatchAddress = $commercialAddress;
            $dispatchCountryId = $commercialCountryId;
            $dispatchState = $commercialState;
            $dispatchCity = $commercialCity;
            $dispatchZip = $commercialZip;
            $dispatchZone = $commercialZone;
        }

        if ($businessName === '' || $email === '') {
            return ['ok' => false, 'code' => 'seller_customer_create_required'];
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'code' => 'seller_customer_create_email_invalid'];
        }

        $validTermIds = array_column($this->sellerPortalTermsListFromApi($companyId), 'term_id');
        $termIdPost = (int) $this->input->post('term_id');
        $defTerm = (int) ($this->session->userdata(self::SELLER_DEFAULT_TERM_ID_KEY) ?? 0);
        $firstTerm = $validTermIds !== [] ? (int) reset($validTermIds) : 0;
        $termId = ($termIdPost > 0 && in_array($termIdPost, $validTermIds, true))
            ? $termIdPost : ($defTerm > 0 && in_array($defTerm, $validTermIds, true) ? $defTerm : $firstTerm);

        $validGroupIds = array_column($this->sellerPortalCustomerGroupsListFromApi($companyId), 'group_id');
        $groupIdPost = (int) $this->input->post('group_id');
        $defGroup = (int) ($this->session->userdata(self::SELLER_DEFAULT_GROUP_ID_KEY) ?? 0);
        $firstGroup = $validGroupIds !== [] ? (int) reset($validGroupIds) : 0;
        $groupId = ($groupIdPost > 0 && in_array($groupIdPost, $validGroupIds, true))
            ? $groupIdPost : ($defGroup > 0 && in_array($defGroup, $validGroupIds, true) ? $defGroup : $firstGroup);

        if ($termId <= 0 || $groupId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_create_missing_defaults'];
        }

        $validSellerIds = array_column($this->sellerPortalSellersListRowsFromApi($companyId), 'id');
        $assignCatalogSellerId = (int) $this->input->post('seller_assign_catalog');
        if ($assignCatalogSellerId <= 0 || ! in_array($assignCatalogSellerId, $validSellerIds, true)) {
            $assignCatalogSellerId = $sellerId;
        }

        $idKor = $idKorPost > 0 ? $idKorPost : ($erpCode !== '' ? (int) preg_replace('/\D+/', '', $erpCode) : 0);
        $custKor = $erpCode !== '' ? $erpCode : '';
        $sellerIdsPivot = $validSellerIds !== []
            ? $this->sellerPortalNormalizeSellerIdsFromPostUsingValid($sellerId, $validSellerIds)
            : [$sellerId];

        $payload = [
            'company_id' => $companyId,
            'name' => $contactName,
            'business_name' => $businessName,
            'email' => $email,
            'phone' => $this->sellerPortalClipPhoneForApi($phone),
            'cell_phone' => $this->sellerPortalClipPhoneForApi($cellPhone),
            'tax_id' => $taxId !== '' ? $taxId : null,
            'discount' => $discount,
            'catalog_emails' => (bool) $catalogEmails,
            'term_id' => $termId,
            'group_id' => $groupId,
            'sellers' => $sellerIdsPivot,
            'seller_assign_catalog' => $assignCatalogSellerId,
            'cust_id_kor' => $custKor !== '' ? $custKor : null,
            'id_kor' => $idKor > 0 ? $idKor : null,
            'commercial_address' => $commercialAddress !== '' ? $commercialAddress : null,
            'commercial_delivery_address' => $commercialDeliveryAddr !== '' ? $commercialDeliveryAddr : null,
            'commercial_country' => $commercialCountryId > 0 ? $commercialCountryId : null,
            'commercial_state' => $commercialState !== '' ? $commercialState : null,
            'commercial_city' => $commercialCity !== '' ? $commercialCity : null,
            'commercial_zip_code' => $commercialZip !== '' ? $commercialZip : null,
            'commercial_zone' => $commercialZone !== '' ? $commercialZone : null,
            'dispatch_address' => $dispatchAddress !== '' ? $dispatchAddress : null,
            'dispatch_delivery_address' => $dispatchDeliveryFull !== '' ? $dispatchDeliveryFull : null,
            'dispatch_country' => $dispatchCountryId > 0 ? $dispatchCountryId : null,
            'dispatch_state' => $dispatchState !== '' ? $dispatchState : null,
            'dispatch_city' => $dispatchCity !== '' ? $dispatchCity : null,
            'dispatch_zip_code' => $dispatchZip !== '' ? $dispatchZip : null,
            'dispatch_zone' => $dispatchZone !== '' ? $dispatchZone : null,
        ];

        $resp = $this->sellerPortalApiV2JsonMutate('POST', '/seller-portal/customers', $payload);
        if ($this->sellerPortalApiMutationResultOk($resp)) {
            return ['ok' => true, 'code' => 'seller_customer_create_success'];
        }

        return ['ok' => false, 'code' => 'seller_customer_create_error', 'extra' => $this->sellerPortalApiValidationExtra($resp)];
    }

    /**
     * @return array{ok: bool, code: string, extra?: string}
     */
    private function updateSellerPortalCustomerFromPost(int $sellerId, int $companyId): array
    {
        if ($sellerId <= 0 || $companyId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_update_error'];
        }
        $customerId = (int) $this->input->post('customer_id');
        if ($customerId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_update_invalid'];
        }
        $existing = $this->sellerPortalCustomerRowFromApi($customerId);
        if ($existing === null || ! $this->sellerPortalCustomerRowAllowedForSeller($existing, $companyId, $sellerId)) {
            return ['ok' => false, 'code' => 'seller_customer_update_invalid'];
        }

        $businessName = trim((string) $this->input->post('business_name'));
        $contactName = trim((string) $this->input->post('name'));
        $email = trim((string) $this->input->post('email'));
        $phone = trim((string) $this->input->post('phone'));
        $cellPhone = trim((string) $this->input->post('cell_phone'));
        $taxId = trim((string) $this->input->post('tax_id'));
        $erpCode = trim((string) $this->input->post('erp_code'));
        if ($erpCode === '') {
            $erpCode = trim((string) $this->input->post('erp_id'));
        }
        $idKorPost = (int) $this->input->post('id_kor');
        $discount = (float) str_replace(',', '.', (string) $this->input->post('discount'));
        $catalogEmails = (int) $this->input->post('catalog_emails') === 1 ? 1 : 0;

        $commercialAddress = trim((string) $this->input->post('commercial_address'));
        $commercialDeliveryAddr = trim((string) $this->input->post('commercial_delivery_address'));
        $commercialCountryId = (int) $this->input->post('commercial_country_id');
        $commercialState = trim((string) $this->input->post('commercial_state'));
        $commercialCity = trim((string) $this->input->post('commercial_city'));
        $commercialZip = trim((string) $this->input->post('commercial_zip_code'));
        $commercialZone = trim((string) $this->input->post('commercial_zone'));

        $sameAs = $this->input->post('same_as_commercial');
        $sameAs = $sameAs === '1' || $sameAs === 'on' || $sameAs === 'true';

        $dispatchDeliveryFull = trim((string) $this->input->post('dispatch_delivery_address'));
        $dispatchAddress = trim((string) $this->input->post('dispatch_address'));
        $dispatchCountryId = (int) $this->input->post('dispatch_country_id');
        $dispatchState = trim((string) $this->input->post('dispatch_state'));
        $dispatchCity = trim((string) $this->input->post('dispatch_city'));
        $dispatchZip = trim((string) $this->input->post('dispatch_zip_code'));
        $dispatchZone = trim((string) $this->input->post('dispatch_zone'));
        $notes = trim((string) $this->input->post('notes'));
        $deliveryNotes = trim((string) $this->input->post('delivery_notes'));

        if ($sameAs) {
            $dispatchDeliveryFull = $dispatchDeliveryFull !== '' ? $dispatchDeliveryFull : $commercialDeliveryAddr;
            $dispatchAddress = $commercialAddress;
            $dispatchCountryId = $commercialCountryId;
            $dispatchState = $commercialState;
            $dispatchCity = $commercialCity;
            $dispatchZip = $commercialZip;
            $dispatchZone = $commercialZone;
        }

        if ($businessName === '' || $email === '') {
            return ['ok' => false, 'code' => 'seller_customer_create_required'];
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'code' => 'seller_customer_create_email_invalid'];
        }

        $validTermIds = array_column($this->sellerPortalTermsListFromApi($companyId), 'term_id');
        $termIdPost = (int) $this->input->post('term_id');
        $defTerm = (int) ($this->session->userdata(self::SELLER_DEFAULT_TERM_ID_KEY) ?? 0);
        $firstTerm = $validTermIds !== [] ? (int) reset($validTermIds) : 0;
        $termId = ($termIdPost > 0 && in_array($termIdPost, $validTermIds, true))
            ? $termIdPost : ($defTerm > 0 && in_array($defTerm, $validTermIds, true) ? $defTerm : $firstTerm);

        $validGroupIds = array_column($this->sellerPortalCustomerGroupsListFromApi($companyId), 'group_id');
        $groupIdPost = (int) $this->input->post('group_id');
        $defGroup = (int) ($this->session->userdata(self::SELLER_DEFAULT_GROUP_ID_KEY) ?? 0);
        $firstGroup = $validGroupIds !== [] ? (int) reset($validGroupIds) : 0;
        $groupId = ($groupIdPost > 0 && in_array($groupIdPost, $validGroupIds, true))
            ? $groupIdPost : ($defGroup > 0 && in_array($defGroup, $validGroupIds, true) ? $defGroup : $firstGroup);

        if ($termId <= 0 || $groupId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_create_missing_defaults'];
        }

        $validSellerIds = array_column($this->sellerPortalSellersListRowsFromApi($companyId), 'id');
        $assignCatalogSellerId = (int) $this->input->post('seller_assign_catalog');
        if ($assignCatalogSellerId <= 0 || ! in_array($assignCatalogSellerId, $validSellerIds, true)) {
            $assignCatalogSellerId = $sellerId;
        }

        $idKor = $idKorPost > 0 ? $idKorPost : ($erpCode !== '' ? (int) preg_replace('/\D+/', '', $erpCode) : 0);
        $custKor = $erpCode !== '' ? $erpCode : '';
        $sellerIdsPivot = $validSellerIds !== []
            ? $this->sellerPortalNormalizeSellerIdsFromPostUsingValid($sellerId, $validSellerIds)
            : [$sellerId];

        $notesDb = $this->sellerPortalTruncateForCustomersColumn($notes, 'notes');
        $shipNotesDb = $this->sellerPortalTruncateForCustomersColumn($deliveryNotes, 'dispatch_shipping_notes');
        $custStatus = (int) ($existing['customer_status'] ?? 1) === 1;

        $payload = [
            'company_id' => $companyId,
            'name' => $contactName,
            'business_name' => $businessName,
            'email' => $email,
            'phone' => $this->sellerPortalClipPhoneForApi($phone),
            'cell_phone' => $this->sellerPortalClipPhoneForApi($cellPhone),
            'tax_id' => $taxId !== '' ? $taxId : null,
            'discount' => $discount,
            'catalog_emails' => (bool) $catalogEmails,
            'customer_status' => $custStatus,
            'term_id' => $termId,
            'group_id' => $groupId,
            'sellers' => $sellerIdsPivot,
            'seller_assign_catalog' => $assignCatalogSellerId,
            'cust_id_kor' => $custKor !== '' ? $custKor : null,
            'id_kor' => $idKor > 0 ? $idKor : null,
            'notes' => $notesDb !== '' ? $notesDb : null,
            'commercial_address' => $commercialAddress !== '' ? $commercialAddress : null,
            'commercial_delivery_address' => $commercialDeliveryAddr !== '' ? $commercialDeliveryAddr : null,
            'commercial_country' => $commercialCountryId > 0 ? $commercialCountryId : null,
            'commercial_state' => $commercialState !== '' ? $commercialState : null,
            'commercial_city' => $commercialCity !== '' ? $commercialCity : null,
            'commercial_zip_code' => $commercialZip !== '' ? $commercialZip : null,
            'commercial_zone' => $commercialZone !== '' ? $commercialZone : null,
            'dispatch_address' => $dispatchAddress !== '' ? $dispatchAddress : null,
            'dispatch_delivery_address' => $dispatchDeliveryFull !== '' ? $dispatchDeliveryFull : null,
            'dispatch_country' => $dispatchCountryId > 0 ? $dispatchCountryId : null,
            'dispatch_state' => $dispatchState !== '' ? $dispatchState : null,
            'dispatch_city' => $dispatchCity !== '' ? $dispatchCity : null,
            'dispatch_zip_code' => $dispatchZip !== '' ? $dispatchZip : null,
            'dispatch_zone' => $dispatchZone !== '' ? $dispatchZone : null,
            'dispatch_shipping_notes' => $shipNotesDb !== '' ? $shipNotesDb : null,
        ];

        $resp = $this->sellerPortalApiV2JsonMutate('PUT', '/seller-portal/customers/' . $customerId, $payload);
        if ($this->sellerPortalApiMutationResultOk($resp)) {
            return ['ok' => true, 'code' => 'seller_customer_update_success'];
        }

        return ['ok' => false, 'code' => 'seller_customer_update_error', 'extra' => $this->sellerPortalApiValidationExtra($resp)];
    }

    /**
     * @return array{ok: bool, code: string}
     */
    private function deleteSellerPortalCustomerAssignmentFromPost(int $sellerId, int $companyId): array
    {
        if ($sellerId <= 0 || $companyId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_delete_invalid'];
        }
        $customerId = (int) $this->input->post('customer_id');
        if ($customerId <= 0) {
            return ['ok' => false, 'code' => 'seller_customer_delete_invalid'];
        }
        $row = $this->sellerPortalCustomerRowFromApi($customerId);
        if ($row === null || ! $this->sellerPortalCustomerRowAllowedForSeller($row, $companyId, $sellerId)) {
            return ['ok' => false, 'code' => 'seller_customer_delete_invalid'];
        }

        $resp = $this->sellerPortalApiV2JsonMutate('POST', '/seller-portal/customers/' . $customerId . '/unassign', []);
        if ($this->sellerPortalApiMutationResultOk($resp)) {
            return ['ok' => true, 'code' => 'seller_customer_delete_success'];
        }

        return ['ok' => false, 'code' => 'seller_customer_delete_error', 'extra' => $this->sellerPortalApiValidationExtra($resp)];
    }

    private function sellerPortalCountryIdFromDisplayName(string $countryName): int
    {
        return $this->sellerPortalCountryIdFromDisplayNameFromApi($countryName);
    }

    /**
     * Mapa nombre (minúsculas) → id (misma fuente que company/customers/Add: solo API country + props).
     *
     * @return array<string, int>
     */
    private function sellerPortalCountryNameToIdMapFromApi(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }
        $cache = [];
        foreach ($this->sellerPortalCountriesSelectListForForms() as $r) {
            if (! is_array($r)) {
                continue;
            }
            $id = (int) ($r['id'] ?? 0);
            $nm = trim((string) ($r['name'] ?? ''));
            if ($id <= 0 || $nm === '') {
                continue;
            }
            $key = function_exists('mb_strtolower') ? mb_strtolower($nm, 'UTF-8') : strtolower($nm);
            $cache[$key] = $id;
        }

        return $cache;
    }

    private function sellerPortalCountryIdFromDisplayNameFromApi(string $countryName): int
    {
        $nm = trim($countryName);
        if ($nm === '') {
            return 0;
        }
        $map = $this->sellerPortalCountryNameToIdMapFromApi();
        $key = function_exists('mb_strtolower') ? mb_strtolower($nm, 'UTF-8') : strtolower($nm);

        return (int) ($map[$key] ?? 0);
    }

    /**
     * @return list<array{term_id: int, name: string}>
     */
    private function sellerPortalTermsListForCompany(int $companyId): array
    {
        return $this->sellerPortalTermsListFromApi($companyId);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function sellerPortalSellersListForCompany(int $companyId): array
    {
        return $this->sellerPortalSellersListRowsFromApi($companyId);
    }

    private function sellerPortalCompanyDefaultCountryId(int $companyId): int
    {
        if ($companyId <= 0) {
            return 0;
        }

        try {
            $json = $this->sellerPortalApiV2JsonGet('/seller-portal/context');
            if (is_array($json) && isset($json['data']['default_country_id'])) {
                $v = (int) $json['data']['default_country_id'];
                if ($v > 0) {
                    return $v;
                }
            }
        } catch (\Throwable $e) {
            log_message('debug', 'Seller_portal sellerPortalCompanyDefaultCountryId: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * @return list<int>
     */
    private function sellerPortalNormalizeSellerIdsFromPost(int $companyId, int $loggedSellerId): array
    {
        $validIds = array_column($this->sellerPortalSellersListForCompany($companyId), 'id');
        $validSet = array_fill_keys($validIds, true);
        $raw = $this->input->post('sellers');
        if (! is_array($raw)) {
            $raw = $raw !== null && $raw !== '' ? [$raw] : [];
        }
        $out = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0 && isset($validSet[$id])) {
                $out[$id] = true;
            }
        }
        if ($loggedSellerId > 0) {
            $out[$loggedSellerId] = true;
        }
        $ids = array_keys($out);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    /**
     * @param list<int> $sellerIds
     */
    private function sellerPortalTruncateForCustomersColumn(string $value, string $logicalColumn): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }
        $max = in_array($logicalColumn, ['notes', 'dispatch_shipping_notes'], true) ? 50 : 255;
        if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $max) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }
        if (strlen($value) > $max) {
            return substr($value, 0, $max);
        }

        return $value;
    }

    private function extractOrderCustomerId(array $row): int
    {
        $cid = (int) ($row['customer_id'] ?? $row['customerId'] ?? 0);
        if ($cid > 0) {
            return $cid;
        }

        $cust = $row['customer'] ?? null;
        if (is_array($cust)) {
            return (int) ($cust['customer_id'] ?? $cust['id'] ?? 0);
        }

        return 0;
    }

    /**
     * Aplana filas tipo {"data":{...}} o {"attributes":{...}} que a veces devuelve la serialización JSON.
     *
     * @param mixed $row
     *
     * @return array<string, mixed>|null
     */
    private function normalizeSellerApiOrderRow($row): ?array
    {
        if (! is_array($row)) {
            return null;
        }
        $cur = $row;
        for ($i = 0; $i < 3; $i++) {
            if (isset($cur['attributes']) && is_array($cur['attributes'])) {
                $cur = $cur['attributes'];

                continue;
            }
            if (isset($cur['data']) && is_array($cur['data'])) {
                $inner = $cur['data'];
                if ($this->looksLikeOrderApiRow($inner)) {
                    return $inner;
                }
                $cur = $inner;

                continue;
            }
            break;
        }

        if ($this->looksLikeOrderApiRow($cur)) {
            return $cur;
        }
        // Listados mínimos (p. ej. solo customer_id + montos) siguen siendo órdenes válidas.
        if (isset($cur['customer_id'], $cur['company_id'])) {
            return $cur;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function looksLikeOrderApiRow(array $row): bool
    {
        return isset($row['order_id']) || isset($row['orderId']) || isset($row['order_number']) || isset($row['orderNumber'])
            || isset($row['UUID']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractSellerPortalCustomerDisplayName(array $row): string
    {
        $cust = $row['customer'] ?? null;
        if (is_array($cust)) {
            $name = trim((string) ($cust['business_name'] ?? $cust['name'] ?? $cust['display_name'] ?? $cust['company_name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
        if (is_string($cust) && trim($cust) !== '') {
            return trim(html_entity_decode(strip_tags($cust), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $cid = (int) ($row['customer_id'] ?? $row['customerId'] ?? 0);
        if ($cid > 0) {
            $apiRow = $this->sellerPortalCustomerRowFromApi($cid);
            if (is_array($apiRow)) {
                $grid = $this->extractSellerPortalCustomerGridFields($apiRow);
                $name = trim($grid['business'] !== '' ? $grid['business'] : $grid['contact']);
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return '';
    }

    private function extractSellerPortalOrderTotal(array $row): float
    {
        foreach (['total_amount', 'totalAmount', 'total_delivered', 'totalDelivered'] as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return (float) $row[$k];
            }
        }
        $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        $discountA = isset($row['discount_a']) ? (float) $row['discount_a'] : (isset($row['discountA']) ? (float) $row['discountA'] : 0.0);
        if ($amount > 0.0 || $discountA > 0.0) {
            return max(0.0, $amount - $discountA);
        }
        $sumDetails = $this->sumSellerPortalOrderDetailsLineTotals($row);
        if ($sumDetails > 0.0) {
            return $sumDetails;
        }

        return 0.0;
    }

    /**
     * Suma importes de líneas del payload API (sin normalizar filas anidadas).
     *
     * @param array<string, mixed> $row
     */
    private function sumSellerPortalOrderDetailsLineTotals(array $row): float
    {
        $details = $row['order_details'] ?? $row['orderDetails'] ?? [];
        if (! is_array($details)) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($details as $d) {
            if (! is_array($d)) {
                continue;
            }
            if (isset($d['data']) && is_array($d['data'])) {
                $d = $d['data'];
            }
            if (isset($d['attributes']) && is_array($d['attributes'])) {
                $d = $d['attributes'];
            }
            if (isset($d['total']) && $d['total'] !== '' && $d['total'] !== null) {
                $sum += (float) $d['total'];

                continue;
            }
            $price = isset($d['sale_price']) ? (float) $d['sale_price'] : (isset($d['price']) ? (float) $d['price'] : (isset($d['unit_price']) ? (float) $d['unit_price'] : 0.0));
            $qty = isset($d['quantity']) ? (float) $d['quantity'] : (isset($d['qty']) ? (float) $d['qty'] : 0.0);
            $sum += $price * $qty;
        }

        return $sum;
    }

    /**
     * Subtotal (amount o suma de líneas), importe de descuento y porcentaje mostrado en la etiqueta (tipo % en pedido).
     *
     * @param array<string, mixed> $row
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function extractSellerPortalOrderSubtotalAndDiscount(array $row, float $orderTotal): array
    {
        $subtotal = 0.0;
        if (isset($row['amount']) && $row['amount'] !== null && $row['amount'] !== '') {
            $subtotal = (float) $row['amount'];
        }
        if ($subtotal <= 0.0) {
            $subtotal = $this->sumSellerPortalOrderDetailsLineTotals($row);
        }
        $discountA = null;
        foreach (['discount_a', 'discountA'] as $k) {
            if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
                $discountA = (float) $row[$k];
                break;
            }
        }
        if ($discountA === null) {
            $discountA = max(0.0, $subtotal - $orderTotal);
        }
        if ($subtotal <= 0.0 && ($orderTotal > 0.0 || $discountA > 0.0)) {
            $subtotal = max(0.0, $orderTotal + $discountA);
        }
        $discountType = (int) ($row['discount_type'] ?? $row['discountType'] ?? 0);
        $discountCfg = isset($row['discount']) && $row['discount'] !== null && $row['discount'] !== '' ? (float) $row['discount'] : 0.0;
        $percentLabel = $discountType === 1 ? $discountCfg : 0.0;

        return [$subtotal, $discountA, $percentLabel];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{0: string, 1: string}
     */
    private function portalOrderRowCreatedParts(array $row): array
    {
        $raw = $row['created'] ?? $row['created_at'] ?? $row['createdAt'] ?? $row['updated'] ?? $row['updated_at'] ?? $row['updatedAt'] ?? null;
        if (is_array($raw)) {
            $raw = $raw['date'] ?? $raw['formatted'] ?? null;
        }
        $s = trim((string) ($raw ?? ''));
        if ($s === '') {
            return ['', ''];
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{1,2}:\d{2}:\d{2})/', $s, $m)) {
            return [$m[1], $m[2]];
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})$/', $s, $m)) {
            return [$m[1], ''];
        }

        $ts = strtotime($s);
        if ($ts !== false) {
            return [date('Y-m-d', $ts), date('H:i:s', $ts)];
        }

        $d = substr($s, 0, 10);
        $t = strlen($s) >= 19 ? substr($s, 11, 8) : '';

        return [$d, $t];
    }

    /**
     * Color hex seguro para el punto de estado (#RGB o #RRGGBB).
     */
    private function sellerPortalSanitizeStatusColor(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $v = trim($value);
        if ($v === '') {
            return '';
        }
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $v) === 1) {
            return $v;
        }

        return '';
    }

    private function portalOrderStatusBadgeClass(?int $codStatus): string
    {
        if ($codStatus === null) {
            return 'portal-order-status--neutral';
        }
        if ($codStatus < 0) {
            return 'portal-order-status--muted';
        }

        switch ($codStatus) {
            case 0:
            case 1:
                return 'portal-order-status--new';
            case 2:
            case 16:
                return 'portal-order-status--pending';
            case 3:
            case 9:
            case 10:
                return 'portal-order-status--progress';
            case 4:
            case 8:
            case 11:
            case 12:
                return 'portal-order-status--done';
            case 7:
            case 13:
                return 'portal-order-status--bad';
            case 5:
            case 6:
            case 14:
                return 'portal-order-status--return';
            default:
                return 'portal-order-status--neutral';
        }
    }

    /**
     * @return array{symbol: string, digits: int, radixPoint: string, groupSeparator: string}
     */
    private function portalOrdersMoneyFormat(int $companyId): array
    {
        $defaults = [
            'symbol' => '$',
            'digits' => 2,
            'radixPoint' => '.',
            'groupSeparator' => ',',
        ];
        if ($companyId <= 0) {
            return $defaults;
        }

        $ctx = $this->sellerPortalContextPayload();
        $fmt = $ctx['format_numeric'] ?? null;
        if (is_array($fmt)) {
            $defaults['digits'] = (int) ($fmt['digits'] ?? 2);
            $defaults['radixPoint'] = (string) ($fmt['radixPoint'] ?? $fmt['radix_point'] ?? '.');
            $defaults['groupSeparator'] = (string) ($fmt['groupSeparator'] ?? $fmt['group_separator'] ?? ',');
        } elseif (is_object($fmt)) {
            $defaults['digits'] = (int) ($fmt->digits ?? 2);
            $defaults['radixPoint'] = (string) ($fmt->radixPoint ?? '.');
            $defaults['groupSeparator'] = (string) ($fmt->groupSeparator ?? ',');
        }

        $sym = trim((string) ($ctx['currency_symbol'] ?? ''));
        if ($sym !== '' && strcasecmp($sym, 'null') !== 0) {
            $defaults['symbol'] = $sym;
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    private function basePortalData(): array
    {
        $this->applyLanguageQueryToSession();
        $langId = $this->getPortalLanguageId();

        return [
            'pageTexts' => $this->loadPortalLangSection($langId),
            'lang_id' => $langId,
            'html_lang' => $this->portalHtmlLangCode($langId),
            'settings' => $this->getPlatformSettings(),
        ];
    }

    private function respondJson(array $data, int $status = 200): void
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }

    private function applyLanguageQueryToSession(): void
    {
        $lang = $this->input->get('lang');
        if (! is_string($lang) || $lang === '') {
            return;
        }
        $lang = strtolower($lang);
        $map = [
            'en' => 1,
            'es' => 2,
            'pt' => 3,
            'it' => 4,
            'ru' => 5,
            'de' => 6,
            'zh' => 7,
        ];
        if (isset($map[$lang])) {
            $this->session->set_userdata('seller_portal_lang_id', $map[$lang]);

            return;
        }
        if (ctype_digit($lang)) {
            $n = (int) $lang;
            if ($n >= 1 && $n <= 7) {
                $this->session->set_userdata('seller_portal_lang_id', $n);
            }
        }
    }

    private function getPortalLanguageId(): int
    {
        $loggedIn = $this->session->has_userdata(self::SELLER_COMPANY_KEY)
            && $this->session->has_userdata(self::SELLER_ID_KEY);
        if ($loggedIn) {
            $lm = (int) ($this->session->userdata(self::SELLER_LANG_KEY) ?? 0);
            if ($lm >= 1 && $lm <= 7) {
                return $lm;
            }
        }
        $id = (int) ($this->session->userdata('seller_portal_lang_id') ?? 0);
        if ($id >= 1 && $id <= 7) {
            return $id;
        }

        return 1;
    }

    private function langJsonFilenameForLanguageId(int $languageId): string
    {
        static $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];

        return $map[$languageId] ?? 'en-EN.json';
    }

    private function portalHtmlLangCode(int $languageId): string
    {
        static $map = [
            1 => 'en',
            2 => 'es',
            3 => 'pt',
            4 => 'it',
            5 => 'ru',
            6 => 'de',
            7 => 'zh-CN',
        ];

        return $map[$languageId] ?? 'en';
    }

    /**
     * @param list<array<string, mixed>> $customersRows
     */
    private function sellerPortalCatalogRefreshAssignableIdsSession(array $customersRows): void
    {
        $ids = [];
        foreach ($customersRows as $c) {
            if (! is_array($c)) {
                continue;
            }
            $id = (int) ($c['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            $this->session->unset_userdata(self::CATALOG_ASSIGNABLE_IDS_KEY);
        } else {
            $this->session->set_userdata(self::CATALOG_ASSIGNABLE_IDS_KEY, $ids);
        }
    }

    private function sellerPortalCatalogCustomerIsAssigned(int $customerId): bool
    {
        if ($customerId <= 0) {
            return false;
        }
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();
        $apiIds = $this->fetchAssignedCustomerIdsForSellerPortalCatalogApiOnly($sellerId, $companyId);
        if (in_array($customerId, $apiIds, true)) {
            return true;
        }

        $cached = $this->session->userdata(self::CATALOG_ASSIGNABLE_IDS_KEY);
        if (is_array($cached)) {
            foreach ($cached as $v) {
                if ((int) $v === $customerId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Columnas opcionales de `customers` a mostrar cuando existan en BD.
     *
     * @return array<string, bool>
     */
    private function sellerPortalCustomerListFiltersFromInput(): array
    {
        $status = strtolower(trim((string) $this->input->get('f_status')));
        if (! in_array($status, ['', 'enabled', 'disabled'], true)) {
            $status = '';
        }
        $groupId = (int) $this->input->get('f_group');
        if ($groupId < 0) {
            $groupId = 0;
        }
        $sellerIdF = (int) $this->input->get('f_seller');
        if ($sellerIdF < 0) {
            $sellerIdF = 0;
        }
        $termId = (int) $this->input->get('f_term');
        if ($termId < 0) {
            $termId = 0;
        }
        $q = trim((string) $this->input->get('q'));
        $page = (int) $this->input->get('page');
        if ($page < 1) {
            $page = 1;
        }

        return [
            'status' => $status,
            'group_id' => $groupId,
            'seller_id' => $sellerIdF,
            'term_id' => $termId,
            'q' => $q,
            'page' => $page,
        ];
    }

    /**
     * @param list<array<string, mixed>> $customers
     * @param array{status: string, group_id: int, seller_id: int, term_id: int, q: string, page: int} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function sellerPortalFilterCustomersForPortal(array $customers, array $filters): array
    {
        $out = [];
        $q = (string) ($filters['q'] ?? '');
        $qLower = $q !== '' && function_exists('mb_strtolower') ? mb_strtolower($q, 'UTF-8') : strtolower($q);
        foreach ($customers as $c) {
            if (! is_array($c)) {
                continue;
            }
            $status = (string) ($c['status'] ?? '');
            $stF = (string) ($filters['status'] ?? '');
            if ($stF === 'enabled' && $status !== 'active') {
                continue;
            }
            if ($stF === 'disabled' && $status !== 'inactive') {
                continue;
            }
            $fg = (int) ($filters['group_id'] ?? 0);
            if ($fg > 0 && (int) ($c['group_id'] ?? 0) !== $fg) {
                continue;
            }
            $fs = (int) ($filters['seller_id'] ?? 0);
            if ($fs > 0 && (int) ($c['assigned_seller_id'] ?? 0) !== $fs) {
                continue;
            }
            $ft = (int) ($filters['term_id'] ?? 0);
            if ($ft > 0 && (int) ($c['term_id'] ?? 0) !== $ft) {
                continue;
            }
            if ($qLower !== '') {
                $blob = implode(' ', array_filter([
                    (string) ($c['business'] ?? ''),
                    (string) ($c['contact'] ?? ''),
                    (string) ($c['email'] ?? ''),
                    (string) ($c['phone'] ?? ''),
                    (string) ($c['mobile'] ?? ''),
                    (string) ($c['erp_id'] ?? ''),
                    (string) ($c['address_full'] ?? ''),
                    (string) ($c['customer_group_name'] ?? ''),
                    (string) ($c['assigned_seller_name'] ?? ''),
                    (string) ($c['name_sort_display'] ?? ''),
                ], static function ($s) {
                    return trim((string) $s) !== '';
                }));
                $blobLower = function_exists('mb_strtolower') ? mb_strtolower($blob, 'UTF-8') : strtolower($blob);
                $pos = function_exists('mb_strpos') ? mb_strpos($blobLower, $qLower) : strpos($blobLower, $qLower);
                if ($pos === false) {
                    continue;
                }
            }
            $out[] = $c;
        }

        return $out;
    }

    /**
     * Cabeceras de contexto del portal vendedor para rutas /seller-portal/*.
     *
     * @return array<string, string>
     */
    private function sellerPortalApiHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];
        $token = trim((string) ($this->session->userdata('token') ?? ''));
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        $sellerId = $this->getSellerPortalSellerId();
        $companyId = $this->getSellerCompanyId();
        if ($sellerId > 0) {
            $headers['X-Seller-Portal-Seller-Id'] = (string) $sellerId;
        }
        if ($companyId > 0) {
            $headers['X-Seller-Portal-Company-Id'] = (string) $companyId;
        }

        return $headers;
    }

    /**
     * GET JSON autenticado a la API v2 (token Sanctum de sesión del portal vendedor).
     *
     * @param array<string, scalar|list<scalar>> $query
     *
     * @return array<string, mixed>|null Decodificado; null si no hay token o HTTP no 2xx.
     */
    private function sellerPortalApiV2JsonGet(string $path, array $query = []): ?array
    {
        $token = trim((string) ($this->session->userdata('token') ?? ''));
        if ($token === '') {
            return null;
        }
        $path = '/' . ltrim($path, '/');
        $url = rtrim($this->getApiV2EntryPoint(), '/') . '/v2' . $path;
        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 60]);
            $response = $client->request('GET', $url, [
                'headers' => $this->sellerPortalApiHeaders(),
                'query' => $query,
            ]);
            $code = $response->getStatusCode();
            if ($code < 200 || $code >= 300) {
                log_message('debug', 'Seller_portal sellerPortalApiV2JsonGet: HTTP ' . $code . ' path=' . $path);

                return null;
            }
            $raw = $response->getBody()->getContents();
            $json = json_decode($raw, true);

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            log_message('error', 'Seller_portal sellerPortalApiV2JsonGet: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * POST/PUT/PATCH JSON a rutas seller-portal.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    private function sellerPortalApiV2JsonMutate(string $method, string $path, array $payload = []): ?array
    {
        $token = trim((string) ($this->session->userdata('token') ?? ''));
        if ($token === '') {
            return null;
        }
        $path = '/' . ltrim($path, '/');
        $url = rtrim($this->getApiV2EntryPoint(), '/') . '/v2' . $path;
        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 60]);
            $response = $client->request(strtoupper($method), $url, [
                'headers' => $this->sellerPortalApiHeaders() + ['Content-Type' => 'application/json'],
                'json' => $payload,
            ]);
            $code = $response->getStatusCode();
            $raw = $response->getBody()->getContents();
            $json = json_decode($raw, true);
            if (! is_array($json)) {
                $json = ['message' => $raw];
            }
            if ($code < 200 || $code >= 300) {
                $json['error'] = $json['error'] ?? true;
                log_message('debug', 'Seller_portal sellerPortalApiV2JsonMutate: HTTP ' . $code . ' path=' . $path);

                return $json;
            }

            return $json;
        } catch (\Throwable $e) {
            log_message('error', 'Seller_portal sellerPortalApiV2JsonMutate: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Listado de clientes asignados vía {@see GET /v2/seller-portal/customers}.
     *
     * @return list<array<string, mixed>>|null null si la API no responde.
     */
    private function fetchAssignedCustomersForSellerPortalFromApi(int $sellerId, int $companyId): ?array
    {
        if ($sellerId <= 0 || $companyId <= 0) {
            return null;
        }

        $json = $this->sellerPortalApiV2JsonGet('/seller-portal/customers');
        if ($json === null) {
            return null;
        }
        $data = $json['data'] ?? null;
        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param mixed $payload
     *
     * @return array<string, mixed>|null
     */
    private function sellerPortalNormalizeApiCustomerPayload($payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }
        if (isset($payload['attributes']) && is_array($payload['attributes'])) {
            $base = $payload['attributes'];
            if (! isset($base['customer_id']) && isset($payload['customer_id'])) {
                $base['customer_id'] = $payload['customer_id'];
            }

            return $base;
        }

        return $payload;
    }

    /**
     * @param list<array<string, mixed>> $apiCustomers
     * @param int $companyId empresa (vendedores y país solo vía API v2)
     *
     * @return list<array<string, mixed>>
     */
    private function fetchAssignedCustomersForSellerPortal(int $sellerId, int $companyId): array
    {
        $fromApi = $this->fetchAssignedCustomersForSellerPortalFromApi($sellerId, $companyId);

        return $fromApi ?? [];
    }


    /**
     * Grupos de cliente de la empresa (para desplegable de solo lectura en el listado).
     *
     * @return list<array{group_id: int, name: string}>
     */
    private function sellerPortalCustomerGroupsForCompany(int $companyId): array
    {
        return $this->sellerPortalCustomerGroupsListFromApi($companyId);
    }

    /**
     * @param list<int> $sellerIds
     *
     * @return array<int, string>
     */
    private function resolveCatalogCustomerLabel(int $customerId, int $sellerId, int $companyId): string
    {
        foreach ($this->fetchAssignedCustomersForSellerPortalCustomerTabApiOnly($sellerId, $companyId) as $c) {
            if ((int) ($c['id'] ?? 0) === $customerId) {
                return (string) ($c['label'] ?? '');
            }
        }

        return '#' . (string) $customerId;
    }

    private function sellerCatalogPublicImageBase(): string
    {
        $ep = rtrim($this->getApiV2EntryPoint(), '/');
        $base = preg_replace('#/api$#', '', $ep);

        return rtrim($base !== '' && $base !== $ep ? $base : $ep, '/');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getSellerCatalogCart(): array
    {
        $raw = $this->session->userdata(self::CATALOG_CART_KEY);
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $k => $line) {
            if (! is_array($line)) {
                continue;
            }
            $pid = (int) ($line['pro_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $stock = $line['stock'] ?? null;
            $out[(string) $pid] = [
                'pro_id' => $pid,
                'name' => (string) ($line['name'] ?? ''),
                'sku' => (string) ($line['sku'] ?? ''),
                'price' => (float) ($line['price'] ?? 0),
                'qty' => (float) ($line['qty'] ?? 0),
                'fixed_price' => (bool) ($line['fixed_price'] ?? false),
                'minimum_stock' => (float) ($line['minimum_stock'] ?? 0),
                'notify_minimum_stock' => ! empty($line['notify_minimum_stock']),
                'stock' => $stock !== null && $stock !== '' ? (float) $stock : null,
            ];
        }

        return $out;
    }

    private function sellerCatalogCartLineCount(): int
    {
        return count($this->getSellerCatalogCart());
    }

    private function sellerCatalogCartGrandTotal(): float
    {
        $t = 0.0;
        foreach ($this->getSellerCatalogCart() as $line) {
            $t += (float) ($line['price'] ?? 0) * (float) ($line['qty'] ?? 0);
        }

        return round($t, 4);
    }

    /**
     * Token de carrito de catálogo (misma fila que portal cliente) para URLs /api/v2/images/catalog/...?token=
     */
    private function sellerPortalCatalogImageToken(int $companyId, int $customerId): string
    {
        if ($companyId <= 0 || $customerId <= 0) {
            return '';
        }

        $resp = $this->sellerPortalApiV2JsonMutate(
            'POST',
            '/seller-portal/catalog/customers/' . rawurlencode((string) $customerId) . '/browse-session',
            []
        );
        if (is_array($resp) && isset($resp['data']['token_kor']) && $resp['data']['token_kor'] !== '') {
            return (string) $resp['data']['token_kor'];
        }

        return '';
    }

    /**
     * Tras elegir cliente + «New order»: misma pantalla que company (catalogo/producto) con token de catalog_cart.
     *
     * URL: base_url('catalogo/producto?token=' . token_kor).
     */
    private function sellerPortalRedirectToCatalogoProductoAfterCustomerPick(int $companyId, int $customerId): void
    {
        $token = $this->sellerPortalCatalogImageToken($companyId, $customerId);
        if ($token === '') {
            $this->session->set_flashdata('seller_catalog_flash', 'catalog_browse_token_error');
            redirect(site_url('sellers/catalog'));

            return;
        }
        $this->session->unset_userdata(self::CATALOG_CART_KEY);
        redirect(base_url('catalogo/producto?token=' . rawurlencode($token)));
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchSellerCatalogProductsFromApi(int $companyId, int $customerId, string $catalogImageToken = ''): ?array
    {
        if ($companyId <= 0 || $customerId <= 0) {
            return null;
        }

        $json = $this->sellerPortalApiV2JsonGet(
            '/seller-portal/catalog/customers/' . rawurlencode((string) $customerId) . '/products'
        );
        if ($json === null) {
            return null;
        }
        $rows = $json['data'] ?? [];
        if (is_array($rows) && isset($rows['data']) && is_array($rows['data'])) {
            $rows = $rows['data'];
        }
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }
            $row = $this->normalizeSellerPortalCatalogProductRow($item, $companyId, $catalogImageToken);
            if ($row !== null) {
                $out[] = $row;
            }
        }

        return $out;
    }

    private function normalizeSellerPortalCatalogProductRow(array $item, int $companyId, string $catalogImageToken): ?array
    {
        $proId = (int) ($item['pro_id'] ?? $item['product_id'] ?? 0);
        if ($proId <= 0) {
            return null;
        }
        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            $name = '#' . (string) $proId;
        }
        $sku = trim((string) ($item['sku'] ?? ''));
        $price = isset($item['sale_price']) ? (float) $item['sale_price'] : 0.0;
        if ($price <= 0 && isset($item['price_reduced'])) {
            $price = (float) $item['price_reduced'];
        }
        if ($price <= 0 && isset($item['list_price'])) {
            $price = (float) $item['list_price'];
        }
        $orderable = $price > 0;
        $img = trim((string) ($item['Img'] ?? $item['imageurl'] ?? ''));
        $imageUrl = '';
        $imageUuid = null;
        $imageResolveMode = $img === '' ? 'empty' : 'unparsed';
        // Miniaturas vía proxy MVC (sellers/catalog/image/…); Bearer en servidor, sin URL pública /api/v2/images/catalog.
        $legacyFile = trim((string) ($item['images'] ?? $item['pic1'] ?? ''));
        if ($legacyFile !== '' && $companyId > 0) {
            $localRel = 'uploads/products/' . $companyId . '/' . $legacyFile;
            if (is_file(FCPATH . $localRel)) {
                $imageUrl = base_url($localRel);
                $imageResolveMode = 'local_upload';
            }
        }
        if ($imageUrl === '' && $img !== '' && preg_match('#/images/([A-Za-z0-9-]+)\.(webp|png|jpg|jpeg|gif)$#i', $img, $m) === 1) {
            $uuid = $m[1];
            $ext = strtolower($m[2]);
            $imageUuid = $uuid;
            $imageUrl = site_url('sellers/catalog/image/' . $uuid . '.' . $ext);
            $imageResolveMode = 'mvc_proxy';
        } elseif ($imageUrl === '' && $img !== '' && preg_match('#/images/no-image#i', $img) === 1) {
            $imageUrl = base_url('uploads/noimg.png');
            $imageResolveMode = 'noimg_placeholder';
        } elseif ($imageUrl === '' && $img !== '' && strncmp($img, 'http', 4) === 0) {
            $imageUrl = $img;
            $imageResolveMode = 'absolute_url';
        }
        $minimumStock = isset($item['minimum_stock']) ? (float) $item['minimum_stock'] : 0.0;
        $notifyRaw = $item['notify_minimum_stock'] ?? 0;
        $notifyMin = $notifyRaw === true || $notifyRaw === 1 || $notifyRaw === '1';
        $stock = isset($item['stock']) ? (float) $item['stock'] : 0.0;

        $categoryIds = [];
        $categoryNamesById = [];
        if (isset($item['categories']) && is_array($item['categories'])) {
            foreach ($item['categories'] as $c) {
                if (! is_array($c)) {
                    continue;
                }
                $cid = (int) ($c['cat_id'] ?? $c['category_id'] ?? 0);
                if ($cid <= 0) {
                    continue;
                }
                $categoryIds[] = $cid;
                $categoryNamesById[$cid] = trim((string) ($c['category_name'] ?? $c['name'] ?? ('#' . (string) $cid)));
            }
        }
        $categoryIds = array_values(array_unique($categoryIds));

        $catalogImagesTable = $item['catalog_images_table'] ?? null;
        if (! is_array($catalogImagesTable)) {
            $catalogImagesTable = null;
        }

        $productsTable = $item['products_table'] ?? null;
        if (! is_array($productsTable)) {
            $productsTable = null;
        }

        $productImagesTable = $item['product_images_table'] ?? null;
        if (! is_array($productImagesTable)) {
            $productImagesTable = null;
        }

        return [
            'pro_id' => $proId,
            'name' => $name,
            'sku' => $sku,
            'price' => round($price > 0 ? $price : 0.0, 4),
            'orderable' => $orderable,
            'img' => $img,
            'image_url' => $imageUrl,
            'image_uuid' => $imageUuid,
            'image_resolve_mode' => $imageResolveMode,
            'catalog_images_table' => $catalogImagesTable,
            'products_table' => $productsTable,
            'product_images_table' => $productImagesTable,
            'minimum_stock' => $minimumStock,
            'notify_minimum_stock' => $notifyMin,
            'stock' => $stock,
            'category_ids' => $categoryIds,
            'category_names_by_id' => $categoryNamesById,
        ];
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    private function sellerPortalCatalogProductsKeyedByProId(array $products): array
    {
        $map = [];
        foreach ($products as $p) {
            if (! is_array($p)) {
                continue;
            }
            $id = (int) ($p['pro_id'] ?? 0);
            if ($id > 0) {
                $map[$id] = $p;
            }
        }

        return $map;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array{cat_id: int, name: string, count: int}>
     */
    private function buildSellerPortalCatalogCategoryFilterList(array $products): array
    {
        $acc = [];
        foreach ($products as $p) {
            if (! is_array($p)) {
                continue;
            }
            $ids = isset($p['category_ids']) && is_array($p['category_ids']) ? $p['category_ids'] : [];
            $names = isset($p['category_names_by_id']) && is_array($p['category_names_by_id']) ? $p['category_names_by_id'] : [];
            foreach ($ids as $cid) {
                $cid = (int) $cid;
                if ($cid <= 0) {
                    continue;
                }
                if (! isset($acc[$cid])) {
                    $acc[$cid] = [
                        'cat_id' => $cid,
                        'name' => (string) ($names[$cid] ?? ('#' . (string) $cid)),
                        'count' => 0,
                    ];
                }
                $acc[$cid]['count']++;
            }
        }
        $list = array_values($acc);
        usort($list, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $list;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return list<array<string, mixed>>
     */
    private function filterAndSortSellerPortalCatalogProducts(array $products, ?int $categoryId, ?string $search, ?int $short): array
    {
        $out = $products;
        if ($categoryId !== null && $categoryId > 0) {
            $out = array_values(array_filter($out, static function (array $p) use ($categoryId): bool {
                $ids = isset($p['category_ids']) && is_array($p['category_ids']) ? $p['category_ids'] : [];

                return in_array($categoryId, $ids, true);
            }));
        }
        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search, 'UTF-8');
            $out = array_values(array_filter($out, static function (array $p) use ($needle): bool {
                $name = mb_strtolower((string) ($p['name'] ?? ''), 'UTF-8');
                $sku = mb_strtolower((string) ($p['sku'] ?? ''), 'UTF-8');

                return ($needle !== '' && (mb_strpos($name, $needle, 0, 'UTF-8') !== false || mb_strpos($sku, $needle, 0, 'UTF-8') !== false));
            }));
        }
        usort($out, function (array $a, array $b) use ($short): int {
            $oA = isset($a['order']) ? (int) $a['order'] : 0;
            $oB = isset($b['order']) ? (int) $b['order'] : 0;
            switch ($short) {
                case 2:
                    $cmp = strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));

                    return $cmp === 0 ? $oA <=> $oB : -$cmp;
                case 3:
                    $cmp = strcasecmp((string) ($a['sku'] ?? ''), (string) ($b['sku'] ?? ''));

                    return $cmp === 0 ? $oA <=> $oB : $cmp;
                case 4:
                    $cmp = strcasecmp((string) ($a['sku'] ?? ''), (string) ($b['sku'] ?? ''));

                    return $cmp === 0 ? $oA <=> $oB : -$cmp;
                case 5:
                    $cmp = ((float) ($a['price'] ?? 0) <=> (float) ($b['price'] ?? 0));

                    return $cmp === 0 ? $oA <=> $oB : $cmp;
                case 6:
                    $cmp = ((float) ($a['price'] ?? 0) <=> (float) ($b['price'] ?? 0));

                    return $cmp === 0 ? $oA <=> $oB : -$cmp;
                case 1:
                default:
                    $cmp = strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));

                    return $cmp === 0 ? $oA <=> $oB : $cmp;
            }
        });

        return $out;
    }

    private function sellerPortalCatalogMinStepQty(array $productRow): float
    {
        $m = (float) ($productRow['minimum_stock'] ?? 0);

        return $m > 0 ? $m : 1.0;
    }

    private function sellerPortalMoqApplies(array $productRow): bool
    {
        return ! empty($productRow['notify_minimum_stock']) && (float) ($productRow['minimum_stock'] ?? 0) > 0;
    }

    private function sellerPortalQtyIsMoqCompliant(float $qty, float $multiplo): bool
    {
        if ($multiplo <= 0) {
            return true;
        }
        $ratio = $qty / $multiplo;

        return abs($ratio - round($ratio)) < 1e-8;
    }

    /**
     * @param array<string, mixed> $productRow
     *
     * @return array<string, mixed>
     */
    private function sellerPortalCatalogCartLineFromProductRow(array $productRow, float $qty): array
    {
        return [
            'pro_id' => (int) ($productRow['pro_id'] ?? 0),
            'name' => (string) ($productRow['name'] ?? ''),
            'sku' => (string) ($productRow['sku'] ?? ''),
            'price' => (float) ($productRow['price'] ?? 0),
            'qty' => $qty,
            'fixed_price' => false,
            'minimum_stock' => (float) ($productRow['minimum_stock'] ?? 0),
            'notify_minimum_stock' => ! empty($productRow['notify_minimum_stock']),
            'stock' => isset($productRow['stock']) ? (float) $productRow['stock'] : null,
        ];
    }

    private function getSellerPortalCompanySellWithInventory(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }
        $ctx = $this->sellerPortalContextPayload();

        return (int) ($ctx['sell_with_inventory'] ?? 0) === 1;
    }

    private function getSellerPortalCompanyShowInventory(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }
        $ctx = $this->sellerPortalContextPayload();

        return (int) ($ctx['show_inventory'] ?? 0) === 1;
    }

    private function sellerPortalCatalogProductsRedirectQueryFromPost(): string
    {
        $cat = $this->input->post('catalog_redirect_cat_id');
        $search = $this->input->post('catalog_redirect_search');
        $short = $this->input->post('catalog_redirect_short');
        $q = [];
        if ($cat !== null && $cat !== '') {
            $q['cat_id'] = (int) $cat;
        }
        if ($search !== null && trim((string) $search) !== '') {
            $q['search'] = trim((string) $search);
        }
        if ($short !== null && $short !== '') {
            $q['short'] = (int) $short;
        }

        return $q === [] ? '' : ('?' . http_build_query($q));
    }

    /**
     * @param array<string, array<string, mixed>> $cart
     */
    private function validateSellerPortalCartBusinessRules(array $cart, int $companyId): ?string
    {
        $cid = (int) ($this->session->userdata(self::CATALOG_CUSTOMER_KEY) ?? 0);
        $catalogImgToken = $this->sellerPortalCatalogImageToken($companyId, $cid);
        $all = $this->fetchSellerCatalogProductsFromApi($companyId, $cid, $catalogImgToken);
        if ($all === null) {
            return 'catalog_products_load_error';
        }
        $map = $this->sellerPortalCatalogProductsKeyedByProId($all);
        $sellInv = $this->getSellerPortalCompanySellWithInventory($companyId);
        foreach ($cart as $line) {
            if (! is_array($line)) {
                continue;
            }
            $pid = (int) ($line['pro_id'] ?? 0);
            $pr = $map[$pid] ?? null;
            if ($pr === null || empty($pr['orderable'])) {
                return 'catalog_product_not_available';
            }
            $qty = (float) ($line['qty'] ?? 0);
            if ($this->sellerPortalMoqApplies($pr) && ! $this->sellerPortalQtyIsMoqCompliant($qty, (float) $pr['minimum_stock'])) {
                $this->session->set_flashdata('seller_catalog_checkout_error_min', (string) (int) $pr['minimum_stock']);

                return 'catalog_moq_invalid';
            }
            if ($sellInv && $qty > (float) ($pr['stock'] ?? 0)) {
                return 'catalog_stock_exceeded';
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $lines
     *
     * @return array{ok: bool, order_id?: int, code?: string}
     */
    private function postSellerPortalOrderFromApi(int $customerId, int $sellerId, array $lines): array
    {
        $products = [];
        foreach ($lines as $line) {
            $price = (float) ($line['price'] ?? 0);
            $qty = (float) ($line['qty'] ?? 0);
            $pid = (int) ($line['pro_id'] ?? 0);
            if ($pid <= 0 || $qty <= 0 || $price <= 0) {
                continue;
            }
            $products[] = [
                'pro_id' => $pid,
                'qty' => $qty,
                'price' => $price,
                'fixed_price' => (bool) ($line['fixed_price'] ?? false),
                'comment' => '-',
            ];
        }
        if ($products === []) {
            return ['ok' => false, 'code' => 'catalog_cart_empty'];
        }
        $payload = [
            'customer_id' => $customerId,
            'seller_id' => $sellerId > 0 ? $sellerId : 0,
            'discount_type' => 1,
            'discount' => 0,
            'proforma' => false,
            'source' => 'seller_portal',
            'products' => $products,
        ];

        $resp = $this->sellerPortalApiV2JsonMutate('POST', '/seller-portal/catalog/orders', $payload);
        if ($resp === null) {
            return ['ok' => false, 'code' => 'catalog_order_error_generic'];
        }
        if (isset($resp['error'])) {
            if (isset($resp['message']) && is_string($resp['message']) && str_contains($resp['message'], 'validation')) {
                return ['ok' => false, 'code' => 'catalog_order_error_validation'];
            }

            return ['ok' => false, 'code' => 'catalog_order_error_http'];
        }
        $data = $resp['data'] ?? $resp;
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        $oid = (int) ($data['order_id'] ?? $data['id'] ?? 0);
        if ($oid > 0) {
            return ['ok' => true, 'order_id' => $oid];
        }

        return ['ok' => false, 'code' => 'catalog_order_error_generic'];
    }

    /**
     * Rutas absolutas a api/lang/{archivo}.json (mismo criterio que Customer_portal::candidatePortalLangJsonPaths).
     *
     * @return list<string>
     */
    private function candidateSellerPortalLangJsonPaths(string $file): array
    {
        $paths = [];
        $seen = [];

        $add = static function (string $path) use (&$paths, &$seen): void {
            $norm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (! is_readable($norm) || ! is_file($norm)) {
                return;
            }
            $resolved = realpath($norm);
            $canonical = $resolved !== false ? $resolved : $norm;
            if (isset($seen[$canonical])) {
                return;
            }
            $seen[$canonical] = true;
            $paths[] = $canonical;
        };

        $custom = $this->config->item('seller_portal_lang_path');
        if (is_string($custom) && $custom !== '') {
            $add(rtrim($custom, '/\\') . DIRECTORY_SEPARATOR . $file);
        }

        if (defined('FCPATH')) {
            $parentOfFront = realpath(rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . '..');
            if ($parentOfFront !== false) {
                $add($parentOfFront . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
            }
            $add(rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
        }

        $appTrim = rtrim(APPPATH, '/\\');
        $repoRoot = dirname(dirname($appTrim));
        $add($repoRoot . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);

        if (PHP_VERSION_ID >= 70000) {
            $add(dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
        }

        if (defined('BASEPATH')) {
            $mvcDir = dirname(rtrim(BASEPATH, '/\\'));
            $add($mvcDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
        }

        $add(rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'seller_portal' . DIRECTORY_SEPARATOR . $file);

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadPortalLangSectionFromFile(int $languageId): array
    {
        $file = $this->langJsonFilenameForLanguageId($languageId);

        foreach ($this->candidateSellerPortalLangJsonPaths($file) as $path) {
            $raw = @file_get_contents($path);
            if ($raw === false || $raw === '') {
                continue;
            }
            if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
                $raw = substr($raw, 3);
            }
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Seller_portal json_decode failed for ' . $path . ': ' . json_last_error_msg());

                continue;
            }
            if (is_array($data) && isset($data['cmp-seller-portal']) && is_array($data['cmp-seller-portal'])) {
                return $data['cmp-seller-portal'];
            }
        }

        return [];
    }

    /**
     * Textos del portal: bloque cmp-seller-portal. Inglés como base y el idioma activo sobrescribe (claves nuevas no fallan en silencio).
     *
     * @return array<string, mixed>
     */
    private function loadPortalLangSection(int $languageId): array
    {
        $en = $this->loadPortalLangSectionFromFile(1);
        $loc = $this->loadPortalLangSectionFromFile((int) $languageId);

        if ($loc === [] && $en === []) {
            log_message('error', 'Seller_portal: cmp-seller-portal no disponible (language_id=' . $languageId . ')');

            return [];
        }

        if ((int) $languageId === 1) {
            return $en !== [] ? $en : $loc;
        }

        if ($loc === []) {
            return $en;
        }

        return $en !== [] ? array_merge($en, $loc) : $loc;
    }
}
