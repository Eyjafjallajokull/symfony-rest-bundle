<?php

namespace Eyja\RestBundle\EventListener;

use Eyja\RestBundle\Exception\BadRequestException;
use Eyja\RestBundle\Message\ExceptionMessage;
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

		$exceptionMessage = new ExceptionMessage();
		$exceptionMessage->setStatus($statusCode);
		$exceptionMessage->setMessage($exception->getMessage());
		$this->addDebugInfo($exceptionMessage, $exception);
		$this->addAdditionalInfo($exceptionMessage, $exception);

		$response = new Response('', $statusCode);
		$this->serializer->serializeResponse($event->getRequest(), $response, $exceptionMessage, 'application/json');
		$event->setResponse($response);
	}

	/**
	 * @param ExceptionMessage $exceptionMessage
	 * @param $exception
	 */
	public function addDebugInfo(ExceptionMessage $exceptionMessage, $exception) {
		if ($this->debug) {
			$exceptionMessage->setException(get_class($exception));
			$exceptionMessage->setTrace(explode("\n", $exception->getTraceAsString()));
		}
	}

	/**
	 * @param ExceptionMessage $exceptionMessage
	 * @param $exception
	 *
	 * @return mixed
	 */
	public function addAdditionalInfo(ExceptionMessage $exceptionMessage, $exception) {
		if ($exception instanceof BadRequestException) {
			$exceptionInfo = $exception->getInfo();
			if ($exceptionInfo instanceof ConstraintViolationList) {
				$info = array();
				foreach ($exceptionInfo as $violation) {
					$info[$violation->getPropertyPath()] = $violation->getMessage();
				}
				$exceptionMessage->setInfo($info);
			} else if (!empty($exceptionInfo)) {
				$exceptionMessage->setInfo($exceptionInfo);
			}
		}
		return $exceptionMessage;
	}
}