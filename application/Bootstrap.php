<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDoctype()
    {
        $this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_TRANSITIONAL');
		
		$view->headTitle('TenBrain - Save your Brain, While saving Money!')
             ->setSeparator(' - ');
	}
	
    protected function _initRoutes()
    {
		$front_controller = Zend_Controller_Front::getInstance();
		$router = $front_controller->getRouter();
		$router->addRoute('default_selection', new Zend_Controller_Router_Route('', array(
			'controller'	=> 'selection',
			'action'		=> 'index'
		)));
		unset($front_controller);
    }
	
    protected function _initAutoload()
	{
	    $this->options = $this->getOptions();
	    Zend_Registry::set('config.recaptcha', $this->options['recaptcha']);
	}
}
