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

	public function processFilterFields($allowedFilterFields, &$filterNode) {
		if (!$filterNode) {
			return;
		}
		if ($filterNode['type'] == 'expression') {
			if (!array_key_exists($filterNode['field'], $allowedFilterFields)) {
				throw new BadRequestException('Field "'.$filterNode['field'].'" is not allowed in filters');
			}
			$filterNode['field'] = $allowedFilterFields[$filterNode['field']]['databaseName'];
		} else {
			foreach ($filterNode['children'] as &$child) {
				$this->processFilterFields($allowedFilterFields, $child);
			}
		}
	}

	public function getOrder() {
		$filters = $this->query->get('order', '');
		if (!empty($filters)) {
			try {
				$op = new OrderParser();
				$filters = $op->parse($filters);
			} catch (SyntaxErrorException $e) {
				throw new BadRequestException('Invalid filter definition. '.$e->getMessage(), null, $e);
			}
		} else {
			return null;
		}
		return $filters;
	}

	public function processOrderFields($allowedFilterFields, &$orderDefinition) {
		foreach ($orderDefinition as &$order) {
			if (!array_key_exists($order['field'], $allowedFilterFields)) {
				throw new BadRequestException('Field "'.$order['field'].'" is not allowed in order definition');
			}
			$order['field'] = $allowedFilterFields[$order['field']]['databaseName'];
		}
	}
}