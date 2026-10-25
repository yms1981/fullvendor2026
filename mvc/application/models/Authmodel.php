<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Authmodel extends My_Model {


	public function login($username, $password) 
	{
		$where = "email = '".$username."'";

		if ($this->count('users', $where) != 1) {

			$msg = "Invalid Email.";
		} else {
			$user = $this->get_by('users', true, $where);
			if (password_verify($password, $user->password) == 0) {
				$msg = "Invalid Password";
			}else {

				$msg = "Login";
				
			$value = array(
              'id'=>$user->userId,
              'email'=>$user->email,
			  'password'=>$password,
              'type' => $user->userType
            );
          
          $this->session->set_userdata($value);
				//$this->session->set_userdata('userId', $user->userId);
			}
		}
		return $msg;
	}
	public function sellerlogin($username, $password)
	{
		$where = "email = '".$username."' AND userType=2";

		if ($this->count('users', $where) != 1) {

			$msg = "Invalid Email.";
		} else {
			$user = $this->get_by('users', true, $where);
			if (password_verify($password, $user->password) == 0) {
				$msg = "Invalid Password";
			}else {

				$msg = "Login";
				
			$value = array(
              'id'=>$user->userId,
              'email'=>$user->email,
			  'password'=>$password,
              'type' => "seller"
            );
          
          $this->session->set_userdata($value);
				//$this->session->set_userdata('userId', $user->userId);
			}
		}
		return $msg;
	}



	public function verifyOTP($data)
	{
		$user = $this->get('users', true, 'userId', $data['userId']);
		if ($user->otp == $data['otp']) {
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
		if ($this->count('users', $where1)>0) {
			$msg = '["This Email is alerady registered with us.", "error", "#DD6B55"]';
		} elseif ($this->count('users', $where2)>0) {
			$msg = '["This Mobile no. is alerady registered with us.", "error", "#DD6B55"]';
		} elseif (!$this->save('users', $data)) {
			$msg = 'error';
		} else {
			$msg = '["New User added successfully!", "success", "#A5DC86"]';
		}
		return $msg;
	}
	public function getProfile($userId)
	{
		return $this->get_by('users', true, "userId=$userId");
	}
	


}