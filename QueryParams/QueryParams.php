<?php


namespace Eyja\RestBundle\QueryParams;


use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class QueryParams {
	/** @var ParameterBag */
	protected $query;

	public function __construct(Request $request) {
		$this->query = $request->query;
	}

	public function getOffset() {
		return $this->query->getInt('offset', 0);
	}

	public function getLimit($defaultLimit = 25) {
		return $this->query->getInt('limit', $defaultLimit);
	}

	public function getFilters() {
		return $this->query->get('filter', '');
	}
}