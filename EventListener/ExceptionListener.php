<?php

namespace Eyja\RestBundle\EventListener;

use Eyja\RestBundle\Exception\BadRequestException;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class ResponseListener
 */
class ExceptionListener {
	/** @var bool */
	private $debug;

	/** @var Serializer */
	private $serializer;

	/**
	 * konstruktor ustawia debug
	 *
	 * @param bool $debug
	 */
	public function __construct($debug) {
		$this->debug = (bool)$debug;
	}

	public function setSerializer(Serializer $serializer) {
		$this->serializer = $serializer;
	}

	/**
	 * Zamiana wyjątku na ładnego jsona
	 *
	 * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
	 */
	public function onKernelException(GetResponseForExceptionEvent $event) {
		$exception = $event->getException();

		// sprawdzamy czy exception wystąpił w RESTowym kontrolerze
		if ($event->getRequest()->attributes->get('is_rest_request', false) === false) {
			return;
		}

		$statusCode = 500;
		if ($exception instanceof HttpException) {
			$statusCode = $exception->getStatusCode();
		}
		$response = array(
			'status' => $statusCode, 'message' => $exception->getMessage()
		);
		$this->addDebugInfo($response, $exception);
		$this->addAdditionalInfo($response, $exception);

		$response = new Response($this->serialize($response), $statusCode);
		$response->headers->set('Content-Type', 'application/json');
		$event->setResponse($response);
	}

	/**
	 * Serializuje dane
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	private function serialize($data) {
		return $this->serializer->serialize($data, 'json');
	}

	/**
	 * @param $response
	 * @param $exception
	 */
	public function addDebugInfo(&$response, $exception) {
		if ($this->debug) {
			$response['exception'] = get_class($exception);
			$response['trace'] = explode("\n", $exception->getTraceAsString());
		}
	}

	/**
	 * @param $exception
	 * @param $response
	 *
	 * @return mixed
	 */
	public function addAdditionalInfo(&$response, $exception) {
		if ($exception instanceof BadRequestException) {
			$exceptionInfo = $exception->getInfo();
			if ($exceptionInfo instanceof ConstraintViolationList) {
				$info = array();
				foreach ($exceptionInfo as $violation) {
					$info[$violation->getPropertyPath()] = $violation->getMessage();
				}
				$response['info'] = $info;
				return $response;
			} else if (!empty($exceptionInfo)) {
				$response['info'] = $exceptionInfo;
				return $response;
			}
		}
	}
}