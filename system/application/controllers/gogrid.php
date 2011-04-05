<?php

class Gogrid extends Controller {

	function __construct()
	{
		parent::Controller();
		
		//authentication stuff:		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		// the model:
		$this->load->model('Gogrid_model', 'gg');
		
		header('Content-type: application/json');	// only xhr responses from this controller
		
		if(!$this->authentication->is_signed_in())
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> 'you do not have the permission to access this page'
			));
			die();
		}
	}

	function index()
	{
		error_reporting(E_ALL);
		header('Content-type: text/plain');
		$this->gg->test();
		die(PHP_EOL . 'voila! this is an gogrid controller index function');
	}
	
	function lookup($lookup)
	{
		print_r($this->gg->lookup($lookup));
	}
	
	function get_free_addresses()
	{
		$addresses = $this->gg->get_free_addresses();
		echo json_encode(array(
			'success'	=> (bool) $addresses,
			'addresses'	=> $addresses
		));
	}
	
	function get_available_ram_sizes()
	{
		$rams = $this->gg->get_available_ram_sizes();
		echo json_encode(array(
			'success'	=> (bool) $rams,
			'sizes'		=> $rams
		));
	}
	
	function launch_instance()
	{
		$params = array(
			'image'			=> $this->input->post('image_id'),
			'name'			=> $this->input->post('name'),
			'ip'			=> $this->input->post('address'),
			'server.ram'	=> $this->input->post('ram')
		);
		
		echo json_encode(array(
			'success' => $this->gg->launch_instance($params)
		));
	}
	
	function terminate_instance()
	{
		echo json_encode(array(
			'success' => $this->gg->delete_instance($this->input->post('instance_id'))
		));
	}
	
	function reboot_instance()
	{
		echo json_encode(array(
			'success' => $this->gg->restart_instance($this->input->post('instance_id'))
		));
	}
	
	function start_instance()
	{
		echo json_encode(array(
			'success' => $this->gg->start_instance($this->input->post('instance_id'))
		));
	}
	
	function stop_instance()
	{
		echo json_encode(array(
			'success' => $this->gg->stop_instance($this->input->post('instance_id'))
		));
	}
	
	function get_instance_password()
	{
		$password = $this->gg->get_password($this->input->post('instance_id'));
		$success = !empty($password);
		
		echo json_encode(array(
			'success'		=> $success,
			'error_message'	=> $success ? '' : 'You are not authorised to do this',
			'username'		=> $success ? $password['username'] : '',
			'password'		=> $success ? $password['password'] : '',
		));
	}
}

/* End of file gogrid.php */
/* Location: ./system/application/controllers/gogrid.php */