<?php

class Selection extends CI_Controller {

	private $_variants;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('language', 'url'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		$this->output->enable_profiler(true);
		
		$this->_variants = array(
			'tenstack'		=> array(
				'business'	=> array(
					'type'		=> 'big',
					'disabled'	=> true,
					'text'		=> 'Business<br />Stack'
				),
				'enterprise'=>  array(
					'type'		=> 'big',
					'disabled'	=> true,
					'text'		=> 'Enterprise<br />Stack'
				),
				'web'		=>  array(
					'type'		=> 'big',
					'disabled'	=> false,
					'text'		=> 'UC<br />Stack'
				)
			),
			'deployment'	=> array(
				'desktop'	=>  array(
					'type'		=> 'big',
					'disabled'	=> true,
					'text'		=> 'Desktop<br />Deployment'
				),
				'enterprise'=> array(
					'type'		=> 'big',
					'disabled'	=> true,
					'text'		=> 'Enterprise<br />Deployment'
				),
				'cloud'		=> array(
					'type'		=> 'big',
					'disabled'	=> false,
					'text'		=> 'Cloud<br />Deployment'
				)
			),
			'os'	=> array(
				'linux'		=> array(
					'type'		=> 'small',
					'disabled'	=> false,
					'text'		=> 'Linux'
				),
				'windows'	=> array(
					'type'		=> 'small',
					'disabled'	=> true,
					'text'		=> 'Windows'
				),
				'mac'		=> array(
					'type'		=> 'small',
					'disabled'	=> true,
					'text'		=> 'Mac'
				)
			),
			'vm'			=> array(
				'vmware'	=> array(
					'type'		=> 'small',
					'disabled'	=> true,
					'text'		=> 'Vmware'
				),
				'citrix'	=> array(
					'type'		=> 'small',
					'disabled'	=> true,
					'text'		=> 'Citrix Xen'
				),
				'kvm'		=> array(
					'type'		=> 'small',
					'disabled'	=> true,
					'text'		=> 'Kvm'
				)
			),
			'providers'		=> array(
				'amazon'	=> array(
					'type'		=> 'small',
					'disabled'	=> false,
					'text'		=> 'AWS'
				),
				'gogrid'	=> array(
					'type'		=> 'small',
					'disabled'	=> true,
					'text'		=> 'GoGrid'
				),
				'rackspace'	=> array(
					'type'		=> 'small',
					'disabled'	=> false,
					'text'		=> 'Rackspace'
				)
			)
		);
	}
	
	function index()
	{
		$this->tenstack();
	}
	
	function tenstack()
	{
		$this->session->unset_userdata('tenstack');
		$this->session->unset_userdata('deployment');
		
		$type = 'tenstack';
		$this->load->view('selection', array(
			'type'			=> $type,
			'next'			=> 'selection/deployment',
			'selections'	=> $this->_variants[$type]
		));	
	}
	
	function deployment($tenstack)
	{
		if(in_array($tenstack, array_keys($this->_variants['tenstack'])))
		{
			$this->session->set_userdata('tenstack', $tenstack);
			
			$type = 'deployment';
			$this->load->view('selection', array(
				'type'			=> $type,
				'next'			=> 'selection/finals',
				'selections'	=> $this->_variants[$type]
			));
		}
		else
		{
			$this->load->view('error', array(
				'message' => 'selection failed'
			));			
		}
	}
	
	function finals($deployment)
	{
		if(in_array($deployment, array_keys($this->_variants['deployment'])))
		{
			$this->session->set_userdata('deployment', $deployment);
			
			$deploy_aliases = array(
				'desktop'	=> 'os',
				'enterprise'=> 'vm',
				'cloud'		=> 'providers'
			);
			
			$this->load->view('selection', array(
				'type'			=> $deploy_aliases[$deployment],
				'next'			=> 'selection/results',
				'selections'	=> $this->_variants[$deploy_aliases[$deployment]]
			));
		}
		else
		{
			$this->load->view('error', array(
				'message' => 'selection failed'
			));
		}
	}
	
	function results($finals)
	{
		$deploy_aliases = array(
			'desktop'	=> 'os',
			'enterprise'=> 'vm',
			'cloud'		=> 'providers'
		);
		$deployment = $this->session->userdata('deployment');
		$dep = $deploy_aliases[$deployment];
		if(in_array($finals, array_keys($this->_variants[$dep])))
		{
			$tenstack = $this->session->userdata('tenstack');
			
			$this->session->unset_userdata('tenstack');
			$this->session->unset_userdata('deployment');
			
			$this->session->set_userdata(array(
				'selection' => array(
					'tenstack'		=> $tenstack,
					'deployment'	=> $deployment,
					$dep			=> $finals // ????????????
				)
			));
			
			$this->load->view('results', array(
				'results'	=> array(
					'tenstack ' . $tenstack		=> $this->_variants['tenstack'][$tenstack]['text'],
					'deployment ' . $deployment	=> $this->_variants['deployment'][$deployment]['text'],
					$dep . ' ' . $finals		=> $this->_variants[$dep][$finals]['text']
				)
			));
		}
		else
		{
			$this->load->view('error', array(
				'message' => 'selection failed'
			));
		}
	}
	
	function confirm()
	{
		if(!$this->authentication->is_signed_in())
		{
			$this->session->set_userdata('sign_in_redirect', '/selection/confirm');
			redirect('account/sign_in');
		}
		$selection = $this->session->userdata('selection');
		if($selection)
		{
			//print_r($this->session->userdata('selection'));
			$user_name = $this->account_model->get_by_id($this->session->userdata('account_id'))->username;
			//die;
			switch($selection['providers'])
			{
				case 'rackspace':
					$this->load->model('Rackspace_model', 'rackspace');
					$this->rackspace->launch_instance('TenBrain UC Stack for ' . $user_name, 49, 1 );
				break;	
				case 'amazon':
					$this->load->model('Amazon_model', 'amazon');
					$this->amazon->launch_instance('ami-326c9f5b', 't1.micro', 'TenBrain UC Stack for ' . $user_name);
				break;
				case 'gogrid':
					echo 'Started GoGrid Instance';
					die;
					//$this->load->model('GoGrid_model', 'gogrid');
					//$this->gogrid->launch_instance('512MB', '5825', 'TenBrain UC Stack for ' . $user_name);
				break;
				default:
					$this->load->model('Amazon_model', 'amazon');
					$this->amazon->launch_instance('ami-326c9f5b', 't1.micro', 'TenBrain UC Stack for ' . $user_name);
				break;
			}
			$this->session->unset_userdata('selection');			
		}
		
		redirect('/control_panel');
	}
}

/* End of file selection.php */
/* Location: ./system/application/controllers/selection.php */