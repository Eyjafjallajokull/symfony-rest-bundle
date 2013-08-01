<?php

namespace Eyja\RestBundle\Message;

class ExceptionMessage {
	/** @var string */
	protected $status;
	/** @var string */
	protected $message;
	/** @var string */
	protected $exception;
	/** @var array */
	protected $trace;
	/** @var array */
	protected $info;

	/**
	 * @param string $exception
	 */
	public function setException($exception) {
		$this->exception = $exception;
	}

	/**
	 * @return string
	 */
	public function getException() {
		return $this->exception;
	}

	/**
	 * @param array $info
	 */
	public function setInfo($info) {
		$this->info = $info;
	}

	/**
	 * @return array
	 */
	public function getInfo() {
		return $this->info;
	}

	/**
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @param string $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param array $trace
	 */
	public function setTrace($trace) {
		$this->trace = $trace;
	}

	/**
	 * @return array
	 */
	public function getTrace() {
		return $this->trace;
	}

}