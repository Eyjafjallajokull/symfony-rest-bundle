<?php

namespace Eyja\RestBundle\EventListener;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ViewListener {
	/** @var Serializer */
	private $serializer;

	public function setSerializer(Serializer $serializer) {
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
            $this->serializeResponse($request, $response, $result);
			$event->setResponse($response);
		}
	}

    protected function serializeResponse(Request $request, Response $response, $data) {
        $groups = $request->attributes->get('serialization_groups', array());
        $content = $this->serializeContent($data, $groups, 'json');
        $response->setContent($content);
        $response->headers->set('content-type', 'application/json');
    }

    protected function serializeContent($content, $groups, $type) {
        $serializationContext = SerializationContext::create();
        $serializationContext->enableMaxDepthChecks();
        $serializationContext->setGroups($groups);
        return $this->serializer->serialize($content, $type, $serializationContext);
    }

}
