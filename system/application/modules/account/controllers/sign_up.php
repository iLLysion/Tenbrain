<?php
/*
 * Sign_up Controller
 */
class Sign_up extends Controller {
	
	/**
	 * Constructor
	 */
	function Sign_up()
	{
		parent::Controller();
		
		// Load the necessary stuff...
		$this->load->config('account/account');
		$this->load->helper(array('language', 'account/ssl', 'url'));
        $this->load->library(array('account/authentication', 'account/recaptcha', 'form_validation'));
		$this->load->model(array('account/account_model', 'account/account_details_model'));
		$this->load->language(array('general', 'account/sign_up', 'account/connect_third_party', 'account/email_validate'));
	}
	
	/**
	 * Account sign up
	 *
	 * @access public
	 * @return void
	 */
	function index()
	{
		// Enable SSL?
		maintain_ssl($this->config->item("ssl_enabled"));
		
		// Redirect signed in users to homepage
		if ($this->authentication->is_signed_in()) redirect('');
		
		// Check recaptcha
		$recaptcha_result = $this->recaptcha->check();
		
		// Store recaptcha pass in session so that users only needs to complete captcha once
		if ($recaptcha_result === TRUE) $this->session->set_userdata('sign_up_recaptcha_pass', TRUE);
		
		// Setup form validation
		$this->form_validation->set_error_delimiters('<span class="field_error">', '</span>');
		$this->form_validation->set_rules(array(
			array('field'=>'sign_up_username', 'label'=>'lang:sign_up_username', 'rules'=>'trim|required|alpha_dash|min_length[2]|max_length[24]'),
			array('field'=>'sign_up_password', 'label'=>'lang:sign_up_password', 'rules'=>'trim|required|min_length[6]'),
			array('field'=>'sign_up_email', 'label'=>'lang:sign_up_email', 'rules'=>'trim|required|valid_email|max_length[160]')
		));
		
		// Run form validation
		if ($this->form_validation->run() === TRUE) 
		{
			// Check if user name is taken
			if ($this->username_check($this->input->post('sign_up_username')) === TRUE)
			{
				$data['sign_up_username_error'] = lang('sign_up_username_taken');
			}
			// Check if email already exist
			elseif ($this->email_check($this->input->post('sign_up_email')) === TRUE)
			{
				$data['sign_up_email_error'] = lang('sign_up_email_exist');
			}
			// Either already pass recaptcha or just passed recaptcha
			elseif ( ! ($this->session->userdata('sign_up_recaptcha_pass') == TRUE || $recaptcha_result === TRUE) && $this->config->item("sign_up_recaptcha_enabled") === TRUE)
			{
				$data['sign_up_recaptcha_error'] = $this->input->post('recaptcha_response_field') ? lang('sign_up_recaptcha_incorrect') : lang('sign_up_recaptcha_required');
			}
			else 
			{
				// Remove recaptcha pass
				$this->session->unset_userdata('sign_up_recaptcha_pass');
				
				// Create user
				$user_id = $this->account_model->create($this->input->post('sign_up_username'), $this->input->post('sign_up_email'), $this->input->post('sign_up_password'));
				
				// Add user details (auto detected country, language, timezone)
				$this->account_details_model->update($user_id);
				
				$account = $this->account_model->get_by_id($user_id);
				
				// send the welcome email
				$this->load->library('email');
				$email_validate_url = site_url('account/validate_email?id='.$account->id.'&token='.sha1($account->id.$this->config->item('password_reset_secret')));
				
				// Send validation email
				$this->email->from($this->config->item('password_reset_email'), lang('validate_email_sender'));
				$this->email->to($account->email);
				$this->email->subject(lang('welcome_email_subject'));
				$this->email->message($this->load->view('email_validate', array('username' => $account->username, 'email_validate_url' => anchor($email_validate_url, $email_validate_url)), TRUE));
				@$this->email->send();
				
				// Auto sign in?
				if ($this->config->item("sign_up_auto_sign_in"))
				{
					// Run sign in routine
					$this->authentication->sign_in($user_id);
				}
				redirect('account/sign_in');
			}
		}
		
		// Load recaptcha code
		if ($this->config->item("sign_up_recaptcha_enabled") === TRUE)
			if ($this->session->userdata('sign_up_recaptcha_pass') != TRUE) 
				$data['recaptcha'] = $this->recaptcha->load($recaptcha_result, $this->config->item("ssl_enabled"));
		
		// Load sign up view
		$this->load->view('sign_up', isset($data) ? $data : NULL);
	}
	
	/**
	 * Check if a username exist
	 *
	 * @access public
	 * @param string
	 * @return bool
	 */
	function username_check($username)
	{
		return $this->account_model->get_by_username($username) ? TRUE : FALSE;
	}
	
	/**
	 * Check if an email exist
	 *
	 * @access public
	 * @param string
	 * @return bool
	 */
	function email_check($email)
	{
		return $this->account_model->get_by_email($email) ? TRUE : FALSE;
	}
	
}


/* End of file sign_up.php */
/* Location: ./system/application/modules/account/controllers/sign_up.php */