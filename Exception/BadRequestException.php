<?php

namespace Eyja\RestBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Named http exception
 */
class BadRequestException extends HttpException {
	/** @var string|array */
	private $info = '';

	/**
	 * Constructor.
	 *
	 * @param string $message  The internal exception message
	 * @param string|array $info
	 * @param \Exception $previous The previous exception
	 * @param integer $code     The internal exception code
	 */
	public function __construct($message = null, $info = null, \Exception $previous = null, $code = 0) {
		parent::__construct(400, $message, $previous, array(), $code);
		$this->setInfo($info);
	}

	/**
	 * Sets additional info
	 *
	 * @param string|array $info
	 */
	public function setInfo($info) {
		$this->info = $info;
	}

	/**
	 * Returns additional info
	 *
	 * @return array|string
	 */
	public function getInfo() {
		return $this->info;
	}
}
