<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Change_password extends Company_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function index()
	{
		log_message('debug', 'Change_password::index - Starting');
		
		try {
			$data = array(
				'title' => 'Change Password',
				'page' => 'profile',
				'subpage' => 'changepassword'
			);
			
			log_message('debug', 'Change_password::index - Basic data set');

			// Obtener userId de forma segura
			try {
				$user = $this->getUser();
				if (is_array($user) && isset($user['id'])) {
					$data['userId'] = $user['id'];
				} elseif (is_object($user) && isset($user->id)) {
					$data['userId'] = $user->id;
				} else {
					$data['userId'] = null;
				}
				log_message('debug', 'Change_password::index - User ID: ' . ($data['userId'] ?? 'null'));
			} catch (\Exception $e) {
				log_message('error', 'Error getting userId in Change_password: ' . $e->getMessage());
				$data['userId'] = null;
			}

			// Obtener langdef de forma segura
			try {
				if (isset($this->HelperLanguage) && is_object($this->HelperLanguage)) {
					$data['langdef'] = $this->HelperLanguage->get_language_head();
				} else {
					// Intentar cargar HelperLanguage
					try {
						$this->load->model('HelperLanguage');
						if (isset($this->HelperLanguage) && is_object($this->HelperLanguage)) {
							$data['langdef'] = $this->HelperLanguage->get_language_head();
						} else {
							$data['langdef'] = [];
						}
					} catch (\Exception $e) {
						log_message('error', 'Error loading HelperLanguage: ' . $e->getMessage());
						$data['langdef'] = [];
					}
				}
				log_message('debug', 'Change_password::index - Langdef loaded');
			} catch (\Exception $e) {
				log_message('error', 'Error getting langdef in Change_password: ' . $e->getMessage());
				$data['langdef'] = [];
			}

			// Verificar que getCompany() funcione antes de llamar a showContent
			try {
				$compId = $this->getCompany();
				if (!$compId) {
					log_message('error', 'Company ID is null in Change_password::index');
					// Redirigir a login si no hay company
					redirect(base_url('login'), 'refresh');
					return;
				}
				log_message('debug', 'Change_password::index - Company ID: ' . $compId);
			} catch (\Exception $e) {
				log_message('error', 'Error getting company in Change_password::index: ' . $e->getMessage());
				redirect(base_url('login'), 'refresh');
				return;
			}

			// Usar showContent para cargar el layout completo con todos los estilos
			// showContent llama a getDataForView que puede lanzar excepciones
			log_message('debug', 'Change_password::index - Calling showContent');
			try {
				$this->showContent('company/change_password', $data);
				log_message('debug', 'Change_password::index - showContent completed successfully');
			} catch (\Exception $e) {
				log_message('error', 'Error in showContent for Change_password: ' . $e->getMessage());
				log_message('error', 'Stack trace: ' . $e->getTraceAsString());
				log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
				// Intentar cargar la vista directamente como fallback
				try {
					$data['main_content'] = 'company/change_password';
					$this->load->view('layouts/company', $data);
				} catch (\Exception $e2) {
					log_message('error', 'Error loading view directly: ' . $e2->getMessage());
					show_error('An error occurred loading the page. Please contact support.', 500);
				}
			}
		} catch (\Exception $e) {
			log_message('error', 'Fatal error in Change_password::index: ' . $e->getMessage());
			log_message('error', 'Stack trace: ' . $e->getTraceAsString());
			log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
			show_error('An error occurred. Please contact support. Error: ' . htmlspecialchars($e->getMessage()), 500);
		} catch (\Error $e) {
			log_message('error', 'Fatal PHP error in Change_password::index: ' . $e->getMessage());
			log_message('error', 'Stack trace: ' . $e->getTraceAsString());
			log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
			show_error('An error occurred. Please contact support. Error: ' . htmlspecialchars($e->getMessage()), 500);
		}
	}


	public function update()
	{
		// Log para debug
		log_message('debug', 'Change_password::update called. Method: ' . $this->input->method());
		log_message('debug', 'POST data: ' . json_encode($this->input->post()));
		
		// Verificar que sea una petición POST
		if ($this->input->method() !== 'post') {
			log_message('debug', 'Change_password::update - Not a POST request, redirecting');
			redirect(company_url('change_password'), 'refresh');
			return;
		}

		try {
			$compId = $this->getCompany();
			
		if (!$compId) {
			log_message('error', 'Change_password::update - Company ID is null');
			
			// Si es una petición AJAX, devolver JSON
			if ($this->input->is_ajax_request()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Company ID not found. Please contact support.']);
				return;
			}
			
			$msg = '["Company ID not found. Please contact support.", "error", "#F29F06"]';
			$this->flashMessage($msg);
			redirect(company_url('change_password'), 'refresh');
			return;
		}
	} catch (\Exception $e) {
		log_message('error', 'Error getting company ID in Change_password::update: ' . $e->getMessage());
		
		// Si es una petición AJAX, devolver JSON
		if ($this->input->is_ajax_request()) {
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Error getting company information. Please contact support.']);
			return;
		}
		
		$msg = '["Error getting company information. Please contact support.", "error", "#F29F06"]';
		$this->flashMessage($msg);
		redirect(company_url('change_password'), 'refresh');
		return;
	}

		$c_password = $this->input->post('c_password');
		$n_password_confirmation = $this->input->post('n_password_confirmation'); // Nueva contraseña
		$n_password = $this->input->post('n_password'); // Confirmación de nueva contraseña
		
		// Verificar que los campos requeridos estén presentes
		if (empty($c_password) || empty($n_password_confirmation) || empty($n_password)) {
			log_message('debug', 'Change_password::update - Missing required fields. c_password: ' . (empty($c_password) ? 'empty' : 'present') . ', n_password_confirmation: ' . (empty($n_password_confirmation) ? 'empty' : 'present') . ', n_password: ' . (empty($n_password) ? 'empty' : 'present'));
			
			// Si es una petición AJAX, devolver JSON
			if ($this->input->is_ajax_request()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (Current Password, New Password, and Repeat Password).']);
				return;
			}
			
			$msg = '["Please fill in all required fields (Current Password, New Password, and Repeat Password).", "error", "#F29F06"]';
			$this->flashMessage($msg);
			redirect(company_url('change_password'), 'refresh');
			return;
		}
		
		// Validar que la nueva contraseña y la confirmación coincidan
		if ($n_password_confirmation !== $n_password) {
			log_message('debug', 'Change_password::update - Passwords do not match');
			
			// Si es una petición AJAX, devolver JSON
			if ($this->input->is_ajax_request()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'New Password and Repeat Password do not match. Please try again.']);
				return;
			}
			
			$msg = '["New Password and Repeat Password do not match. Please try again.", "error", "#F29F06"]';
			$this->flashMessage($msg);
			redirect(company_url('change_password'), 'refresh');
			return;
		}
		
		// Usar n_password_confirmation como la nueva contraseña (o n_password, ya que coinciden)
		$new_password = $n_password_confirmation;

		// Validaciones deshabilitadas temporalmente para pruebas
		// $this->form_validation->set_rules('c_password', 'Current Password', 'trim|required');
		// $this->form_validation->set_rules('n_password_confirmation', 'New Password', 'trim|required');
		// $this->form_validation->set_rules('n_password', 'Repeat Password', 'trim|required|matches[n_password_confirmation]');

		// if ($this->form_validation->run() == false)
		// {
		// 	$errorMessages = [];
		// 	if (form_error('c_password')) {
		// 		$errorMessages[] = strip_tags(form_error('c_password'));
		// 	}
		// 	if (form_error('n_password_confirmation')) {
		// 		$errorMessages[] = strip_tags(form_error('n_password_confirmation'));
		// 	}
		// 	if (form_error('n_password')) {
		// 		$errorMessages[] = strip_tags(form_error('n_password'));
		// 	}
		// 	
		// 	$errorMsg = !empty($errorMessages) ? implode(' ', $errorMessages) : 'Please fill all required fields.';
		// 	$msg = '["' . $errorMsg . '", "error", "#F29F06"]';
		// 	$this->flashMessage($msg);
		// 	redirect(company_url('change_password'),'refresh');
		// 	return;
		// }

		try {
		// Cambiar la contraseña vía API (verifica la actual, valida que sea distinta y actualiza companies + users)
		$changeResponse = $this->post('auth/password/change', [
			'company_id' => (int) $compId,
			'current_password' => $c_password,
			'new_password' => $new_password,
		]);

		log_message('debug', 'Change_password::update - API Response: ' . json_encode($changeResponse));

		if ($this->hasError($changeResponse) || ! (is_array($changeResponse) && ! empty($changeResponse['success']))) {
			$apiMessage = '';
			if (is_array($changeResponse) && isset($changeResponse['message'])) {
				$apiMessage = is_array($changeResponse['message'])
					? ($changeResponse['message']['message'] ?? '')
					: (string) $changeResponse['message'];
			}
			if ($apiMessage === '') {
				$apiMessage = 'Failed to change password. Please try again.';
			}

			if ($this->input->is_ajax_request()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => $apiMessage]);
				return;
			}

			$msg = '["' . htmlspecialchars($apiMessage, ENT_QUOTES) . '", "warning", "#F29F06"]';
			$this->flashMessage($msg);
			redirect(company_url('change_password'),'refresh');
			return;
		}

		// Si es una petición AJAX, devolver JSON de éxito
		if ($this->input->is_ajax_request()) {
			// Guardar mensaje de éxito antes de cerrar sesión
			$msg = '["Password changed successfully. Please login again.", "success", "#A5DC86"]';
			$this->flashMessage($msg);
			
			log_message('debug', 'Change_password::update - Password changed successfully, logging out');

			// Cerrar sesión
			$this->doLogOut();

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true, 
				'message' => 'Password changed successfully. You will be redirected to login.',
				'redirect' => base_url('login')
			]);
			return;
		}

		// Mensaje de éxito (guardar antes de cerrar sesión)
		$msg = '["Password changed successfully. Please login again.", "success", "#A5DC86"]';
		$this->flashMessage($msg);
		
		log_message('debug', 'Change_password::update - Password changed successfully, logging out');

		// Cerrar sesión
		$this->doLogOut();

		// Redirigir a login usando header location para asegurar que funcione
		header('Location: ' . base_url('login'));
		exit;

	} catch (\Exception $e) {
		// Capturar cualquier excepción y mostrar mensaje de error
		$errorMsg = 'An unexpected error occurred: ' . $e->getMessage();
		log_message('error', 'Change password error: ' . $errorMsg);
		log_message('error', 'Stack trace: ' . $e->getTraceAsString());
		
		// Si es una petición AJAX, devolver JSON
		if ($this->input->is_ajax_request()) {
			header('Content-Type: application/json');
			echo json_encode([
				'success' => false, 
				'message' => 'Failed to change password. ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . ' Please try again or contact support.'
			]);
			return;
		}
		
		$msg = '["Failed to change password. ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . ' Please try again or contact support.", "error", "#F29F06"]';
		$this->flashMessage($msg);
		redirect(company_url('change_password'),'refresh');
		return;
	}
	}

}
