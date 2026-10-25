<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property $company_id
 * @property $category_id
 * @property $language_id
 * @property $name
 * @property $short_name
 * @property $email
 * @property $password
 * @property $phone_number
 * @property $cell_number
 * @property $fax
 * @property $image
 * @property $address
 * @property $country
 * @property $state
 * @property $city
 * @property $zip_code
 * @property $tax_condition
 * @property $tax_id
 * @property $max_number_of_clients
 * @property $max_number_of_orders
 * @property $max_number_of_vendors
 * @property $total_price
 * @property $website
 * @property $created
 * @property $status
 * @property $slug_url
 * @property $currency
 * @property $createdby_comp_id
 * @property $currency_code_id
 * @property $token
 * @property $ordersend_time
 * @property $catalogsend_time
 * @property $format_id
 * @property $deleted_at
 * @property $sell_with_inventory
 * @property $rol_id
 * @property $show_inventory
 * @property $show_semaforo_stock
 * @property $sellers_ids
 */
class Usermodel extends My_Model
{
	public function login($username, $password)
	{
		$where = "email = '".$username."' OR username = '".$username."'";
		if ($this->count('admin', $where) != 1)
		{
			$msg = "Invalid Username.";
		} else {
			$user = $this->get_by('admin', true, $where);
			if (password_verify($password, @$user->password) == 0)
			{
				$msg = "Invalid Password";
			} elseif (@$user->status == 0) {
				$msg = "Your account has been deactivated. Please contact master admin for details.";
			}	else {
				$msg = "Login";
				$this->session->set_userdata('admin', '1');
				$this->session->set_userdata('userId', @$user->userId);
			}
		}
		return $msg;
	}

	public function userLogin($email, $password)
	{
		$where = "email = '".$email."'";
		if ($this->count('users', $where) != 1)
		{
			$msg = "Invalid Email.";
		} else {
			$sql = "SELECT u.*, r.referBy FROM users AS u JOIN referal AS r ON r.referTo = u.userId WHERE $where";
			$user = $this->get_by('users', true, $where);
			if (password_verify($password, @$user->password) == 0)
			{
				$msg = "Invalid Password";
			} elseif (@$user->status == 0)
			{
				$msg = "Your account has been deactivated. Please contact master admin for details.";
			} else {
				$msg = $user;
			}
		}
		return $msg;
	}

	function get_unique_url($url, $id = false)
	{
		$this->db->select('slug_url, company_id');
		$this->db->where('slug_url', $url);
		$rest = $this->db->get('companies');

		if ($rest->num_rows() == 0) {
			return $url;
		} else {
			$cr = $rest->first_row();
			if ($cr->company_id == $id) {
				return $url;
			} else {
				$url = $url . '1';
				return $this->get_unique_url($url, $id);
			}
		}
	}

	public function validateLogin($username, $password){

		$where = "email = '".$username."' and deleted_at is null";
		$user = $this->get_by('companies', true, $where);

		$company = $this->get_by('companies', true, 'company_id ='.$user->company_id);
		//echo json_encode($company);
		if (!$user) return false;
		if (password_verify($password, $user->password) == 0)  return false;
		if ($user->status == 0) return false;
		return true;

	}



	public function compemailverify($username)
	{
		$where = "email = '".$username."'";

		if ($this->count('companies', $where) != 1)
		{
			$msg = 0;
		} else {

			$msg = 1;

		}
		return $msg;
	}
	public function adminmailverify($username)
	{
		$where = "email = '".$username."'";
		if ($this->count('admin', $where) != 1)
		{
			$msg = 0;
		} else {

			$msg = 1;

		}
		return $msg;
	}
	public function verifyOTP($data)
	{
		$user = $this->get('users', true, 'userId', $data['userId']);
		if (@$user->otp == $data['otp'])
		{
			return false;
		} else {
			return true;
		}
	}

	public function addNewUser($data)
	{
		$where1 = array(
			'email' => $data['email'],
			'userType' => 1,
		);
		$where2 = array(
			'mobile' => $data['mobile'],
			'userType' => 1,
		);
		if ($this->count('users', $where1)>0)
		{
			$msg = '["This Email is already registered with us.", "error", "#DD6B55"]';
		} elseif ($this->count('users', $where2)>0)
		{
			$msg = '["This Mobile no. is already registered with us.", "error", "#DD6B55"]';
		} elseif (!$this->save('users', $data))
		{
			$msg = 'error';
		} else {
			$msg = '["New User added successfully!", "success", "#A5DC86"]';
		}
		return $msg;
	}

	public function addNewCompany($data)
	{
		$where1 = array(
			'email' => $data['email'],
			//'deleted_at' => null
		);
		$where2 = array(
			'phone_number' => $data['phone_number'],
		);
		$where3 = array(
			'name' => $data['name'],
		);

		if ($this->count('companies', $where1)>0)
		{
			$msg =1;
			//$msg = '["This Email is already registered with us.", "error", "#DD6B55"]';
		} elseif ($this->count('companies', $where2)>0)
		{
			$msg =2;
			//$msg = '["This Phone no. is already registered with us.", "error", "#DD6B55"]';
		} elseif ($this->count('companies', $where3)>0)
		{
			$msg =6;
		} elseif (!$this->save('companies', $data))
		{
			$msg =3;
			//$msg = 'error';
		} else {
			$msg =4;
			//$msg = '["New Company added successfully!", "success", "#A5DC86"]';
		}
		return $msg;
	}

	/**
	 * Crea fila en users y vínculo manager en company_user (misma transacción que la compañía en el controlador).
	 *
	 * @param array{name:string,email:string,password:string,language_id:int,phone?:?string,cellphone?:?string} $payload password ya hasheado (mismo algoritmo que companies.password)
	 */
	public function attachManagerUserForNewCompany(int $companyId, array $payload): bool
	{
		if ($companyId < 1 || $payload['email'] === '' || $payload['password'] === '') {
			return false;
		}

		$now = date('Y-m-d H:i:s');
		$userRow = array(
			'name' => $payload['name'],
			'email' => $payload['email'],
			'password' => $payload['password'],
			'language_id' => (int) $payload['language_id'],
			'phone' => $payload['phone'] ?? null,
			'cellphone' => $payload['cellphone'] ?? null,
			'created_at' => $now,
			'updated_at' => $now,
		);

		if (! $this->save('users', $userRow)) {
			return false;
		}

		$userId = (int) $this->db->insert_id();
		if ($userId < 1) {
			return false;
		}

		$pivot = array(
			'company_id' => $companyId,
			'user_id' => $userId,
			'type' => 'manager',
			'profileable_id' => null,
			'profileable_type' => null,
			'created_at' => $now,
			'updated_at' => $now,
		);

		return $this->save('company_user', $pivot);
	}

	public function addNewVendor($data)
	{
		$where1 = array(
			'email' => $data['email'],
		);
		$where2 = array(
			'phone_number' => $data['phone_number'],
		);

		if ($this->count('sellers', $where1)>0)
		{
			$msg = '["This Email is already registered with us.", "error", "#DD6B55"]';
		} elseif ($this->count('sellers', $where2)>0)
		{
			$msg = '["This Phone no. is already registered with us.", "error", "#DD6B55"]';
		} elseif (!$this->save('sellers', $data))
		{
			$msg = 'error';
		} else {
			$msg = '["New Vendor added successfully!", "success", "#A5DC86"]';
		}
		return $msg;
	}
}
