<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');



/**
 * @property CI_Email $email
 */
class HelperEmail extends CI_Model {

    public $company = array();
    function __construct()
	{
		parent::__construct();
        $this->load->model('Helper');
        $this->load->model('HelperLanguage');
        $this->company = $this->Helper->get_company_info();
        $this->load->model("CustomerModel");
        $this->load->model("SellerModel");
    }
    public function get_template($request){

        $request = $this->Helper->cast($request);

        $lang = $this->HelperLanguage->catalog();
        $link_catalog = catalog_public_browse_url((string) ($request->token ?? ''));
        $etiq_link = "<a href='".$link_catalog."'>". $lang['enlace'] ."</a>";

        $customer = $this->CustomerModel->getCustomer($request->customer_id);
		$customer_name = $customer->business_name. ' ('. $customer->name . ')';
        $seller = $customer->assign_catalog!=null?$this->SellerModel->get_seller($customer->assign_catalog):$this->SellerModel->get_default();

        $companyLogoSrc = company_logo_url((string) ($this->company->image ?? ''));
        if ($companyLogoSrc === '') {
            $companyLogoSrc = base_url('uploads/noimg.png');
        }
        $htmlContent = "
        <body style='background-color:#e2e1e0;font-family: Open Sans, sans-serif;font-size:100%;font-weight:400;line-height:1.4;color:#000;'>
        <table style='max-width:600px;margin:20px auto 10px;background-color:#fff;padding:20px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;-webkit-box-shadow:0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24);-moz-box-shadow:0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24);box-shadow:0 1px 3px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24); border-top: solid 10px #000000;'>
        <thead>
            <tr>
                <th style='text-align:left;'><img style='max-width: 150px;' src='".$companyLogoSrc."' alt='".$this->company->name."'></th>
                <th style='text-align:right;font-weight:400;'>"; $htmlContent .= "</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan='2' style='height:2px;background:#232323'></td>
            </tr>
            <tr>
                <td colspan='2' style='padding:20px 0'><h3 style='margin;0;color:green'>". sprintf($lang['saludo'], $customer_name)  ."</h3>
                    <p>". $lang['asi_son'] ."</p>
                    <p>" . sprintf($lang['podras'], $etiq_link) .  "</p>
                </td>
            </tr>
        <tr>
        <td style='width:50%;padding:0;vertical-align:top'>
        <br>
        ". $lang['gracias'] ."<br>";

        if ($seller) {

            $htmlContent .= "<strong>". $lang['seller'] .": </strong>". $seller->first_name . ' '. $seller->last_name ."<br>
            <strong>". $lang['phone'] .": </strong>". $seller->phone_number ."<br>
            <strong>Email: </strong>". $seller->email ."<br><br>";
        }

        $htmlContent .="</td></tr><tr>
                        <td colspan='2' style='padding: 10px 10px; text-align: center; font-size: 11px; border-top: 1px solid #595959; border-bottom: 1px solid #595959;'>". $lang['mensaje_automatizado'] ."
                        </td>
                        </tr>
                        </tbody>
                        </table>
                        </body>
                        ";
        return $htmlContent;
    }

}
