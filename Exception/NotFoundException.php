<?php
namespace Eyja\RestBundle\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Named http exception
 */
class NotFoundException extends HttpException {
	/**
	 * Constructor.
	 *
	 * @param string     $message  The internal exception message
	 * @param \Exception $previous The previous exception
	 * @param integer    $code     The internal exception code
	 */
	public function __construct($message = null, \Exception $previous = null, $code = 0) {
		parent::__construct(404, $message, $previous, array(), $code);
	}
}
