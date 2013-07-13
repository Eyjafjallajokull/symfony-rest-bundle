<?php

namespace Eyja\RestBundle\Routing;

class RestRoutes {
	const GET_SINGLE = 'getSingle';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const GET_COLLECTION = 'getCollection';
	const CREATE = 'create';

	protected static $routes = array(
		'getSingle' => array(
			'name' => 'getSingle',
			'pattern' => '/{resource}/{id}',
			'methods' => array('GET'),
			'serialization_groups' => array('single')
		), 'update' => array(
			'name' => 'update',
			'pattern' => '/{resource}/{id}',
			'methods' => array('PUT'),
			'serialization_groups' => array('single')
		), 'delete' => array(
			'name' => 'delete',
			'pattern' => '/{resource}/{id}',
			'methods' => array('DELETE'),
			'serialization_groups' => array('single')
		), 'getCollection' => array(
			'name' => 'getCollection',
			'pattern' => '/{resource}',
			'methods' => array('GET'),
			'serialization_groups' => array('collection')
		), 'create' => array(
			'name' => 'create',
			'pattern' => '/{resource}',
			'methods' => array('POST'),
			'serialization_groups' => array('single')
		),
	);

	public static function getRoute($name) {
		if (!array_key_exists($name, self::$routes)) {
			throw new \InvalidArgumentException('Invalid route name '.$name);
		}
		return self::$routes[$name];
	}

    public static function getRouteName($resource, $action) {
        return 'rest_'.$resource.'_'.$action;
    }
}