<?php

namespace Eyja\RestBundle\Message;

use PhpCollection\Map;

class Collection {
	/** @var \ArrayIterator */
	protected $results;
	/** @var Map meta information about returned collection */
	protected $metadata;

	function __construct() {
		$this->results = new \ArrayIterator();
		$this->metadata = new Map();
	}

	/**
	 * @param Map $metadata
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
	}

	/**
	 * @return Map
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
}