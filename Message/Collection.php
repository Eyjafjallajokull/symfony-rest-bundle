<?php

namespace Eyja\RestBundle\Message;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class Collection {
	/** @var \ArrayIterator */
	protected $results;
	/** @var ParameterBag meta information about returned collection */
	protected $metadata;

	function __construct() {
		$this->results = new \ArrayIterator();
		$this->metadata = new ParameterBag();
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag $metadata
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
	}

	/**
	 * @return \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag
	 */
	public function getMetadata() {
		return $this->metadata;
	}

	/**
	 * @param \ArrayIterator $results
	 */
	public function setResults($results) {
		$this->results = $results;
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getResults() {
		return $this->results;
	}

	/**
	 * @return array
	 */
	public function getMetadataArray() {
		return $this->getMetadata()->all();
	}
}