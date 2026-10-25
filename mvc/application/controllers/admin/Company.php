<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends MY_Controller {

    /** @var int */
    private $lastCreatedCompanyId = 0;

    /** @var string */
    private $lastCompanyApiError = '';

    /** @var string */
    private $lastManagerApiError = '';

    public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
		$this->load->helper('admin_shell');
	}


	public function add()
	{
		$data = array(
			'title' => admin_lang_string('cmp-admin-company', 'form_add_title', 'Add new company'),
			'page' => 'company',
			'loadCompanyFormScripts' => true,
		);
		$data['formData'] = [];
		$this->populateCompanyFormCommonData($data);

		$this->load->view('admin/header', $data);
		$this->load->view('admin/company_add', $data);
		$this->load->view('admin/footer', $data);
	}


	public function create()
	{
        $request = $this->input->post();
		$createdOk = false;
		$missingRequired = ! $this->input->post('name');
		if ($missingRequired) {
			$text = $this->adminLangString(
				'cmp-admin-company',
				'required_name',
				'Company name is required.'
			);
			$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			$this->renderCompanyAddForm($request);
			return;
		}

		if (trim((string) $this->input->post('password')) === '') {
			$text = $this->adminLangString(
				'cmp-admin-company',
				'manager_password_required',
				'Password is required to create the company manager user.'
			);
			$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			$this->renderCompanyAddForm($request);
			return;
		}

		$plainPassword = (string) $this->input->post('password');
		if (strlen($plainPassword) < 8) {
			$text = $this->adminLangString(
				'cmp-admin-company',
				'manager_password_min_length',
				'The manager password must be at least 8 characters.'
			);
			$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			$this->renderCompanyAddForm($request);
			return;
		}

		$cname = $this->input->post('cname') ?: [];
		$cemail = $this->input->post('cemail') ?: [];
		if (! is_array($cname)) {
			$cname = [];
		}
		if (! is_array($cemail)) {
			$cemail = [];
		}
		$contactsErr = $this->validateCompanyContactsEmails($cname, $cemail);
		if ($contactsErr !== null) {
			$this->flashMessage($contactsErr);
			$this->renderCompanyAddForm($request);
			return;
		}

		if ($this->input->post('name'))
		{
			$hasImageUpload = false;
			$uploadTmpPath = '';
			$uploadOrigName = '';

			if (! empty($_FILES['images']['name'])) {
				$uploadErr = (int) ($_FILES['images']['error'] ?? UPLOAD_ERR_NO_FILE);
				if ($uploadErr !== UPLOAD_ERR_OK) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_image_upload_failed',
						'The company image could not be uploaded to storage.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}
				$uploadTmpPath = (string) ($_FILES['images']['tmp_name'] ?? '');
				if ($uploadTmpPath === '' || ! is_uploaded_file($uploadTmpPath)) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_image_upload_failed',
						'The company image could not be uploaded to storage.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}
				$hasImageUpload = true;
				$uploadOrigName = (string) ($_FILES['images']['name'] ?? 'image.png');
			}

				$mydata = array(
					'language_id'=> $this->testInput($this->input->post('language_id')),
					'name' => $this->testInput($this->input->post('name')),
					'short_name' => $this->testInput($this->input->post('short_name')),
					'email' => $this->testInput($this->input->post('email')),
					'password' =>$this->enc_password($this->input->post('password')),
					'phone_number' => $this->testInput($this->input->post('phone_number')),
					'cell_number' => $this->testInput($this->input->post('cell_number')),
					'website' => $this->testInput($this->input->post('website')),
					'currency'=> $this->testInput($this->input->post('currency')),
					'max_number_of_clients' => $this->testInput($this->input->post('max_number_of_clients')),
					'max_number_of_orders' => $this->testInput($this->input->post('max_number_of_orders')),
					'max_number_of_vendors' => $this->testInput($this->input->post('max_number_of_vendors')),
					'total_price' => $this->testInput($this->input->post('total_price')),
					'status' => 1,
					'created'=> date('Y-m-d H:i:s'),
					'sell_with_inventory' => $this->testInput($this->input->post('sell_with_inventory')),
					'show_inventory' => $this->testInput($this->input->post('show_inventory')),
					'show_semaforo_stock' => $this->testInput($this->input->post('show_semaforo_stock')),
				);
				$this->mergeCompanyAddressFromPost($mydata);
				$this->mergeCompanyCategoryColumn($mydata, $this->input->post('category_id'));
				$this->mergeCompanyWhatsappFromPost($mydata);

				$slug = $this->testInput($this->input->post('name'));
				if (empty($slug) || $slug == '') {
					$slug = $this->testInput($this->input->post('name'));
				}
				$slug = strtolower(url_title($slug));
				$mydata['slug_url'] = $this->resolveUniqueCompanySlugViaApi($slug);
				$mydata['createdby_comp_id'] = null;

				if (trim((string) $mydata['email']) === '') {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'manager_email_required',
						'Email is required to create the company manager user.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}

				if ($this->companyFieldExistsViaApi('email', (string) $mydata['email'])) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'email_already_registered',
						'This email is already registered with us.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}
				if ($this->companyFieldExistsViaApi('phone', (string) ($mydata['phone_number'] ?? ''))) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'phone_already_registered',
						'This phone number is already registered with us.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}
				if ($this->companyFieldExistsViaApi('name', (string) $mydata['name'])) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_name_already_registered',
						'This company name is already registered.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}

				$companyPayload = $this->filterCompanyRowForApi($mydata);
				$compId = $this->createCompanyViaApi($companyPayload);
				$msg = $compId > 0 ? 4 : 3;

				if ($msg === 4) {
					$managerResp = $this->createManagerUserViaApi($compId, [
						'name' => $mydata['name'],
						'email' => $mydata['email'],
						'password' => $plainPassword,
						'language_id' => (int) $mydata['language_id'],
						'phone' => ($mydata['phone_number'] ?? '') !== '' ? $mydata['phone_number'] : null,
						'cellphone' => ($mydata['cell_number'] ?? '') !== '' ? $mydata['cell_number'] : null,
					]);
					if (! $managerResp) {
						$msg = 5;
					} elseif (! $this->syncManagerUserPasswordViaApi(
						$compId,
						$plainPassword,
						(string) $mydata['email'],
						(string) $mydata['name']
					)) {
						log_message('error', 'admin_company.create manager password sync failed company_id=' . $compId);
					}
					if ($msg === 4 && $hasImageUpload && $uploadTmpPath !== '') {
						$storedImage = $this->uploadCompanyImageToMinioApi($compId, $uploadTmpPath, $uploadOrigName);
						if ($storedImage === null) {
							$msg = 7;
						} else {
							$this->updateCompanyViaApi($compId, ['image' => $storedImage]);
						}
					}
				}

				if($msg==3){
					if ($this->lastCompanyApiError !== '') {
						$detailLower = strtolower($this->lastCompanyApiError);
						if (str_contains($detailLower, 'unauthorized')) {
							$detail = $this->adminLangString(
								'cmp-admin-company',
								'create_failed_not_admin',
								'Your session is not authorized to create companies. Please sign in again at the administrator login.'
							);
						} else {
							$template = $this->adminLangString(
								'cmp-admin-company',
								'create_failed_api',
								'Could not create company: %s'
							);
							$detail = sprintf($template, $this->lastCompanyApiError);
						}
					} else {
						$detail = $this->adminLangString(
							'cmp-admin-company',
							'create_failed',
							'The company could not be created. Please verify the form data.'
						);
					}
					$this->flashMessage(json_encode([$detail, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}else if($msg==5){
					if ($this->lastManagerApiError !== '') {
						$template = $this->adminLangString(
							'cmp-admin-company',
							'manager_user_create_failed_api',
							'The company was created but the manager user could not be created: %s'
						);
						$detail = sprintf($template, $this->lastManagerApiError);
					} else {
						$detail = $this->adminLangString(
							'cmp-admin-company',
							'manager_user_create_failed',
							'Could not create the company manager user. Please try again or use another email.'
						);
					}
					if ($compId > 0) {
						$partialTemplate = $this->adminLangString(
							'cmp-admin-company',
							'company_created_manager_failed',
							'Company #%d was created. You can edit it to assign a manager manually.'
						);
						$detail .= ' '.sprintf($partialTemplate, $compId);
					}
					$this->flashMessage(json_encode([$detail, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;
				}else if($msg==7){
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_image_upload_failed',
						'The company image could not be uploaded to storage.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					$this->renderCompanyAddForm($request);
					return;

				}else if($msg==4 && $compId > 0){

                    $cname = $this->input->post('cname') ?: [];
                    $cemail = $this->input->post('cemail') ?: [];
                    $cwebsite = $this->input->post('cwebsite') ?: [];
                    $cphone = $this->input->post('cphone') ?: [];
                    $ccphone = $this->input->post('ccphone') ?: [];
                    if (! is_array($cname)) {
                        $cname = [];
                    }
                    if (! is_array($cemail)) {
                        $cemail = [];
                    }

                    $productsUploadDir = (defined('FCPATH') ? FCPATH : './') . 'uploads/products/' . $compId;
                    if (! is_dir($productsUploadDir)) {
                        mkdir($productsUploadDir, 0755, true);
                    }
                    $this->initCompanyDefaultsViaApi($compId, $plainPassword);

                    if (!empty(array_filter($cname))) {
                        $contacts = [];
                        for($i=0;$i<count($cname);$i++)
                        {
							if (trim((string) ($cname[$i] ?? '')) === '') {
								continue;
							}
                            $contacts[] = array(
                                'company_id' => $compId,
                                'name'=>$this->testInput($cname[$i]),
                                'email'=>$this->testInput(isset($cemail[$i]) ? (string) $cemail[$i] : ''),
                                'website'=>$this->testInput(isset($cwebsite[$i]) ? (string) $cwebsite[$i] : ''),
                                'phone'=>$this->testInput(isset($cphone[$i]) ? (string) $cphone[$i] : ''),
                                'cell_phone'=>$this->testInput(isset($ccphone[$i]) ? (string) $ccphone[$i] : ''));
                        }
                        $this->replaceCompanyContactsViaApi($compId, $contacts);
                    }

                    $data['emailData']=$mydata['email'];
                    $data['password']=$this->input->post('password');
                    $data['companyName']=$this->input->post('name');
                    $data['registerEmailLang'] = $this->adminCompanyFormLang();

                    $subject = $this->adminLangString(
						'cmp-admin-company',
						'email_register_subject',
						'Registered with Full Vendor | Full Vendor'
					);
                    $htmlContent = $this->load->view('admin/register_email_template', $data, TRUE);
                    $email=$mydata['email'];

                    $this->saveInitialOrderNumberViaApi($request['initial_order_number'] ?? null, $compId);

                    $stripeWarn = $this->trySyncStripeCustomerAfterCompanyCreate($compId);
                    $successText = $this->adminLangString(
						'cmp-admin-company',
						'company_created_success',
						'Company created successfully.'
					);
                    $this->flashMessage(json_encode([$successText, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
                    if ($stripeWarn !== '') {
                        $this->flashMessage(json_encode([$stripeWarn, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
                    }
                    FullvendorEmailHelper::sendMailFromNoReply($email, $subject, $htmlContent);
					$createdOk = true;
                }
		}

		if (! $createdOk) {
			$text = $this->adminLangString(
				'cmp-admin-company',
				'create_failed',
				'The company could not be created. Please verify the form data.'
			);
			$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			$this->renderCompanyAddForm($request);
			return;
		}

        redirect(admin_url('company/lists'),'refresh');
	}

	private function renderCompanyAddForm(array $formData = []): void
	{
		$data = array(
			'title' => admin_lang_string('cmp-admin-company', 'form_add_title', 'Add new company'),
			'page' => 'company',
			'loadCompanyFormScripts' => true,
		);
		$data['formData'] = $formData;
		$this->populateCompanyFormCommonData($data);

		$this->load->view('admin/header', $data);
		$this->load->view('admin/company_add', $data);
		$this->load->view('admin/footer', $data);
	}

	public function lists()
	{
		$data = array(
			'title' => $this->adminLangString(
				'cmp-admin-company',
				'company_list_page_title',
				'Companies'
			),
			'page' => 'company',
			'subpage' => 'companylist'
		);

		$list = $this->fetchCompaniesForAdminList();

		$data['list'] = $list;
		$data['companyListLang'] = [
			'addCompany' => $this->adminLangString('cmp-admin-company', 'company_list_add', 'Add company'),
			'colImage' => $this->adminLangString('cmp-admin-company', 'company_list_col_image', 'Image'),
			'colName' => $this->adminLangString('cmp-admin-company', 'company_list_col_name', 'Company name'),
			'colEmail' => $this->adminLangString('cmp-admin-company', 'company_list_col_email', 'Email'),
			'colPhone' => $this->adminLangString('cmp-admin-company', 'company_list_col_phone', 'Phone'),
			'colStripe' => $this->adminLangString('cmp-admin-company', 'company_list_col_stripe', 'Stripe'),
			'colWhatsapp' => $this->adminLangString('cmp-admin-company', 'company_list_col_whatsapp', 'WhatsApp'),
			'colWhatsappInstanceName' => $this->adminLangString('cmp-admin-company', 'company_list_col_whatsapp_instance_name', 'Instance name'),
			'colWhatsappApikey' => $this->adminLangString('cmp-admin-company', 'company_list_col_whatsapp_apikey', 'WhatsApp API key'),
			'colCreated' => $this->adminLangString('cmp-admin-company', 'company_list_col_created', 'Created'),
			'colStatus' => $this->adminLangString('cmp-admin-company', 'company_list_col_status', 'Status'),
			'colActions' => $this->adminLangString('cmp-admin-company', 'company_list_col_actions', 'Actions'),
			'actionView' => $this->adminLangString('cmp-admin-company', 'company_list_action_view', 'View'),
			'actionEdit' => $this->adminLangString('cmp-admin-company', 'company_list_action_edit', 'Edit'),
			'actionDelete' => $this->adminLangString('cmp-admin-company', 'company_list_action_delete', 'Delete'),
			'statusSwitchAria' => $this->adminLangString('cmp-admin-company', 'company_list_status_switch_aria', 'Enable or disable this company'),
			'statusError' => $this->adminLangString('cmp-admin-company', 'company_status_update_error', 'The status could not be updated. Please try again.'),
		];
		$data['companyStatusUrl'] = admin_url('company/changeStatus');
		$data['adminCountryPhoneMeta'] = admin_build_country_phone_meta_map($this->fetchAdminCountriesList());
		$pendingStripeCount = $this->fetchPendingStripeCountFromApi();
		$data['stripeBulkSyncUi'] = [
			'enabled' => (bool) ($this->companiesCapabilities()['hasStripeId'] ?? false),
			'pendingCount' => $pendingStripeCount,
			'endpointUrl' => admin_url('company/stripe_sync_all_pending'),
			'buttonLabel' => $this->adminLangString('cmp-admin-company', 'btn_stripe_sync_all_pending', 'Sync Stripe (pending)'),
			'confirmTitle' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_confirm_title', 'Sync pending companies with Stripe?'),
			'confirmText' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_confirm_text', 'This will create a Stripe customer for every company that does not yet have a Stripe ID.'),
			'confirmYes' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_confirm_yes', 'Yes, sync now'),
			'confirmNo' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_confirm_no', 'Cancel'),
			'runningText' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_running', 'Syncing companies with Stripe...'),
			'doneTitle' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_done_title', 'Stripe sync finished'),
			'errorTitle' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_error_title', 'Could not sync with Stripe'),
			'noneTitle' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_result_none_pending', 'There are no pending companies to sync.'),
			'errorsLabel' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_result_errors_label', 'Errors'),
			'processedLabel' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_result_processed', 'Processed'),
			'updatedLabel' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_result_updated', 'Updated'),
		];
		$data['companyDeleteUi'] = [
			'confirmTitle' => $this->adminLangString(
				'cmp-admin-company',
				'delete_confirm_title',
				'Delete this company?'
			),
			'confirmText' => $this->adminLangString(
				'cmp-admin-company',
				'delete_confirm_text',
				'The company will be marked as deleted. This cannot be undone from here.'
			),
			'confirmYes' => $this->adminLangString(
				'cmp-admin-company',
				'delete_confirm_yes',
				'Yes, delete'
			),
			'confirmNo' => $this->adminLangString(
				'cmp-admin-company',
				'delete_confirm_no',
				'Cancel'
			),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/company_list');
		$this->load->view('admin/footer');
	}

	/**
	 * Sirve el logo para el panel admin: la petición va al MVC (mismo origen que el navegador)
	 * y aquí se obtiene la imagen desde la API usando API_SERVER (p. ej. host Docker "api"),
	 * que el navegador no puede resolver directamente.
	 */
	public function serve_company_logo($company_id = null): void
	{
		if ($company_id === null || $company_id === '') {
			show_404();

			return;
		}
		$company_id = (int) $company_id;
		if ($company_id < 1 || $this->fetchCompanyById($company_id) === null) {
			show_404();

			return;
		}
		$row = $this->fetchCompanyById($company_id);
		if (! $row || empty($row->image)) {
			show_404();

			return;
		}
		$image = trim((string) $row->image);

		if (function_exists('company_logo_is_api_image') && company_logo_is_api_image($image)) {
			$dot = strrpos($image, '.');
			if ($dot === false) {
				show_404();

				return;
			}
			$uuid = substr($image, 0, $dot);
			$ext = substr($image, $dot + 1);
			$server = function_exists('get_api_server') ? get_api_server() : 'localhost';
			$server = preg_replace('#^https?://#i', '', (string) $server);
			$server = rtrim($server, '/');
			if ($server === '' || $server === 'laravel.test') {
				$server = 'localhost';
			}
			$internalBase = getenv('API_INTERNAL_BASE_URL');
			if ($internalBase !== false && is_string($internalBase) && trim($internalBase) !== '') {
				$url = rtrim(trim($internalBase), '/') . '/api/v2/images/' . rawurlencode($uuid) . '.' . rawurlencode($ext);
			} else {
				$scheme = getenv('API_INTERNAL_SCHEME') !== false && getenv('API_INTERNAL_SCHEME') !== ''
					? preg_replace('#[^a-z]#i', '', strtolower((string) getenv('API_INTERNAL_SCHEME')))
					: 'http';
				if ($scheme !== 'http' && $scheme !== 'https') {
					$scheme = 'http';
				}
				$url = $scheme . '://' . $server . '/api/v2/images/' . rawurlencode($uuid) . '.' . rawurlencode($ext);
			}

			try {
				$reqOpts = [
					'timeout' => 20,
					'connect_timeout' => 8,
					'http_errors' => false,
				];
				$token = method_exists($this, 'getUserToken') ? $this->getUserToken() : null;
				if ($token !== null && $token !== '') {
					$reqOpts['headers'] = ['Authorization' => 'Bearer ' . $token];
				}
				$response = $this->getHttpClient()->get($url, $reqOpts);
				$code = $response->getStatusCode();
				if ($code >= 200 && $code < 300) {
					$body = $response->getBody()->getContents();
					$ct = $response->getHeaderLine('Content-Type');
					if ($ct !== '') {
						$this->output->set_content_type($ct);
					} else {
						$this->output->set_content_type('image/' . preg_replace('/[^a-z0-9]/i', '', $ext));
					}
					$this->output->set_output($body);

					return;
				}
				log_message('error', 'serve_company_logo HTTP ' . $code . ' url=' . $url);
			} catch (\Throwable $e) {
				log_message('error', 'serve_company_logo: ' . $e->getMessage());
			}
			show_404();

			return;
		}

		$path = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'company' . DIRECTORY_SEPARATOR . $image;
		if (is_readable($path)) {
			$mime = function_exists('mime_content_type') ? @mime_content_type($path) : '';
			if ($mime !== false && $mime !== '') {
				$this->output->set_content_type($mime);
			}
			$this->output->set_output(file_get_contents($path) ?: '');

			return;
		}

		show_404();
	}


	public function edit($company_id = false)
	{
		$cid = (int) $company_id;
		if ($cid < 1 || $this->fetchCompanyById($cid) === null) {
			show_404();

			return;
		}

		$data = array(
			'title' => $this->adminLangString('cmp-admin-company', 'form_edit_title', 'Edit company'),
			'page' => 'company',
			'subpage' => 'companylist',
			'loadCompanyFormScripts' => true,
		);
		$data['contactlist'] = $this->fetchCompanyContactsFromApi($cid);
		$data['data'] = $this->fetchCompanyById($cid);
		$this->populateCompanyFormCommonData($data, [], $cid);
		$data['company'] = $data['data'];

		$data['userlist'] = [];
		$usersApi = $this->getDataFromApi("company/{$cid}/users", [
			'page' => 1,
			'perPage' => 5000,
			'type' => 'manager',
		], true);
		if (is_array($usersApi) && empty($usersApi['error']) && isset($usersApi['data']) && is_array($usersApi['data'])) {
			foreach ($usersApi['data'] as $row) {
				$data['userlist'][] = is_array($row) ? (object) $row : $row;
			}
		} else {
			log_message('error', 'admin Company::edit users API: '.json_encode($usersApi));
		}
		$data['hasInitialOrderNumber'] = $this->fetchInitialOrderNumberFromApi($cid) !== null;
		$data['initialOrderNumber'] = $this->fetchInitialOrderNumberFromApi($cid);
		$data['nextOrderNumber'] = $this->fetchNextOrderNumberFromApi($cid);

		$this->load->library('company_plan_validator');
		$data['companyActivePlan'] = $this->company_plan_validator->getActivePlanForCompany($cid);

		if (function_exists('admin_lang_json_flat_section') && function_exists('admin_shell_lang_filename')) {
			$data['stripeCompanyLang'] = array_merge(
				admin_lang_json_flat_section('en-EN.json', 'cmp-admin-company'),
				admin_lang_json_flat_section(admin_shell_lang_filename(), 'cmp-admin-company'),
			);
		} else {
			$data['stripeCompanyLang'] = [];
		}

		$this->load->view('admin/header', $data);
		$this->load->view('admin/company_edit');
		$this->load->view('admin/footer', $data);
	}


	/**
	 * Crea el customer en Stripe vía API y persiste companies.stripe_id (no bloquea el alta).
	 */
	private function trySyncStripeCustomerAfterCompanyCreate(int $companyId): string
	{
		if ($companyId < 1 || ! ($this->companiesCapabilities()['hasStripeId'] ?? false)) {
			return '';
		}

		$resp = $this->post('stripe/companies/' . $companyId . '/sync-customer', []);
		if ($this->hasError($resp)) {
			$fallback = 'The company was created but Stripe customer ID could not be assigned. Use Sync Stripe customer on edit.';
			if (is_array($resp) && isset($resp['message']) && is_string($resp['message']) && trim($resp['message']) !== '') {
				return trim($resp['message']);
			}

			return $this->adminLangString('cmp-admin-company', 'msg_stripe_customer_on_create_failed', $fallback);
		}

		return '';
	}

	/**
	 * Sincroniza con Stripe todas las companies que aún no tienen stripe_id.
	 * Devuelve JSON con processed/updated/errors y el mensaje localizado del API.
	 */
	public function stripe_sync_all_pending(): void
	{
		$this->load->helper('admin_shell');

		header('Content-Type: application/json; charset=UTF-8');

		if (! ($this->companiesCapabilities()['hasStripeId'] ?? false)) {
			http_response_code(409);
			echo json_encode([
				'error' => true,
				'message' => $this->adminLangString('cmp-admin-company', 'stripe_bulk_error_title', 'Could not sync with Stripe'),
				'data' => ['processed' => 0, 'updated' => 0, 'errors' => []],
			], JSON_UNESCAPED_UNICODE);

			return;
		}

		$resp = $this->post('stripe/companies/sync-pending-customers', []);

		if ($this->hasError($resp)) {
			$msg = is_array($resp) && isset($resp['message']) && is_string($resp['message']) && trim($resp['message']) !== ''
				? trim($resp['message'])
				: $this->adminLangString('cmp-admin-company', 'msg_stripe_api_error', 'Error communicating with Stripe.');
			http_response_code(502);
			echo json_encode([
				'error' => true,
				'message' => $msg,
				'data' => ['processed' => 0, 'updated' => 0, 'errors' => []],
			], JSON_UNESCAPED_UNICODE);

			return;
		}

		$data = is_array($resp) && isset($resp['data']) && is_array($resp['data']) ? $resp['data'] : [];
		$processed = (int) ($data['processed'] ?? 0);
		$updated = (int) ($data['updated'] ?? 0);
		$errors = isset($data['errors']) && is_array($data['errors']) ? array_values($data['errors']) : [];

		$message = '';
		if (is_array($resp) && isset($resp['message']) && is_string($resp['message']) && trim($resp['message']) !== '') {
			$message = trim($resp['message']);
		}
		if ($message === '') {
			$template = $this->adminLangString(
				'cmp-stripe-customer',
				'msg_stripe_bulk_sync_result',
				'Stripe sync: %1$d updated of %2$d pending companies, %3$d error(s).'
			);
			$message = sprintf($template, $updated, $processed, count($errors));
		}

		echo json_encode([
			'error' => false,
			'message' => $message,
			'data' => [
				'processed' => $processed,
				'updated' => $updated,
				'errors' => $errors,
			],
		], JSON_UNESCAPED_UNICODE);
	}

	public function stripe_sync_customer($company_id = false): void
	{
		$this->load->helper('admin_shell');
		$cid = (int) $company_id;
		if ($cid < 1 || $this->fetchCompanyById($cid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/companies/' . $cid . '/sync-customer', []);
		$pt = function_exists('admin_lang_json_flat_section') && function_exists('admin_shell_lang_filename')
			? array_merge(
				admin_lang_json_flat_section('en-EN.json', 'cmp-admin-company'),
				admin_lang_json_flat_section(admin_shell_lang_filename(), 'cmp-admin-company'),
			)
			: [];
		$okKey = 'msg_stripe_customer_synced';
		if ($this->hasError($resp)) {
			$msg = is_array($resp) && isset($resp['message']) && is_string($resp['message'])
				? $resp['message']
				: ($pt['msg_stripe_api_error'] ?? 'Stripe error');
			$this->flashMessage(json_encode([$msg, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
		} else {
			$msg = is_array($resp) && isset($resp['message']) && is_string($resp['message']) && $resp['message'] !== ''
				? $resp['message']
				: ($pt[$okKey] ?? $okKey);
			$this->flashMessage(json_encode([$msg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
		}
		redirect(admin_url('company/edit/' . $cid), 'refresh');
	}

	public function update()
	{
		$request = $this->input->post();
        if ($this->input->post('name') && $this->input->post('company_id'))
		{
			$mydata =array();
			$company_id = (int) $this->input->post('company_id');
			$oldImage = $this->input->post('oldImage');

			$compdetl = $this->fetchCompanyById($company_id);
			if ($compdetl === null) {
				show_404();
			}
			if($this->input->post('password') !=""){
			   $newpass=$this->enc_password($this->input->post('password'));
			}else{
			    $newpass=@$compdetl->password;
			}

			$this->load->library('company_plan_validator');
			$activePlan = $this->company_plan_validator->getActivePlanForCompany((int) $company_id);
			$planClients = $activePlan && isset($activePlan->max_clients)
				? (int) $activePlan->max_clients
				: (int) ($compdetl->max_number_of_clients ?? 0);
			$planOrders = $activePlan && isset($activePlan->max_orders)
				? (int) $activePlan->max_orders
				: (int) ($compdetl->max_number_of_orders ?? 0);
			$planVendors = $activePlan && isset($activePlan->max_users)
				? (int) $activePlan->max_users
				: (int) ($compdetl->max_number_of_vendors ?? 0);

			$mydata = array(
				'language_id'=> $this->testInput($this->input->post('language_id')),
				'name' => $this->testInput($this->input->post('name')),
				'short_name' => $this->testInput($this->input->post('short_name')),
				'phone_number' => $this->testInput($this->input->post('phone_number')),
				'cell_number' => $this->testInput($this->input->post('cell_number')),
				'website' => $this->testInput($this->input->post('website')),
				'password' =>$newpass,
				'max_number_of_clients' => $planClients,
				'currency'=> $this->testInput($this->input->post('currency')),
				'max_number_of_orders' => $planOrders,
				'max_number_of_vendors' => $planVendors,
				'total_price' => $this->testInput($this->input->post('total_price')),
				'status' =>$this->testInput($this->input->post('status')),
				'sell_with_inventory' => $this->testInput($this->input->post('sell_with_inventory')),
				'show_inventory' => $this->testInput($this->input->post('show_inventory')),
				'show_semaforo_stock' => $this->testInput($this->input->post('show_semaforo_stock')),
			);
			$this->mergeCompanyAddressFromPost($mydata);
			$this->mergeCompanyCategoryColumn($mydata, $this->input->post('category_id'));
			$this->mergeCompanyWhatsappFromPost($mydata);

			$c = array();
			$cname = $this->input->post('cname') ?: [];
			$cemail = $this->input->post('cemail') ?: [];
			$cwebsite = $this->input->post('cwebsite') ?: [];
			$cphone = $this->input->post('cphone') ?: [];
			$ccphone = $this->input->post('ccphone') ?: [];
			if (! is_array($cname)) {
				$cname = [];
			}
			if (! is_array($cemail)) {
				$cemail = [];
			}
			$contactsErr = $this->validateCompanyContactsEmails($cname, $cemail);
			if ($contactsErr !== null) {
				$this->flashMessage($contactsErr);
				redirect(admin_url('company/edit/'.$company_id), 'refresh');

				return;
			}
			if (!empty(array_filter($cname))) {
				for ($i=0; $i < count($cname) ; $i++) {
					if(trim($cname[$i]) !="") {
						$compaddrs = array(
							'company_id' => $company_id,
							'name' => $this->testInput($cname[$i]),
							'email' => $this->testInput(isset($cemail[$i]) ? (string) $cemail[$i] : ''),
							'website' => $this->testInput(isset($cwebsite[$i]) ? (string) $cwebsite[$i] : ''),
							'phone' => $this->testInput(isset($cphone[$i]) ? (string) $cphone[$i] : ''),
							'cell_phone' => $this->testInput(isset($ccphone[$i]) ? (string) $ccphone[$i] : ''),
						);
						array_push($c, $compaddrs);
					}

				}
				$this->replaceCompanyContactsViaApi($company_id, $c);
			}

			if ($_FILES['image']['name'] != '')
			{
				$uploadErr = (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
				if ($uploadErr !== UPLOAD_ERR_OK) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_image_upload_failed',
						'The company image could not be uploaded to storage.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					redirect(admin_url('company/edit/'.$company_id), 'refresh');

					return;
				}
				$tmp = (string) ($_FILES['image']['tmp_name'] ?? '');
				if ($tmp === '' || ! is_uploaded_file($tmp)) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_image_upload_failed',
						'The company image could not be uploaded to storage.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					redirect(admin_url('company/edit/'.$company_id), 'refresh');

					return;
				}
				$storedImage = $this->uploadCompanyImageToMinioApi(
					(int) $company_id,
					$tmp,
					(string) ($_FILES['image']['name'] ?? 'image.png')
				);
				if ($storedImage === null) {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_image_upload_failed',
						'The company image could not be uploaded to storage.'
					);
					$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
					redirect(admin_url('company/edit/'.$company_id), 'refresh');

					return;
				}
				$mydata['image'] = $storedImage;
			}
			$slug = $this->testInput($this->input->post('name'));
			if (empty($slug) || $slug == '') {
				$slug = $this->testInput($this->input->post('name'));
			}
			$slug = strtolower(url_title($slug));
			$mydata['slug_url'] = $this->resolveUniqueCompanySlugViaApi($slug, $company_id);
			$mydata = $this->filterCompanyRowForApi($mydata);
			$updOk = $this->updateCompanyViaApi($company_id, $mydata);
			log_message('error', 'admin_company.update result=' . json_encode([
				'ok' => $updOk,
				'company_id' => $company_id,
				'use_whatsapp_in_data' => $mydata['use_whatsapp'] ?? null,
				'whatsapp_instance_in_data' => $mydata['whatsapp_instance'] ?? null,
				'whatsapp_instance_name_in_data' => $mydata['whatsapp_instance_name'] ?? null,
				'whatsapp_apikey_in_data' => $mydata['whatsapp_apikey'] ?? null,
			], JSON_UNESCAPED_UNICODE));
			if (!$updOk) {
				$msg = 'error';
			} else {
				$postedPlainPassword = trim((string) $this->input->post('password'));
				if ($postedPlainPassword !== '') {
					if (! $this->syncManagerUserPasswordViaApi(
						$company_id,
						$postedPlainPassword,
						(string) ($compdetl->email ?? ''),
						(string) ($mydata['name'] ?? '')
					)) {
						log_message('error', 'admin_company.update manager password sync failed company_id=' . $company_id);
						$text = $this->adminLangString(
							'cmp-admin-company',
							'manager_password_sync_failed',
							'Company saved but the manager login password could not be updated. Try again or reset password from the login page.'
						);
						$msg = json_encode([$text, 'warning', '#F29F06'], JSON_UNESCAPED_UNICODE);
					}
				}
				if ($oldImage && $_FILES['image']['name'] != '')
				{
					if (! function_exists('company_logo_is_api_image') || ! company_logo_is_api_image($oldImage)) {
						if (file_exists('./uploads/company/'.$oldImage)) {
							@unlink('./uploads/company/'.$oldImage);
						}
					}
				}

				$this->saveInitialOrderNumberViaApi($request['initial_order_number'] ?? null, $company_id);
				if (! isset($msg) || $msg === 'error' || $msg === '') {
					$text = $this->adminLangString(
						'cmp-admin-company',
						'company_updated_success',
						'Company updated successfully.'
					);
					$msg = json_encode([$text, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
				}
			}

			if ($msg !== 'error') {
				$this->flashMessage($msg);
			}
			redirect(admin_url('company/lists'),'refresh');
		} else {
			show_404();
		}
	}


	public function changeStatus(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$errMsg = $this->adminLangString(
			'cmp-admin-company',
			'company_status_update_error',
			'The status could not be updated. Please try again.'
		);
		if (strtoupper((string) $this->input->method()) !== 'POST') {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		$company_id = (int) $this->input->post('company_id');
		$status = (int) $this->input->post('status');
		if ($company_id < 1 || ($status !== 0 && $status !== 1)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if ($this->fetchCompanyById($company_id) === null) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if (! $this->updateCompanyViaApi($company_id, ['status' => $status])) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if ($status === 1) {
			$okMsg = $this->adminLangString(
				'cmp-admin-company',
				'company_status_activated_success',
				'Company activated.'
			);
		} else {
			$okMsg = $this->adminLangString(
				'cmp-admin-company',
				'company_status_deactivated_success',
				'Company deactivated.'
			);
		}
		echo json_encode([$okMsg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
	}

	public function contactdelete($cId = false)
	{
		$companyId = 0;
		if ($cId != false) {
			$contactId = (int) $cId;
			$contact = $this->fetchCompanyContactById($contactId);
			$companyId = is_object($contact) && isset($contact->company_id) ? (int) $contact->company_id : 0;

			if (! $this->deleteCompanyContactViaApi($contactId)) {
				$msg = 'error';
			} else {
				$msg = json_encode([
					$this->adminLangString('cmp-admin-company', 'contact_deleted_success', 'Contact deleted successfully.'),
					'success',
					'#A5DC86',
				], JSON_UNESCAPED_UNICODE);
			}
			$this->flashMessage($msg);
		}
		if ($companyId > 0) {
			redirect(admin_url('company/edit/'.$companyId),'refresh');

			return;
		}
		redirect(admin_url('company/lists'), 'refresh');
	}

	public function view($company_id = false)
	{
		$cid = (int) $company_id;
		if ($cid < 1 || $this->fetchCompanyById($cid) === null) {
			show_404();

			return;
		}

		$data = array(
			'title' => $this->adminLangString('cmp-admin-company', 'form_view_title', 'View company details'),
			'page' => 'company',
			'subpage' => 'companylist'
		);

		$data['data'] = $this->fetchCompanyById($cid);
		$data['userlist'] = $this->fetchChildCompaniesFromApi($cid);
		$data['cfLang'] = $this->adminCompanyFormLang();
		$this->load->view('admin/header', $data);
		$this->load->view('admin/company_view');
		$this->load->view('admin/footer');
	}
	public function delete($sId = false)
	{
		if ($sId != false) {
			$companyId = (int) $sId;
			$blocked = $this->adminCompanyDeletionBlockedMessage($companyId);
			if ($blocked !== null) {
				$this->flashMessage(json_encode([$blocked, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
				redirect(admin_url('company/lists'), 'refresh');

				return;
			}

			if (! $this->deleteCompanyViaApi($companyId)) {
				$text = $this->adminLangString(
					'cmp-admin-company',
					'company_delete_failed',
					'The company could not be deleted. Please try again.'
				);
				$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			} else {
				$text = $this->adminLangString(
					'cmp-admin-company',
					'company_deleted_success',
					'Company deleted successfully.'
				);
				$this->flashMessage(json_encode([$text, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
			}
		}
		redirect(admin_url('company/lists'), 'refresh');
	}

	/**
	 * Mensaje traducido que impide eliminar la compañía, o null si las validaciones pasan.
	 */
	private function adminCompanyDeletionBlockedMessage(int $companyId): ?string
	{
		if ($companyId < 1) {
			return $this->adminLangString(
				'cmp-admin-company',
				'company_delete_blocked_invalid_company',
				'Invalid company.'
			);
		}

		$resp = $this->getDataFromApi('company/'.$companyId.'/deletion-block-reason', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return null;
		}
		$blockKey = isset($resp['block_key']) ? (string) $resp['block_key'] : '';
		if ($blockKey === '') {
			return null;
		}
		if (isset($resp['message']) && is_string($resp['message']) && trim($resp['message']) !== '') {
			return trim($resp['message']);
		}

		return $this->adminLangString('cmp-admin-company', $blockKey, $blockKey);
	}
	/**
	 * Cada fila de contacto con nombre debe tener un correo válido (RFC básico via filter_var).
	 *
	 * @param array<int|string,mixed> $cname
	 * @param array<int|string,mixed> $cemail
	 * @return string|null JSON SweetAlert o null si OK
	 */
	private function validateCompanyContactsEmails(array $cname, array $cemail): ?string
	{
		foreach ($cname as $i => $name) {
			if (trim((string) $name) === '') {
				continue;
			}
			$raw = isset($cemail[$i]) ? trim((string) $cemail[$i]) : '';
			if ($raw === '' || filter_var($raw, FILTER_VALIDATE_EMAIL) === false) {
				$text = $this->adminLangString(
					'cmp-admin-company',
					'contact_row_invalid_email',
					'Each company contact with a name must have a valid email address.'
				);

				return json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);
			}
		}

		return null;
	}

	/**
	 * Archivo api/lang/*.json según language_id de sesión (alineado con LanguageSeeder).
	 */
	private function adminLangJsonFilename(): string
	{
		if (function_exists('admin_shell_lang_filename')) {
			return admin_shell_lang_filename();
		}
		$langId = (int) ($this->session->userdata('language_id') ?? $this->session->userdata('languageid') ?? 1);
		$map = [
			1 => 'en-EN.json',
			2 => 'es-ES.json',
			3 => 'pt-PT.json',
			4 => 'it-IT.json',
			5 => 'ru-RU.json',
			6 => 'de-DE.json',
			7 => 'zh-CN.json',
		];

		return $map[$langId] ?? 'en-EN.json';
	}

	/**
	 * Una cadena de api/lang/{locale}.json bajo $section.$key.
	 */
	private function adminLangString(string $section, string $key, string $fallback = ''): string
	{
		if (function_exists('admin_lang_string')) {
			$resolved = admin_lang_string($section, $key, '');
			if ($resolved !== '') {
				return $resolved;
			}
		}

		return $fallback;
	}

	/**
	 * Países para selects (API country).
	 *
	 * @return list<object|array<string, mixed>>
	 */
	private function fetchAdminCountriesList(): array
	{
		$rows = [];
		$resp = $this->getDataFromApi('country', [
			'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
			'all' => true,
			'orderProperty' => 'name',
		], true);
		if (is_array($resp) && empty($resp['error'] ?? null)) {
			foreach ($this->extractApiListRows($resp) as $r) {
				$rows[] = is_array($r) ? (object) $r : $r;
			}
		}

		return $this->normalizeCountryRowsForDial($rows);
	}

	/**
	 * Unifica código telefónico (tabla countries: phonecode, phone_code, phonecde, etc.) en ->phonecode entero.
	 *
	 * @param list<object|array<string, mixed>> $rows
	 * @return list<object>
	 */
	private function normalizeCountryRowsForDial(array $rows): array
	{
		$out = [];
		foreach ($rows as $r) {
			$o = is_array($r) ? (object) $r : $r;
			$raw = null;
			foreach (['phonecode', 'phone_code', 'phonecde', 'PhoneCode'] as $k) {
				if (! isset($o->$k)) {
					continue;
				}
				$v = $o->$k;
				if ($v === '' || $v === null) {
					continue;
				}
				$raw = $v;
				break;
			}
			if ($raw === null || $raw === '') {
				$o->phonecode = 0;
			} else {
				$digits = preg_replace('/\D+/', '', (string) $raw);
				$o->phonecode = $digits !== '' ? (int) $digits : 0;
			}
			$out[] = $o;
		}

		return $out;
	}

	/**
	 * @return array<string, string>
	 */
	private function adminCompanyFormLang(): array
	{
		if (function_exists('admin_company_page_texts')) {
			return admin_company_page_texts();
		}

		return [];
	}

	/**
	 * Rellena columnas de dirección en $mydata solo si existen en companies.
	 *
	 * @param array<string, mixed> $mydata
	 */
	private function mergeCompanyAddressFromPost(array &$mydata): void
	{
		if (! ($this->companiesCapabilities()['installed'] ?? false)) {
			return;
		}
		$addressFields = $this->companiesCapabilities()['addressFields'] ?? [];
		if (! is_array($addressFields)) {
			$addressFields = [];
		}
		$map = [
			'full_address' => 'full_address',
			'address' => 'address',
			'country' => 'country',
			'state' => 'state',
			'city' => 'city',
			'zip_code' => 'zip_code',
			'zone' => 'zone',
		];
		foreach ($map as $postKey => $col) {
			if (in_array($col, $addressFields, true)) {
				$mydata[$col] = $this->testInput((string) $this->input->post($postKey));
			}
		}
	}

	/**
	 * Rellena columnas WhatsApp (use_whatsapp bit + whatsapp_instance_name string + whatsapp_apikey string)
	 * en $mydata solo si existen en companies. Se considera "activo" cualquier valor truthy distinto de "0"/"".
	 *
	 * @param array<string, mixed> $mydata
	 */
	private function mergeCompanyWhatsappFromPost(array &$mydata): void
	{
		$cap = $this->companiesCapabilities();
		if (! ($cap['installed'] ?? false)) {
			return;
		}

		$rawPost = $this->input->post('use_whatsapp');
		if (is_array($rawPost)) {
			$on = 0;
			foreach ($rawPost as $v) {
				if ((string) $v === '1' || (int) $v === 1) {
					$on = 1;
					break;
				}
			}
		} else {
			$s = trim((string) ($rawPost ?? ''));
			$on = ($s === '1' || $s === 'on' || $s === 'true') ? 1 : 0;
		}

		$flagCol = null;
		if ($cap['hasUseWhatsapp'] ?? false) {
			$flagCol = 'use_whatsapp';
			$mydata['use_whatsapp'] = $on;
		} elseif ($cap['hasWhatsappInstance'] ?? false) {
			$flagCol = 'whatsapp_instance';
			$mydata['whatsapp_instance'] = $on;
		}

		if ($cap['hasWhatsappInstanceName'] ?? false) {
			$instanceName = trim((string) $this->input->post('whatsapp_instance_name'));
			$mydata['whatsapp_instance_name'] = $instanceName === '' ? null : substr($instanceName, 0, 255);
		}

		if ($cap['hasWhatsappApikey'] ?? false) {
			$apikey = trim((string) $this->input->post('whatsapp_apikey'));
			$mydata['whatsapp_apikey'] = $apikey === '' ? null : substr($apikey, 0, 255);
		}

		log_message('error', 'admin_company.whatsapp post=' . json_encode([
			'post_raw' => $rawPost,
			'on' => $on,
			'flag_col' => $flagCol,
			'has_use_whatsapp' => (bool) ($cap['hasUseWhatsapp'] ?? false),
			'has_whatsapp_instance' => (bool) ($cap['hasWhatsappInstance'] ?? false),
			'has_instance_name_col' => (bool) ($cap['hasWhatsappInstanceName'] ?? false),
			'has_apikey_col' => (bool) ($cap['hasWhatsappApikey'] ?? false),
			'final' => [
				'use_whatsapp' => $mydata['use_whatsapp'] ?? null,
				'whatsapp_instance' => $mydata['whatsapp_instance'] ?? null,
				'whatsapp_instance_name' => $mydata['whatsapp_instance_name'] ?? null,
				'whatsapp_apikey' => $mydata['whatsapp_apikey'] ?? null,
			],
		], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Esquema Laravel: companies.company_category_id; legado: companies.category_id.
	 */
	private function mergeCompanyCategoryColumn(array &$mydata, $categoryPost): void
	{
		unset($mydata['category_id'], $mydata['company_category_id']);
		$cap = $this->companiesCapabilities();
		if (! ($cap['installed'] ?? false)) {
			return;
		}
		$v = $this->testInput((string) $categoryPost);
		if ($cap['hasCompanyCategoryId'] ?? false) {
			$mydata['company_category_id'] = $v !== '' ? $v : null;

			return;
		}
		if ($cap['hasCategoryId'] ?? false) {
			$mydata['category_id'] = $v;
		}
	}

	public function fetchduplicateEmail()
	{
		$rdemail = trim((string) $this->input->post('rdemail'));
		if ($rdemail !== '') {
			echo $this->companyFieldExistsViaApi('email', $rdemail) ? '1' : '0';
		}
	}

	public function fetchduplicateName()
	{
		$rdname = trim((string) $this->input->post('rdname'));
		if ($rdname !== '') {
			echo $this->companyFieldExistsViaApi('name', $rdname) ? '1' : '0';
		}
	}

	public function fetchduplicatePhone(){
		$rdphone = trim((string) $this->input->post('rdphone'));
		if ($rdphone !== '') {
			echo $this->companyFieldExistsViaApi('phone', $rdphone) ? '1' : '0';
		}
	}

	private function fetchCompanyById(int $id): ?object
	{
		if ($id < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('company/'.$id);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * Categoría, grupo, término, perfil, vendedor, unidad y almacén por defecto vía API (sustituye CompanyModel::init_databydefault).
	 */
	private function initCompanyDefaultsViaApi(int $companyId, ?string $plainPassword = null): void
	{
		if ($companyId < 1) {
			return;
		}
		$payload = [];
		if ($plainPassword !== null && $plainPassword !== '') {
			$payload['password'] = $plainPassword;
		}
		$resp = $this->post('company/' . $companyId . '/init-defaults', $payload, true);
		if ($this->hasError($resp)) {
			log_message(
				'error',
				'initCompanyDefaultsViaApi failed for company ' . $companyId . ': ' . json_encode($resp, JSON_UNESCAPED_UNICODE)
			);
		}
	}

	/**
	 * @return list<object>
	 */
	private function fetchCompaniesForAdminList(): array
	{
		$resp = $this->getDataFromApi('company', [
			'all' => true,
			'rootOnly' => 1,
			'orderProperty' => 'company_id',
			'orderDirection' => 'desc',
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @return list<object>
	 */
	private function fetchActiveCompanyCategoriesFromApi(): array
	{
		$resp = $this->getDataFromApi('companiescategory/actives', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @return list<object>
	 */
	private function fetchActiveLanguagesFromApi(): array
	{
		$resp = $this->getDataFromApi('language', [
			'all' => true,
			'status' => 1,
			'orderProperty' => 'language_id',
			'orderDirection' => 'asc',
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			$resp = $this->getDataFromApi('language/actives', [], true);
		}
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @return list<object>
	 */
	private function fetchActiveCurrenciesFromApi(): array
	{
		$resp = $this->getDataFromApi('currency', [
			'all' => true,
			'status' => 1,
			'orderProperty' => 'id',
			'orderDirection' => 'asc',
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @return list<object>
	 */
	private function fetchCurrenciesGroupedByContinentFromApi(): array
	{
		$resp = $this->getDataFromApi('currency/grouped-by-continent', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @param mixed $currencyId
	 * @param list<object> $groups
	 */
	private function resolveAdminCurrencyPickerId($currencyId, array $groups, ?string $currencyCode = null): int
	{
		$id = (int) $currencyId;
		if ($id > 0) {
			foreach ($groups as $group) {
				$children = $group->currencies ?? [];
				if (! is_array($children)) {
					continue;
				}
				foreach ($children as $child) {
					$c = is_array($child) ? (object) $child : $child;
					if ((int) ($c->id ?? 0) === $id) {
						return $id;
					}
				}
			}
		}

		$code = strtoupper(trim((string) ($currencyCode ?? '')));
		if ($code !== '') {
			foreach ($groups as $group) {
				$children = $group->currencies ?? [];
				if (! is_array($children)) {
					continue;
				}
				foreach ($children as $child) {
					$c = is_array($child) ? (object) $child : $child;
					if (strtoupper(trim((string) ($c->Code ?? ''))) === $code) {
						return (int) ($c->id ?? 0);
					}
				}
			}
		}

		return $this->defaultCurrencyPickerId($groups);
	}

	/**
	 * @param list<object> $groups
	 */
	private function defaultCurrencyPickerId(array $groups): int
	{
		foreach ($groups as $group) {
			$children = $group->currencies ?? [];
			if (! is_array($children)) {
				continue;
			}
			foreach ($children as $child) {
				$c = is_array($child) ? (object) $child : $child;
				if ((string) ($c->default ?? '') === 'selected') {
					return (int) ($c->id ?? 0);
				}
			}
		}
		foreach ($groups as $group) {
			$children = $group->currencies ?? [];
			if (is_array($children) && ! empty($children)) {
				$child = $children[0];
				$c = is_array($child) ? (object) $child : $child;

				return (int) ($c->id ?? 0);
			}
		}

		return 0;
	}

	/**
	 * @param list<object> $countries
	 * @return array<string, string>
	 */
	private function buildCountrySortnameMap(array $countries): array
	{
		$map = [];
		foreach ($countries as $country) {
			$c = is_array($country) ? (object) $country : $country;
			$name = trim((string) ($c->name ?? ''));
			$sortname = strtolower(trim((string) ($c->sortname ?? '')));
			if ($name !== '' && $sortname !== '') {
				$map[$name] = $sortname;
			}
		}

		return $map;
	}

	private function fetchDefaultCurrencyObjectForForm(): ?object
	{
		foreach ($this->fetchCurrenciesGroupedByContinentFromApi() as $group) {
			$children = $group->currencies ?? [];
			if (! is_array($children)) {
				continue;
			}
			foreach ($children as $child) {
				$c = is_array($child) ? (object) $child : $child;
				if (isset($c->default) && (string) $c->default === 'selected') {
					return $c;
				}
			}
		}
		foreach ($this->fetchCurrenciesGroupedByContinentFromApi() as $group) {
			$children = $group->currencies ?? [];
			if (is_array($children) && ! empty($children)) {
				$child = $children[0];

				return is_array($child) ? (object) $child : $child;
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function populateCompanyFormCommonData(array &$data, array $formData = [], ?int $companyId = null): void
	{
		$data['catlist'] = $this->fetchActiveCompanyCategoriesFromApi();
		$data['language'] = $this->fetchActiveLanguagesFromApi();
		$data['currList'] = $this->fetchActiveCurrenciesFromApi();
		$data['currencies_pather'] = $this->fetchCurrenciesGroupedByContinentFromApi();
		$data['countries'] = $this->fetchAdminCountriesList();
		$data['adminCountrySortnameMap'] = $this->buildCountrySortnameMap($data['countries']);
		$data['cfLang'] = $this->adminCompanyFormLang();
		if (! isset($data['formData'])) {
			$data['formData'] = $formData;
		}
		$pickerSource = $formData['currency'] ?? null;
		if ($companyId !== null && $companyId > 0 && ($pickerSource === null || $pickerSource === '')) {
			$companyRow = $data['data'] ?? null;
			if (is_object($companyRow) && isset($companyRow->currency)) {
				$pickerSource = $companyRow->currency;
			}
		}
		$pickerCode = null;
		if ($companyId !== null && $companyId > 0) {
			$currencyResp = $this->getDataFromApi('company/'.$companyId.'/currency', [], true);
			$currency = null;
			if (is_array($currencyResp) && ! $this->hasError($currencyResp)) {
				$currency = $currencyResp['data'] ?? null;
			}
			$data['currency'] = is_array($currency) ? (object) $currency : (is_object($currency) ? $currency : $this->fetchDefaultCurrencyObjectForForm());
			if (is_object($data['currency'])) {
				$pickerCode = (string) ($data['currency']->code ?? '');
			}
		} else {
			$data['currency'] = $this->fetchDefaultCurrencyObjectForForm();
		}
		$data['adminSelectedCurrencyId'] = $this->resolveAdminCurrencyPickerId(
			$pickerSource,
			$data['currencies_pather'],
			$pickerCode
		);
	}

	/**
	 * @return list<object>
	 */
	private function fetchCompanyContactsFromApi(int $companyId): array
	{
		if ($companyId < 1) {
			return [];
		}
		$resp = $this->getDataFromApi('company/'.$companyId.'/contacts', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	private function fetchCompanyContactById(int $contactId): ?object
	{
		if ($contactId < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('companycontact/'.$contactId);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * @return list<object>
	 */
	private function fetchChildCompaniesFromApi(int $parentCompanyId): array
	{
		if ($parentCompanyId < 1) {
			return [];
		}
		$resp = $this->getDataFromApi('company', [
			'all' => true,
			'createdByCompId' => $parentCompanyId,
			'orderProperty' => 'company_id',
			'orderDirection' => 'desc',
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function createCompanyViaApi(array $row): int
	{
		$this->lastCreatedCompanyId = 0;
		$this->lastCompanyApiError = '';
		$resp = $this->post('company', $row, true);
		if (! is_array($resp) || $this->hasError($resp)) {
			$this->lastCompanyApiError = $this->resolveApiErrorMessage($resp);

			return 0;
		}
		$data = $resp['data'] ?? [];
		if (is_array($data)) {
			$this->lastCreatedCompanyId = (int) ($data['company_id'] ?? 0);
		} elseif (is_object($data)) {
			$this->lastCreatedCompanyId = (int) ($data->company_id ?? 0);
		}

		return $this->lastCreatedCompanyId;
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function createManagerUserViaApi(int $companyId, array $payload): bool
	{
		$this->lastManagerApiError = '';
		if ($companyId < 1) {
			return false;
		}

		$resp = $this->post('user', array_merge([
			'company_id' => $companyId,
			'role' => 'manager',
		], $payload), true);

		if (! is_array($resp) || $this->hasError($resp)) {
			$this->lastManagerApiError = $this->resolveApiErrorMessage($resp);

			return false;
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function updateCompanyViaApi(int $id, array $row): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->put('company/'.$id, $row, true);

		return is_array($resp) && ! $this->hasError($resp);
	}

	private function deleteCompanyViaApi(int $id): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->apiDelete('company/'.$id, [], true);

		return ! $this->hasError($resp);
	}

	private function deleteCompanyContactViaApi(int $id): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->apiDelete('companycontact/'.$id, [], true);

		return ! $this->hasError($resp);
	}

	private function companyFieldExistsViaApi(string $field, string $value): bool
	{
		$value = trim($value);
		if ($value === '') {
			return false;
		}
		$resource = '';
		if ($field === 'email') {
			$resource = 'company/email/'.rawurlencode($value).'/exists';
		} elseif ($field === 'phone') {
			$resource = 'company/phone/'.rawurlencode($value).'/exists';
		} elseif ($field === 'name') {
			$resource = 'company/name/'.rawurlencode($value).'/exists';
		}
		if ($resource === '') {
			return false;
		}
		$resp = $this->getDataFromApi($resource, [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return false;
		}

		return (bool) ($resp['exists'] ?? false);
	}

	private function resolveUniqueCompanySlugViaApi(string $slug, int $excludeCompanyId = 0): string
	{
		$slug = trim($slug);
		if ($slug === '') {
			return $slug;
		}
		$resp = $this->getDataFromApi('company/slug-'.rawurlencode($slug), [], true);
		if ($this->hasError($resp)) {
			return $slug;
		}
		$data = is_array($resp) ? ($resp['data'] ?? $resp) : null;
		$foundId = 0;
		if (is_array($data)) {
			$foundId = (int) ($data['company_id'] ?? 0);
		} elseif (is_object($data)) {
			$foundId = (int) ($data->company_id ?? 0);
		}
		if ($foundId < 1 || ($excludeCompanyId > 0 && $foundId === $excludeCompanyId)) {
			return $slug;
		}

		return $this->resolveUniqueCompanySlugViaApi($slug.'1', $excludeCompanyId);
	}

	/**
	 * @param list<array<string, mixed>> $contacts
	 */
	private function replaceCompanyContactsViaApi(int $companyId, array $contacts): void
	{
		if ($companyId < 1) {
			return;
		}
		$existing = $this->fetchCompanyContactsFromApi($companyId);
		foreach ($existing as $row) {
			$cid = (int) ($row->c_id ?? 0);
			if ($cid > 0) {
				$this->deleteCompanyContactViaApi($cid);
			}
		}
		foreach ($contacts as $contact) {
			$payload = $contact;
			$payload['company_id'] = $companyId;
			$this->post('companycontact', $payload, true);
		}
	}

	private function fetchPendingStripeCountFromApi(): int
	{
		if (! ($this->companiesCapabilities()['hasStripeId'] ?? false)) {
			return 0;
		}
		$resp = $this->getDataFromApi('company/pending-stripe-customers-count', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return 0;
		}

		return (int) ($resp['count'] ?? 0);
	}

	private function fetchInitialOrderNumberFromApi(int $companyId): ?int
	{
		if ($companyId < 1) {
			return null;
		}
		$resp = $this->getDataFromApi('initialordernumber', [
			'company' => $companyId,
			'perPage' => 1,
			'page' => 1,
		], true);
		if (! is_array($resp) || $this->hasError($resp) || empty($resp['data'][0])) {
			return null;
		}
		$row = $resp['data'][0];
		$val = is_array($row) ? ($row['initial_number'] ?? null) : (is_object($row) ? ($row->initial_number ?? null) : null);

		return $val !== null ? (int) $val : null;
	}

	private function saveInitialOrderNumberViaApi($newInitialOrderNumber, int $companyId): void
	{
		if ($companyId < 1 || $newInitialOrderNumber === null || $newInitialOrderNumber === '' || (int) $newInitialOrderNumber < 1) {
			return;
		}
		$payload = [
			'company_id' => $companyId,
			'initial_number' => (int) $newInitialOrderNumber,
		];
		$existing = $this->fetchInitialOrderNumberFromApi($companyId);
		if ($existing === null) {
			$this->post('initialordernumber', $payload, true);

			return;
		}
		$this->put('initialordernumber/'.$companyId, ['initial_number' => (int) $newInitialOrderNumber], true);
	}

	private function fetchNextOrderNumberFromApi(int $companyId): int
	{
		if ($companyId < 1) {
			return 1;
		}
		$resp = $this->getDataFromApi('order/company/'.$companyId.'/getOrderNumber/orderType/O', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return 1;
		}
		$orderNumber = (string) ($resp['order_number'] ?? '');
		if ($orderNumber === '') {
			return 1;
		}
		if (preg_match('/(\d+)$/', $orderNumber, $m)) {
			return (int) $m[1];
		}

		return 1;
	}

	/**
	 * @param array<string, mixed> $row
	 *
	 * @return array<string, mixed>
	 */
	private function filterCompanyRowForApi(array $row): array
	{
		$columns = $this->companiesCapabilities()['columns'] ?? [];
		if (! is_array($columns) || $columns === []) {
			return $row;
		}
		$out = [];
		foreach ($row as $col => $val) {
			if (in_array($col, $columns, true)) {
				$out[$col] = $val;
			}
		}

		return $out;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function companiesCapabilities(): array
	{
		if (function_exists('admin_capability_section')) {
			return admin_capability_section('companies');
		}

		return [];
	}

	/**
	 * Localiza el usuario manager de la empresa vía API.
	 *
	 * @return array<string, mixed>|null
	 */
	private function fetchManagerUserForCompanyViaApi(int $companyId, ?string $companyEmail = null): ?array
	{
		if ($companyId < 1) {
			return null;
		}

		$usersApi = $this->getDataFromApi("company/{$companyId}/users", [
			'page' => 1,
			'perPage' => 50,
			'type' => 'manager',
		], true);
		if (is_array($usersApi) && empty($usersApi['error']) && ! empty($usersApi['data']) && is_array($usersApi['data'])) {
			foreach ($usersApi['data'] as $row) {
				$normalized = is_array($row) ? $row : (is_object($row) ? (array) $row : null);
				if (is_array($normalized) && (int) ($normalized['id'] ?? 0) > 0) {
					return $normalized;
				}
			}
		}

		$email = strtolower(trim((string) $companyEmail));
		if ($email === '') {
			$company = $this->fetchCompanyById($companyId);
			$email = strtolower(trim((string) ($company->email ?? '')));
		}
		if ($email === '') {
			return null;
		}

		$allUsersApi = $this->getDataFromApi("company/{$companyId}/users", [
			'page' => 1,
			'perPage' => 500,
		], true);
		if (! is_array($allUsersApi) || ! empty($allUsersApi['error']) || empty($allUsersApi['data']) || ! is_array($allUsersApi['data'])) {
			return null;
		}

		foreach ($allUsersApi['data'] as $row) {
			$normalized = is_array($row) ? $row : (is_object($row) ? (array) $row : null);
			if (! is_array($normalized)) {
				continue;
			}
			$rowEmail = strtolower(trim((string) ($normalized['email'] ?? '')));
			if ($rowEmail === $email && (int) ($normalized['id'] ?? 0) > 0) {
				return $normalized;
			}
		}

		return null;
	}

	/**
	 * Actualiza users.password del manager (login /login usa tabla users).
	 */
	private function syncManagerUserPasswordViaApi(
		int $companyId,
		string $plainPassword,
		?string $companyEmail = null,
		?string $managerName = null
	): bool {
		$plainPassword = trim($plainPassword);
		if ($companyId < 1 || $plainPassword === '' || strlen($plainPassword) < 8) {
			return false;
		}

		$manager = $this->fetchManagerUserForCompanyViaApi($companyId, $companyEmail);
		if ($manager === null) {
			return false;
		}

		$userId = (int) ($manager['id'] ?? 0);
		$name = trim((string) ($manager['name'] ?? $managerName ?? ''));
		$email = trim((string) ($manager['email'] ?? $companyEmail ?? ''));
		if ($userId < 1 || $name === '' || $email === '') {
			return false;
		}

		$resp = $this->put('user/' . $userId, [
			'name' => $name,
			'email' => $email,
			'password' => $plainPassword,
		], true);

		return is_array($resp) && ! $this->hasError($resp);
	}


}
