<?php

use Phalcon\Mvc\User\Plugin;

class AuthenticationPlugin extends Plugin
{
	public function getACL()
	{
		$acl = new Phalcon\Acl\Adapter\Memory();
		$acl->setDefaultAction(Phalcon\Acl::DENY);

		//Register roles
		$roles = array(
			'users'  => new Phalcon\Acl\Role("Administrators", "Super-User role"),
			'guests' => new Phalcon\Acl\Role("Guests")
		);
		
		foreach ($roles as $role)
			$acl->addRole($role);
		
		//Private area resources // Define the "NiuUsrInfo" resource //$customersResource = new Phalcon\Acl\Resource("NiuUsrInfo");
		$privateResources = array(
			'NiuUsrInfo'	=> array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
			//'producttypes'	=> array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
			'invoices'		=> array('index', 'profile')
		);
		
		// Add "NiuUsrInfo" resource with a couple of operations // $acl->addResource($customersResource, array("search", "update", "create"));
		foreach ($privateResources as $resource => $actions)
			$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
		
		//Public area resources
		$publicResources = array(
			'index'      => array('index'),
			'about'      => array('index'),
			'register'   => array('index'),
			'errors'     => array('show401', 'show404', 'show500'),
			'session'    => array('index', 'register', 'start', 'end'),
			'contact'    => array('index', 'send')
		);
		
		foreach ($publicResources as $resource => $actions)
			$acl->addResource(new Resource($resource), $actions);
		
		//Grant access to public areas to both users and guests
		foreach ($roles as $role) 
			foreach ($publicResources as $resource => $actions) 
				foreach ($actions as $action)
					$acl->allow($role->getName(), $resource, $action);
			
		// Set access level for roles into resources $acl->allow("Guests", "NiuUsrInfo", "search");		$acl->deny("Guests", "NiuUsrInfo", "create");
		//Grant acess to private area to role Users
		foreach ($privateResources as $resource => $actions) 
			foreach ($actions as $action)
				$acl->allow('Users', $resource, $action);


	}
   /**
	 * This action is executed before execute any action in the application
	 *
	 * @param Event $event
	 * @param Dispatcher $dispatcher
	 */
	public function beforeDispatch(Event $event, Dispatcher $dispatcher)
	{
		$auth = $this->session->get('auth');
		if (!$auth){
			$role = 'Guests';
		} else {
			$role = 'Users';
		}
		$controller = $dispatcher->getControllerName();
		$action = $dispatcher->getActionName();
		$acl = $this->getAcl();
		$allowed = $acl->isAllowed($role, $controller, $action);
		if ($allowed != Acl::ALLOW) {
			$dispatcher->forward(array(
				'controller' => 'errors',
				'action'     => 'show401'
			));
                        $this->session->destroy();
			return false;
		}
	}
}