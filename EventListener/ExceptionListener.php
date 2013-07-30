<?php

namespace Eyja\RestBundle\EventListener;

use Eyja\RestBundle\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Eyja\RestBundle\Serializer\Serializer;

/**
 * Class ResponseListener
 */
class ExceptionListener {
	/** @var bool */
	private $debug;

	/** @var Serializer */
	private $serializer;

    /**
     * Constructor
     *
     * @param Serializer $serializer
     * @param bool $debug
     */
	public function __construct(Serializer $serializer, $debug) {
		$this->debug = (bool)$debug;
        $this->serializer = $serializer;
	}

	/**
	 * Formats pretty exception
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
		$data = array(
			'status' => $statusCode, 'message' => $exception->getMessage()
		);
		$this->addDebugInfo($data, $exception);
		$this->addAdditionalInfo($data, $exception);

		$response = new Response('', $statusCode);
        $this->serializer->serializeResponse($event->getRequest(), $response, $data, 'application/json');
        $event->setResponse($response);
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
        return $response;
	}
}