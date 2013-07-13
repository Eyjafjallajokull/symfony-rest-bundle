<?php

namespace Eyja\RestBundle\EventListener;

use Eyja\RestBundle\Controller\RestRepositoryController;
use Eyja\RestBundle\Routing\RestRoutes;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ControllerListener {
	/** @var Serializer */
	private $serializer;

	public function setSerializer(Serializer $serializer) {
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
			($request->isMethod('post') || $request->isMethod('put')) &&
			$request->headers->get('content-type') === 'application/json'
		) {
			$content = $request->getContent();
			$objectClass = $controller->getRepository()->getClassName();
			$object = $this->serializer->deserialize($content, $objectClass, 'json');
			$request->attributes->set('entity', $object);
		}
	}
}
