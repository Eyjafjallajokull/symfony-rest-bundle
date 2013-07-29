<?php

namespace Eyja\RestBundle\EventListener;

use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Eyja\RestBundle\Serializer\Serializer;

class ViewListener {
	/** @var Serializer */
	private $serializer;

	public function __construct(Serializer $serializer) {
		$this->serializer = $serializer;
	}

	public function onKernelView(GetResponseForControllerResultEvent $event) {
        $request = $event->getRequest();
        $attributes = $request->attributes;
		if ($attributes->get('is_rest_request', false) === true) {
			$result = $event->getControllerResult();
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response = new Response('', 200);
            }
            $this->serializer->serializeResponse($request, $response, $result);
			$event->setResponse($response);
		}
	}
}
