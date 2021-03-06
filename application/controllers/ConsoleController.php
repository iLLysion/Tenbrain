<?php

class ConsoleController extends Zend_Controller_Action
{
	private $_acl = array();
	private $layout;

	public function init()
	{
		$this->_acl = new ZendExt_ACL(Zend_Auth::getInstance()->getIdentity());
		if(!$this->_acl->isUserAllowed('Console','view'))
			$this->_redirect("account/sign_in");
		$helper = $this->_helper->getHelper('Layout');
		$this->layout = $helper->getLayoutInstance(); 
		$this->layout->account_type = $this->_acl->_getUserRoleName;
	}
	
	public function indexAction()
	{
		/* set alternate layout */
		$this->layout->setLayout('console');
		$this->view->headTitle()->prepend('Tenbrain Control Panel');
		
		$this->layout->active_menu_item = 'running_instances';
		
		$scripts = array(
			'extjs4/ext-all-debug',
			'ux/layout/component/form/MultiSelect',
			'ux/layout/component/form/ItemSelector',
			'ux/form/MultiSelect',
			'ux/form/ItemSelector',
			
			'cp/instances',
			'cp/images',
			'cp/snapshots',
			'cp/load_balancers',
			'cp/elastic_ips',
			'cp/profile',
			'cp/transferer'
		);
		
		// if($this->account_type === 'premium') $scripts = array_merge($scripts, array('cp/load_balancers', 'cp/elastic_ips'));
		
		$scripts []= 'cp/cp';
		
		foreach($scripts as $script)
		{
			$this->view->headScript()->appendFile("/js/{$script}.js", 'text/javascript');
		}
	}
	
	public function menuAction()
	{
		// $profile_active = (bool) $this->session->userdata('active_menu_item');
		// $this->session->unset_userdata('active_menu_item');
		$profile_active = false;
		
		$menu = array();
		// Servers: 
		$menu []= array(
			'text'		=> 'Server Management',
			'expanded'	=> true,
			'children'	=> array(
				array(
					'text'	=> 'Running Servers',
					'id'	=> 'running_instances',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Stopped Servers',
					'id'	=> 'stopped_instances',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Terminated Servers',
					'id'	=> 'terminated_instances',
					'leaf'	=> true
				)
			)
		);
		
		// images: 
		$menu []= array(
			'text'		=> 'Available Images',
			'expanded'	=> true,
			'children'	=> array(
				array(
					'text'	=> 'Images available for deployment',
					'id'	=> 'available_images',
					'leaf'	=> true
				)
			)
		);
		
		// snapshots:
		$menu []= array(
			'text'		=> 'Backups',
			'expanded'	=> true,
			'children'	=> array(
				array(
					'text'	=> 'Created Backups',
					'id'	=> 'snapshots',
					'leaf'	=> true
				)
			)
		);
		
		// load balancers:
		$menu []= array(
			'text'		=> 'Load Balancers',
			'expanded'	=> true,
			'children'	=> array(
				array(
					'text'	=> 'Load Balancers',
					'id'	=> 'load_balancers',
					'leaf'	=> true
				)
			)
		);
		
		// elastic IP's
		$menu []= array(
			'text'		=> 'Elastic IPs',
			'expanded'	=> true,
			'children'	=> array(
				array(
					'text'	=> 'Elastic IPs',
					'id'	=> 'elastic_ips',
					'leaf'	=> true
				)
			)
		);
		
		$profile_menu = array(
			'text'		=> 'Your Profile',
			'expanded'	=> true,
			'children'	=> array(
				array(
					'text'	=> 'Profile Information',
					'id'	=> 'account_profile',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Linked accounts',
					'id'	=> 'account_linked',
					'leaf'	=> true
				)
			)
		);
		
		// to be rewritten to zend_auth:
		// if($this->account_model->get_by_id($this->session->userdata('account_id'))->password)
		{
			$profile_menu['children'] []= array(
				'text'	=> 'Password',
				'id'	=> 'account_password',
				'leaf'	=> true
			);
		}
		
		$menu []= $profile_menu;
		
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		
		header('Content-type: application/json');
		echo Zend_Json_Encoder::encode($menu);
	}

	public function cassietestAction()
	{
		
		$auth = Zend_Auth::getInstance();
		$user_id = $auth->getIdentity()->id;
		
		$ids = array(
			ZendExt_CassandraUtil::uuid1(),
			// ZendExt_CassandraUtil::uuid3('name'),
			ZendExt_CassandraUtil::uuid4(),
			uniqid('tralala')
			// ZendExt_CassandraUtil::uuid5('name')
		);
		print_r($ids);die;

		$cassie  = new ZendExt_Cassandra();
		$cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		$sample_servers = array(
			$ids[0]	=> array('provider' => 'Amazon', 'id' => '23456'),
			$ids[1]	=> array('provider' => 'Opennebula', 'id' => '12335'),
			$ids[2]	=> array('provider' => 'Azure', 'id' => '12335'),
			$ids[3]	=> array('provider' => 'Rackspace', 'id' => '12335'),
			$ids[4]	=> array('provider' => 'Gogrid', 'id' => '32145')
		);
		$cassie->SERVERS->batch_insert($sample_servers);
		
		foreach($ids as $id)
		{
			$cassie->USER_SERVERS->insert($user_id, array(
				$id => ''
			));
		}
		
		header('Content-type: text/html; charset=utf-8');
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		
		echo '<pre>';
		$my_server_ids = $cassie->USER_SERVERS->get($user_id);
		print_r($my_server_ids);
		$my_server_ids = array_keys($my_server_ids);
		print_r($my_server_ids);
		
		$my_servers = $cassie->SERVERS->multiget($my_server_ids);
		
		print_r($my_servers);
		
	}
	
	public function testAction()
	{
		$action = new ActionAllower();
		print_r($action->isAllowedGoGridFunctionality());die;
	}
}