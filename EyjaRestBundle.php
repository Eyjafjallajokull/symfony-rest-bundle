<?php

namespace Eyja\RestBundle;

use Eyja\RestBundle\DependencyInjection\AggregateRestControllersPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EyjaRestBundle extends Bundle {
	public function build(ContainerBuilder $container) {
		parent::build($container);
		$container->addCompilerPass(new AggregateRestControllersPass());
	}
}
