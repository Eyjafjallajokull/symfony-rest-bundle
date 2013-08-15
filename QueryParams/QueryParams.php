<?php


namespace Eyja\RestBundle\QueryParams;


use Eyja\RestBundle\Exception\BadRequestException;
use JMS\Parser\SyntaxErrorException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class QueryParams {
	/** @var ParameterBag */
	protected $query;

	public function __construct(ParameterBag $query) {
		$this->query = $query;
	}

	public function getOffset() {
		$value = $this->query->getInt('offset', 0);
		if ($value < 0 || $value > 200) {
			throw new BadRequestException('Invalid value for limit parameter');
		}
		return $value;
	}

	public function getLimit($defaultLimit = 25) {
		$value = $this->query->getInt('limit', $defaultLimit);
		if ($value < 0) {
			throw new BadRequestException('Invalid value for offset parameter');
		}
		return $value;
	}

	public function getFilters() {
		$filters = $this->query->get('filter', '');
		if (!empty($filters)) {
			try {
				$fp = new FilterParser();
				$filters = $fp->parse($filters);
			} catch (SyntaxErrorException $e) {
				throw new BadRequestException('Invalid filter definition. '.$e->getMessage(), null, $e);
			}
		} else {
			return null;
		}
		return $filters;
	}
}