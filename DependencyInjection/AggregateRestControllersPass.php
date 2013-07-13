<?php

namespace Eyja\RestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AggregateRestControllersPass implements CompilerPassInterface {
	public function process(ContainerBuilder $container) {
		$collector = $container->getDefinition('eyja_rest.routing.collector');
		foreach ($container->findTaggedServiceIds('rest.controller') as $id => $attributes) {
			$collector->addMethodCall('add', array($id));
		}
	}
}