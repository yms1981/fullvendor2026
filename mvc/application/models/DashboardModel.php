<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class DashboardModel extends CI_Model
{
    use ApiClient;

    public $company = array();

    /** @var int|null company_id inyectado desde el controlador (fuente de verdad). */
    protected $forcedCompanyId = null;

    function __construct()
	{
        parent::__construct();
        $this->getHttpClient();
        $this->company = $this->Helper->get_company_info();
    }

    /**
     * El controlador debe pasar getCompanyId(): Helper/sesión en el modelo a menudo
     * no ven compId tras session_write_close() o si Helper se construyó demasiado pronto.
     */
    public function setCompanyId(int $companyId): void
    {
        $this->forcedCompanyId = $companyId > 0 ? $companyId : null;
        if ($companyId > 0 && (! is_object($this->company) || (int) ($this->company->company_id ?? 0) < 1)) {
            $this->company = (object) ['company_id' => $companyId];
        }
    }

    /**
     * company_id fiable: controlador → sesión reabierta → Helper.
     */
    protected function resolveCompanyId(): int
    {
        if ($this->forcedCompanyId !== null && $this->forcedCompanyId > 0) {
            return (int) $this->forcedCompanyId;
        }

        if (is_object($this->company) && (int) ($this->company->company_id ?? 0) > 0) {
            return (int) $this->company->company_id;
        }

        // Tras setUserData() el controlador cierra la sesión PHP; hay que reabrirla.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (isset($this->session) && is_object($this->session) && method_exists($this->session, 'userdata')) {
            $compId = $this->session->userdata('compId');
            if (is_array($compId)) {
                $compId = $compId['company_id'] ?? (reset($compId)['company_id'] ?? 0);
            } elseif (is_object($compId)) {
                $compId = $compId->company_id ?? 0;
            }
            $compId = (int) $compId;
            if ($compId > 0) {
                return $compId;
            }

            $company = $this->session->userdata('company');
            if (is_object($company) && (int) ($company->company_id ?? 0) > 0) {
                return (int) $company->company_id;
            }
            if (is_array($company) && (int) ($company['company_id'] ?? 0) > 0) {
                return (int) $company['company_id'];
            }
        }

        $fromHelper = 0;
        if (isset($this->Helper) && is_object($this->Helper)) {
            if (method_exists($this->Helper, 'resolveCompanyId')) {
                $fromHelper = (int) $this->Helper->resolveCompanyId();
            } else {
                $fromHelper = (int) ($this->Helper->company_id ?? 0);
            }
        }

        return $fromHelper > 0 ? $fromHelper : 0;
    }

    public function order_byqty($sales_bycategory) {

        if ($sales_bycategory !== null) {
            usort($sales_bycategory, function($a, $b) {
                return $b->qty_items - $a->qty_items;
            });

            return $sales_bycategory;
        }
    }

    public function get_DataVendor($sales_byVendor){
        if (! is_array($sales_byVendor) || count($sales_byVendor) === 0) {
            return [
                "all" => [],
                "otros_mount" => 0,
                "otros_mount_porcent" => 0,
                "total" => 0,
            ];
        }

        $column_total = array_column($sales_byVendor, "total");

        $total_amount_vendor = 0;
        $otros_mount = 0;
        $otros_mount_porcent = 0;


        foreach($column_total as $amount)
            $total_amount_vendor = $total_amount_vendor + $amount;

        foreach($sales_byVendor as $index => $value){
            $sales_byVendor[$index]->percent_amount = $total_amount_vendor
                ? ($value->total / $total_amount_vendor * 100)
                : 0;

            if ($index > 4) {
                $otros_mount = $otros_mount + $value->total;

            }
        }

        $otros_mount_porcent = $otros_mount?$otros_mount/$total_amount_vendor * 100:0;

        return [
            "all" => $sales_byVendor,
            "otros_mount" => $otros_mount,
            "otros_mount_porcent" => $otros_mount_porcent,
            "total" => $total_amount_vendor,

        ];
    }

    public function get_dataCustomer($sales_bycustomer){
        if (! is_array($sales_bycustomer) || count($sales_bycustomer) === 0) {
            return [
                "all" => [],
                "otros_mount" => 0,
                "otros_mount_porcent" => 0,
                "total" => 0,
                "otros_qty" => 0,
                "total_qty" => 0,
            ];
        }

        $column_total = array_column($sales_bycustomer, "total");
        $column_items = array_column($sales_bycustomer, "items");

        $total_amount_vendor = 0;
        $otros_mount = 0;
        $otros_qty = 0;
        $otros_mount_porcent = 0;
        $total_qty = 0;

        foreach($column_total as $amount)
            $total_amount_vendor = $total_amount_vendor + $amount;

        foreach($column_items as $qty)
            $total_qty = $total_qty + $qty;

        // echo $total_amount_vendor;
        foreach($sales_bycustomer as $index => $value){
            $sales_bycustomer[$index]->percent_amount = $total_amount_vendor
                ? ($value->total / $total_amount_vendor * 100)
                : 0;

            if ($index > 4) {
                $otros_mount = $otros_mount + $value->total;
                $otros_qty = $otros_qty + $value->items;
            }
        }

        $otros_mount_porcent = $otros_mount?$otros_mount/$total_amount_vendor * 100:0;

        return [
            "all" => $sales_bycustomer,
            "otros_mount" => $otros_mount,
            "otros_mount_porcent" => $otros_mount_porcent,
            "total" => $total_amount_vendor,
            "otros_qty" => $otros_qty,
            "total_qty" => $total_qty
        ];
    }

    public function get_datacategory($sales_bycategory){
        if (! is_array($sales_bycategory) || count($sales_bycategory) === 0) {
            return [
                "mount" => [],
                "qty" => [],
                "total_mount" => 0,
                "total_qty" => 0,
                "otros" => [
                    "otros_qty" => 0,
                    "otros_mount" => 0,
                    "otros_qty_porcent" => 0,
                    "otros_mount_porcent" => 0,
                ],
            ];
        }

        $column_total = array_column($sales_bycategory, "amount");
        $column_qty = array_column($sales_bycategory, "qty_items");

        $total_amount_category = 0;
        $total_qty_category = 0;

        foreach($column_total as $amount)
            $total_amount_category = $total_amount_category + $amount;

        foreach($column_qty as $qty)
            $total_qty_category = $total_qty_category + $qty;

        $otros_qty = 0;
        $otros_mount = 0;

        foreach($sales_bycategory as $index => $value){
            $sales_bycategory[$index]->percent_amount = $total_amount_category
                ? ($value->amount / $total_amount_category * 100)
                : 0;

            if ($index > 4) {
                $otros_mount = $otros_mount + $value->amount;
            }
        }

        $cont = 0;
        $categorias_ordenadas = $this->order_byqty($sales_bycategory);

        foreach($categorias_ordenadas as $index => $value){
            $categorias_ordenadas[$index]->percent_qty = $total_qty_category
                ? ($value->qty_items / $total_qty_category * 100)
                : 0;

            if ($index > 4) {
                $otros_qty = $otros_qty + $value->qty_items;
            }

        }

        //echo $total_qty_category;
        $otros_qty_porcent = $otros_qty?$otros_qty/$total_qty_category * 100:0;
        $otros_mount_porcent = $otros_mount?$otros_mount/$total_amount_category * 100:0;

        return [
            "mount" => $sales_bycategory,
            "qty" => $categorias_ordenadas,
            "total_mount" => $total_amount_category,
            "total_qty" => $total_qty_category,
            "otros" => [
                "otros_qty" => $otros_qty,
                "otros_mount" => $otros_mount,
                "otros_qty_porcent" => $otros_qty_porcent,
                "otros_mount_porcent" => $otros_mount_porcent
            ]

        ];
    }

    public function get_filter($request){

        $request = $this->Helper->cast($request);
        $api = $this->fetchFilterDataFromApi($request);

        if ($api === null) {
            return $this->emptyFilterResponse($request);
        }

        $salesByVendor = $this->rowsToObjects($api['sales_by_vendor'] ?? []);
        $salesByCustomer = $this->rowsToObjects($api['sales_by_customer'] ?? []);
        $salesByCategory = $this->rowsToObjects($api['sales_by_category'] ?? []);

        return [
                "sales_byvendor" => $this->get_DataVendor($salesByVendor),
                "top10_productos" => $this->rowsToObjects($api['top10_products'] ?? []),
                "sales_bycategory" => $this->get_datacategory($salesByCategory),
                "sales_bycustomer" => $this->get_dataCustomer($salesByCustomer),
                "sales_total" => $this->rowsToObjects($api['sales_total'] ?? []),
                "sales_type" => [
                    "web" => $this->rowsToObjects($api['sales_type']['web'] ?? []),
                    "app" => $this->rowsToObjects($api['sales_type']['app'] ?? []),
                    "manual" => $this->rowsToObjects($api['sales_type']['manual'] ?? []),
                    "erp_import" => $this->rowsToObjects($api['sales_type']['erp_import'] ?? []),
                ],
                "qty_products" => $this->qty_products($request),
                "send_catalogs_qty"=>$this->send_catalogs($request),
                "send_catalogs_noread"=>$this->send_catalogs_bystatus($request, 1),
                "send_catalogs_open"=>$this->send_catalogs_bystatus($request, 3),
                "send_catalogs_confirmed"=>$this->send_catalogs_bystatus($request, 4),
                "send_catalogs_reads"=>$this->send_catalogs_bystatus($request, 2),
                "data_forchart" => $this->buildDataChartFromMonthly($api['monthly_sales'] ?? [])
                ];
    }

    protected function fetchFilterDataFromApi($request): ?array
    {
        $compId = $this->resolveCompanyId();
        if ($compId < 1) {
            log_message('error', 'Dashboard filter-data: company_id no disponible en sesión');

            return null;
        }

        $params = $this->dashboardDateParams($request);
        $cacheKey = 'dashfilter:'.$compId.':'.md5(json_encode($params));
        $CI = function_exists('get_instance') ? get_instance() : null;
        if (is_object($CI) && method_exists($CI, 'cachedApiValue')) {
            $response = $CI->cachedApiValue($cacheKey, 45, function () use ($compId, $params) {
                return $this->getDataFromApi("company/{$compId}/dashboard/filter-data", $params, true);
            });
        } else {
            $response = $this->getDataFromApi("company/{$compId}/dashboard/filter-data", $params, true);
        }

        if ($this->hasError($response) || ! is_array($response)) {
            log_message('error', 'Dashboard filter-data API failed: ' . json_encode($response));

            return null;
        }

        return $response;
    }

    /**
     * @param mixed $rows
     * @return array<int, object>
     */
    protected function rowsToObjects($rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = is_object($row) ? $row : (object) $row;
        }

        return $out;
    }

    protected function emptyFilterResponse($request): array
    {
        $defaultTypeRow = [(object) ['total' => 0, 'qty_orders' => 0]];

        return [
            'sales_byvendor' => ['all' => [], 'otros_mount' => 0, 'otros_mount_porcent' => 0, 'total' => 0],
            'top10_productos' => [],
            'sales_bycategory' => [
                'mount' => [],
                'qty' => [],
                'total_mount' => 0,
                'total_qty' => 0,
                'otros' => [
                    'otros_qty' => 0,
                    'otros_mount' => 0,
                    'otros_qty_porcent' => 0,
                    'otros_mount_porcent' => 0,
                ],
            ],
            'sales_bycustomer' => [
                'all' => [],
                'otros_mount' => 0,
                'otros_mount_porcent' => 0,
                'total' => 0,
                'otros_qty' => 0,
                'total_qty' => 0,
            ],
            'sales_total' => $defaultTypeRow,
            'sales_type' => ['app' => $defaultTypeRow, 'web' => $defaultTypeRow, 'manual' => $defaultTypeRow, 'erp_import' => $defaultTypeRow],
            'qty_products' => $this->qty_products($request),
            'send_catalogs_qty' => $this->send_catalogs($request),
            'send_catalogs_noread' => $this->send_catalogs_bystatus($request, 1),
            'send_catalogs_open' => $this->send_catalogs_bystatus($request, 3),
            'send_catalogs_confirmed' => $this->send_catalogs_bystatus($request, 4),
            'send_catalogs_reads' => $this->send_catalogs_bystatus($request, 2),
            'data_forchart' => ['all' => [], 'total' => 0],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $monthly
     * @return array{all: array<int, array<string, mixed>>, total: float|int}
     */
    protected function buildDataChartFromMonthly(array $monthly): array
    {
        $format_date = $this->Helper->format_date();
        if (! is_object($format_date)) {
            $format_date = (object) ['month_year' => 'M-Y', 'format_php' => fv_display_date_format()];
        } elseif (! isset($format_date->month_year)) {
            $format_date->month_year = 'M Y';
        }
        $chart = [];
        $total_mount = 0;

        foreach ($monthly as $month) {
            $mount = (float) ($month['total'] ?? 0);
            $total_mount += $mount;
            $chart[] = [
                'start_date' => date($format_date->month_year, strtotime((string) ($month['start_date'] ?? ''))),
                'end_date' => date($format_date->month_year, strtotime((string) ($month['end_date'] ?? ''))),
                'filter' => [
                    'sales_total' => [(object) [
                        'total' => $mount,
                        'qty_orders' => (int) ($month['qty_orders'] ?? 0),
                        'percent_amount' => 0,
                    ]],
                ],
            ];
        }

        foreach ($chart as $key => $value) {
            $mount = (float) ($value['filter']['sales_total'][0]->total ?? 0);
            $chart[$key]['filter']['sales_total'][0]->percent_amount = $total_mount != 0
                ? ($mount / $total_mount) * 100
                : 0;
        }

        return [
            'all' => $chart,
            'total' => $total_mount,
        ];
    }

    public function qty_products(){

        //$query = "SELECT o.order_number, SUM(o.total_amount) FROM orders AS o WHERE o.company_id = 3";
    }

    public function send_catalogs_bystatus($request, $status){
        return $this->fetchSendCatalogStats($request, (int) $status);
    }

    public function send_catalogs($request){
        return $this->fetchSendCatalogStats($request, null);
    }

    protected function fetchSendCatalogStats($request, ?int $statusCartCod){
        $compId = $this->resolveCompanyId();
        if ($compId < 1) {
            return [(object) ['total_send_catalog' => 0]];
        }

        $params = $this->dashboardDateParams($request);
        if ($statusCartCod !== null) {
            $params['status_cart'] = $statusCartCod;
        }

        $cacheKey = 'dashcat:'.$compId.':'.md5(json_encode($params));
        $CI = function_exists('get_instance') ? get_instance() : null;
        if (is_object($CI) && method_exists($CI, 'cachedApiValue')) {
            $response = $CI->cachedApiValue($cacheKey, 45, function () use ($compId, $params) {
                return $this->getObjectFromApi(
                    "catalogcart/company/{$compId}/dashboard/send-catalog-count",
                    $params
                );
            });
        } else {
            $response = $this->getObjectFromApi(
                "catalogcart/company/{$compId}/dashboard/send-catalog-count",
                $params
            );
        }

        $total = 0;
        if (is_object($response) && isset($response->total_send_catalog)) {
            $total = (int) $response->total_send_catalog;
        } elseif (is_array($response) && isset($response['total_send_catalog'])) {
            $total = (int) $response['total_send_catalog'];
        }

        return [(object) ['total_send_catalog' => $total]];
    }

    protected function dashboardDateParams($request){
        $params = [];
        if (! is_object($request)) {
            return $params;
        }

        $fechaDesde = $request->fecha_desde_pordefecto ?? null;
        if ($fechaDesde === null || $fechaDesde === '') {
            // Alinear con el select UI (mes actual seleccionado por defecto).
            $fechaDesde = date('Y-m-01');
        }
        $params['fecha_desde_pordefecto'] = $fechaDesde;

        if (isset($request->desde_fecha)) {
            $params['desde_fecha'] = $request->desde_fecha;
        }
        if (isset($request->hasta_fecha)) {
            $params['hasta_fecha'] = $request->hasta_fecha;
        }

        return $params;
    }
}
