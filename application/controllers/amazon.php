<?php

class Amazon extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		//authentication stuff:
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));

		// the model:
		$this->load->model('Amazon_model', 'amazon');

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
		$this->amazon->test();
		die(PHP_EOL . 'voila! this is an amazon controller index function');
	}
	
	function get_user_credentials()
	{
		echo json_encode(array(
			'success'	=> true,
			'credentials'		=> $this->amazon->get_user_aws_credentials()
		));
	}
	
	function set_user_credentials()
	{
		$new_credentials = array();
		$new_credentials['key'] = $this->input->post('key');
		$new_credentials['user_id'] = $this->input->post('user_id');
		$new_credentials['secret_key'] = $this->input->post('secret_key');
		
		$credentials = $this->amazon->get_user_aws_credentials();
		
		$result = $credentials 
			? $this->amazon->update_user_aws_credentials($new_credentials)
			: $this->amazon->set_user_aws_credentials($new_credentials);
		
		echo json_encode($result);
	}
	
	function show_instances($state)
	{
		$states = array('running', 'terminated', 'stopped');
		if(empty($state) || !in_array($state, $states)) $state = 'running';

		$instances = $this->amazon->describe_instances($state);

		echo json_encode($instances);
	}

	function available_images()
	{
		$images = $this->amazon->describe_images();
		$amazon_last = count($images['images']);

		$this->load->model('Gogrid_model', 'gg');
		$gogrid_images = $this->gg->get_images();
		foreach($gogrid_images as $id => &$image)
		{
			$image['id'] = $amazon_last + $id;
		}
		$images['images'] = array_merge($images['images'], $gogrid_images);

		echo json_encode($images);
	}

	function get_available_instance_types()
	{
		echo json_encode(array(
			'success'	=> true,
			'types'		=> $this->amazon->get_available_instance_types()
		));
	}

	function launch_instance()
	{
		$image_id = $this->input->post('image_id');
		$type = $this->input->post('instance_type');
		$available_types = $this->amazon->available_types;
		if(!in_array($type, $available_types))
		{
			$type = $this->amazon->default_type;
		}

		$name = $this->input->post('instance_name');

		echo json_encode(array(
			'success' => $this->amazon->launch_instance($image_id, $type, $name)
		));
	}
	
	function terminate_instance()
	{
		$instances = $this->input->post('instances');
		if($instances)
		{
			$instances = json_decode($instances);
			foreach($instances as $instance)
			{
				$this->amazon->terminate_instance($instance);
			}
		}
		$instance = $this->input->post('instance_id');
		if($instance)
		{
			$this->amazon->terminate_instance($instance);
		}
		echo json_encode(array('success' => true));
	}

	function start_instance()
	{
		$instances = $this->input->post('instances');
		if($instances)
		{
			$instances = json_decode($instances);
			foreach($instances as $instance)
			{
				$this->amazon->start_instance($instance);
			}
		}
		$instance = $this->input->post('instance_id');
		if($instance)
		{
			$this->amazon->start_instance($instance);
		}
		echo json_encode(array('success' => true));
	}

	function stop_instance()
	{
		$instances = $this->input->post('instances');
		if($instances)
		{
			$instances = json_decode($instances);
			foreach($instances as $instance)
			{
				$this->amazon->stop_instance($instance);
			}
		}
		$instance = $this->input->post('instance_id');
		if($instance)
		{
			$this->amazon->stop_instance($instance);
		}
		echo json_encode(array('success' => true));
	}

	function reboot_instance()
	{
		$instances = $this->input->post('instances');
		if($instances)
		{
			$instances = json_decode($instances);
			foreach($instances as $instance)
			{
				$this->amazon->reboot_instance($instance);
			}
		}
		$instance = $this->input->post('instance_id');
		if($instance)
		{
			$this->amazon->reboot_instance($instance);
		}
		echo json_encode(array('success' => true));
	}

	function download_private_key()
	{
		$key = $this->amazon->download_user_private_key();
		if(!$key)
		{
			echo json_encode(array('success' => false));
			return false;
		}

		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $key['key_name'] . '.pem"');
		echo $key['private_key'];
		return false;
	}

	function created_backups()
	{
		echo json_encode($this->amazon->created_backups($this->input->post('instance_id')));
	}

	function backup_instance()
	{
		echo json_encode($this->amazon->describe_backup_instance($this->input->post('backup_id')));
	}

	function create_backup()
	{
		echo json_encode(array(
			'success' => $this->amazon->create_backup(
				$this->input->post('instance_id'),
				$this->input->post('name'),
				$this->input->post('description')
			)
		));
	}

	function delete_backup()
	{
		$snaps = $this->input->post('backups');
		if($snaps)
		{
			$snaps = json_decode($snaps);
			foreach($snaps as $snap)
			{
				$this->amazon->delete_backup($snap);
			}
		}
		$snap = $this->input->post('backup_id');
		if($snap)
		{
			$this->amazon->delete_backup($snap);
		}
		echo json_encode(array(
			'success' => true
		));
	}

	function restore_backup_to_corresponding_instance()
	{
		echo json_encode(array(
			'success' => $this->amazon->restore_backup_to_corresponding_instance($this->input->post('backup_id'))
		));
	}

	function restore_backup_to_new_instance()
	{
		echo json_encode(array(
			'success' => $this->amazon->restore_backup_to_new_instance(
				$this->input->post('backup_id'),
				$this->input->post('name'),
				$this->input->post('instance_type')
			)
		));
	}

	function transfer_instances()
	{
		$new_credentials = array(
			'user_id'		=> $this->input->post('account_id'),
			'key'			=> $this->input->post('key'),
			'secret_key'	=> $this->input->post('secret_key')
		);
		$transfer = $this->amazon->transfer_instances($new_credentials);
		echo json_encode(array('success' => $transfer));
	}

	function created_load_balancers()
	{
		echo json_encode($this->amazon->created_load_balancers());
	}

	function create_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->amazon->create_load_balancer(
				$this->input->post('name')
			)
		));
	}

	function delete_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->amazon->delete_load_balancer(
				$this->input->post('id')
			)
		));
	}

	function show_lb_instances()
	{
		$instances = $this->amazon->show_lb_instances($this->input->post('lb_name'));
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}

	function register_instances_with_lb()
	{
		$lb_name = $this->input->post('lb_name');
		$instances = json_decode($this->input->post('instances'));

		echo json_encode(array(
			'success'		=> $this->amazon->register_instances_with_load_balancer($lb_name, $instances),
			'error_message'	=> 'A problem has occurred'
		));
	}

	function deregister_instances_from_lb()
	{
		$lb_name = $this->input->post('lb_name');
		$instances = json_decode($this->input->post('instances'));

		echo json_encode(array(
			'success'		=> $this->amazon->deregister_instances_from_load_balancer($lb_name, $instances),
			'error_message'	=> 'A problem has occurred'
		));
	}

	function get_load_balanced_instances()
	{
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->amazon->get_load_balanced_instances($this->input->post('lb_name'))
		));
	}

	function elastic_ips()
	{
		echo json_encode(array(
			'success'		=> true,
			'elastic_ips'	=> $this->amazon->get_elastic_ips()
		));
	}

	function allocate_address()
	{
		$address = $this->amazon->allocate_address();
		echo json_encode(array(
			'success' => (bool) $address,
			'address' => $address
		));
	}

	function get_short_instances_list()	// for associating with an elastic IP
	{
		echo json_encode(array(
			'success'	=> true,
			'instances' => $this->amazon->get_short_instances_list()
		));
	}

	function associate_elastic_ip()
	{
		echo json_encode(array(
			'success'	=> $this->amazon->associate_ip(
				$this->input->post('instance_id'),
				$this->input->post('address')
			)
		));
	}

	function disassociate_address()
	{
		echo json_encode(array(
			'success'	=> $this->amazon->disassociate_ip($this->input->post('address'))
		));
	}

	function release_addresses()
	{
		$ips = json_decode($this->input->post('addresses'));

		echo json_encode(array(
			'success'	=> $this->amazon->release_ip($ips)
		));
	}
}

/* End of file amazon.php */
/* Location: ./system/application/controllers/amazon.php */