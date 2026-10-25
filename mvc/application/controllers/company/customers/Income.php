<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Income extends Company_Controller
{
    use IsAction;

    public function run($customer_id = null) {
        $customerIdFromUri = $this->uri->segment(4);
        $customerId = null;
        if (is_numeric((string) $customer_id) && (int) $customer_id > 0) {
            $customerId = (int) $customer_id;
        } elseif (is_numeric((string) $customerIdFromUri) && (int) $customerIdFromUri > 0) {
            $customerId = (int) $customerIdFromUri;
        }

        $customer = null;
        if ($customerId !== null) {
            $customer = $this->getObjectFromApi("customer/{$customerId}", [
                'props' => [
                    'customer_id',
                    'cust_id_kor',
                    'business_name',
                    'business_name_extended',
                    'name',
                    'email',
                    'phone',
                    'cell_phone',
                    'customer_status',
                    'group_id',
                ],
                'relations' => ['customer_group', 'sellers.user'],
            ]);
            if (!is_object($customer) || isset($customer->error)) {
                $customer = null;
            }
        }

        $customerGroup = null;
        if (isset($customer)) {
            $customerGroup = $customer->customer_group ?? $customer->customerGroup ?? null;
        }

        $customerData = [
            'customer_id' => $customer->customer_id ?? $customerId ?? '',
            'cust_id_kor' => $customer->cust_id_kor ?? '',
            'business_name' => $customer->business_name_extended ?? $customer->business_name ?? '',
            'contact' => $customer->name ?? $customer->contact_name ?? '',
            'email' => $customer->email ?? '',
            'phone' => $customer->phone ?? '',
            'cell_phone' => $customer->cell_phone ?? '',
            'group_name' => $customerGroup->name ?? '',
            'customer_status' => isset($customer->customer_status) ? (string) $customer->customer_status : '',
            'sellers' => [],
        ];

        if (isset($customer) && isset($customer->sellers) && (is_array($customer->sellers) || is_object($customer->sellers))) {
            foreach ((array) $customer->sellers as $seller) {
                if (isset($seller->user->name) && trim((string) $seller->user->name) !== '') {
                    $customerData['sellers'][] = (string) $seller->user->name;
                }
            }
        }

        $this->showContent('company/customer/customer_income', [
            'pageTexts' => $this->getPageTexts(),
            'customerData' => $customerData,
            'totalordersval' => [],
            'totorderquantity' => 0,
            'avgsaleprice' => 0,
            'avgvaluepermonth' => 0,
            'totorderpricevalue' => 0,
        ]);
    }

    protected function getPageTexts(): array
    {
        $langCustomers = (array) ($this->getModuleLang('cmp-clientes') ?? []);
        $langProfile = (array) ($this->getModuleLang('cmp-perfil') ?? []);
        $langEnabled = (array) ($this->getModuleLang('cmp-enabled') ?? []);

        return [
            'title' => $langCustomers['view_income'] ?? '',
            'back' => $langProfile['back'] ?? '',
            'income_customer_info' => $langCustomers['income_customer_info'] ?? '',
            'income_customer_id' => $langCustomers['income_customer_id'] ?? '',
            'income_erp_id' => $langCustomers['field_cust_id_kor'] ?? '',
            'income_business_name' => $langCustomers['nombre_empresa'] ?? '',
            'income_contact' => $langCustomers['nombre'] ?? '',
            'income_email' => $langCustomers['email'] ?? '',
            'income_phone' => $langCustomers['phones'] ?? '',
            'income_cell_phone' => $langCustomers['col_cellphone'] ?? '',
            'income_group' => $langCustomers['group_customer'] ?? '',
            'income_sellers' => $langCustomers['vendedores'] ?? '',
            'income_customer_status' => $langCustomers['col_customer_status'] ?? '',
            'income_no_data' => $langCustomers['option_vacio'] ?? '',
            'income_month' => $langCustomers['income_month'] ?? '',
            'income_value' => $langCustomers['income_value'] ?? '',
            'income_quantity' => $langCustomers['income_quantity'] ?? '',
            'income_total_sales' => $langCustomers['income_total_sales'] ?? '',
            'income_total_average_sale' => $langCustomers['income_total_average_sale'] ?? '',
            'income_total_average_month' => $langCustomers['income_total_average_month'] ?? '',
            'income_total_amount' => $langCustomers['income_total_amount'] ?? '',
            'income_status_enabled' => $langEnabled['option_enable'] ?? '',
            'income_status_disabled' => $langEnabled['option_disable'] ?? '',
        ];
    }

    //OLD CUSTOMER INCOME
//    public function income($customer_id = false)
//    {
//        $compId = $this->session->userdata('compId');
//        if ($customer_id == false)
//        {
//            show_404();
//        } elseif ($this->mymodel->count('customers', ['customer_id'=>$customer_id]) != 1)
//        {
//            show_404();
//        } else {
//
//            $data = array(
//                'title' => 'View Customer Income Details',
//                'page' => 'customer',
//                'subpage' => 'customerlist'
//            );
//
//            $data['data'] =$customerInfo= $this->mymodel->get('customers', true, 'customer_id', $customer_id);
//            $totalorders=$this->mymodel->fetch("SELECT * FROM orders AS ord JOIN order_details AS od ON ord.order_id=od.order_id  WHERE ord.customer_id='$customer_id' AND ord.company_id='$compId' AND ord.payment_status='1'");
//            $totvalue=0;
//            $totqty=0;
//            foreach ($totalorders as $totorders) {
//                $totqty+=$totorders->qty;
//                $totvalue+=$totorders->qty * $totorders->sale_price;
//
//            }
//            $data['totorderpricevalue']=round($totvalue);
//            if($totvalue !=0){
//                $avgsaleprice=round($totvalue/count($totalorders));
//                $data['avgsaleprice']= $avgsaleprice;
//            }else{
//                $data['avgsaleprice']= 0;
//            }
//
//            $data['totorderquantity']=$totqty;
//            $pricepermonth=$this->mymodel->fetch("SELECT year(od.created), month(od.created), sum(ord.sale_price*ord.qty) as total FROM order_details AS ord JOIN orders AS od ON ord.order_id=od.order_id WHERE od.customer_id='$customer_id' AND od.payment_status='1' group by year(od.created), month(od.created)");
//            if(count($pricepermonth) !=0){
//                $pprice=0;
//                foreach ($pricepermonth as $pmonth) {
//                    $pprice+=($pmonth->total)/count($pricepermonth);
//
//                }
//                $data['avgvaluepermonth']=round($pprice);
//            }else{
//                $data['avgvaluepermonth']=0;
//            }
//
//            $langId = $this->session->userdata('language_id');
//            $data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
//            $this->load->view('company/header', $data);
//            $this->load->view('company/customer_income');
//            $this->load->view('company/footer');
//        }
//    }
}
