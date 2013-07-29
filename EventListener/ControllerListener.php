<?php

namespace Eyja\RestBundle\EventListener;

use Eyja\RestBundle\Controller\RestRepositoryController;
use Eyja\RestBundle\Routing\RestRoutes;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Eyja\RestBundle\Serializer\Serializer;

class ControllerListener {
	/** @var Serializer */
	private $serializer;

	public function __construct(Serializer $serializer) {
		$this->serializer = $serializer;
	}

	public function onKernelController(FilterControllerEvent $event) {
		if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
			return;
		}
		$callable = $event->getController();
		$controller = $callable[0];
		$request = $event->getRequest();

		if ($controller instanceof RestRepositoryController &&
			($request->isMethod('post') || $request->isMethod('put'))
		) {
			$objectClass = $controller->getRepository()->getClassName();
			$object = $this->serializer->deserialize($request, $objectClass);
			$request->attributes->set('entity', $object);
		}
	}
}
