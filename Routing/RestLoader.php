<?php

namespace Eyja\RestBundle\Routing;

use Eyja\RestBundle\Controller\RestRepositoryController;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RestLoader extends Loader {
	public function __construct($container) {
		$this->container = $container;
	}

	public function supports($resource, $type = null) {
		return 'rest' === $type;
	}

	public function load($resource, $type = null) {
		$routes = new RouteCollection();
		$importedRoutes = $this->import($resource, 'yaml');
		$routes->addCollection($importedRoutes);

		$restControllers = $this->container->get('eyja_rest.routing.collector')->toArray();
		foreach ($restControllers as $controllerServiceName) {
			/** @var $controller RestRepositoryController */
			$controller = $this->container->get($controllerServiceName);
			$controllerName = $this->getControllerName($controller);
			$resource = $controller->getResourceName();
			$actions = $controller->getAllowedActions();

			foreach ($actions as $actionName) {
				$action = RestRoutes::getRoute($actionName);
				$defaults = array(
					'_controller' => $controllerName.$actionName, 'is_rest_request' => true,
					'rest_action' => $actionName, 'serialization_groups' => $action['serialization_groups']
				);
				$pattern = str_replace('{resource}', $resource, $action['pattern']);
				$route = new Route($pattern, $defaults);
				$route->setMethods($action['methods']);
				$routes->add(RestRoutes::getRouteName($resource, $actionName), $route);
			}
		}

		return $routes;
	}

	public function getControllerName($controller) {
		$controllerClass = get_class($controller);
		$bundleName =
			($p1 = strpos($ns = $controllerClass, '\\')) === false ? $ns :
				substr($ns, 0, ($p2 = strpos($ns, '\\', $p1 + 1)) === false ? strlen($ns) : $p2);
		$bundleName = str_replace('\\', '', $bundleName);
		$controllerName = substr($controllerClass, strrpos($controllerClass, '\\') + 1);
		$controllerName = str_replace('Controller', '', $controllerName);
		$routeController = $bundleName.':'.$controllerName.':';
		return $routeController;
	}
}