<?php

namespace Eyja\RestBundle\Repository;
use Doctrine\ORM\QueryBuilder;
use Eyja\RestBundle\Exception\BadRequestException;

/**
 * Returns collection of entities
 */
class GetCollectionOperation extends AbstractOperation {
	/** @var int */
	protected $limit;
	/** @var int */
	protected $offset;
	/** @var array */
	protected $filters;
	/** @var array */
	protected $order;
	/** @var array */
	protected $params = array();
	protected $criteria;

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param int $offset
	 * @return $this
	 */
	public function setOffset($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param array $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
		return $this;
	}

	/**
	 * @param array $order
	 */
	public function setOrder($order) {
		$this->order = $order;
		return $this;
	}

	public function execute() {
		$baseQueryBuilder = $this->repositoryWrapper->getBaseQuery();

		$this->criteria = $this->compileFilters($baseQueryBuilder, $this->filters);
		if ($this->criteria) {
			$baseQueryBuilder->andWhere($this->criteria);
		}

		if ($this->order) {
			foreach ($this->order as $order) {
				$baseQueryBuilder->addOrderBy('c.' . $order['field'], $order['direction']);
			}
		}

		// fetch results
		$query = $baseQueryBuilder->getQuery();
		$query->setParameters($this->params);
		$query->useResultCache(true);
		$query->setFirstResult($this->offset);
		$query->setMaxResults($this->limit);
		$results = $query->getResult();
		return $results;
	}

	public function getCollectionTotal() {
		$baseQueryBuilder = $this->repositoryWrapper->getBaseQuery();
		$baseQueryBuilder->select('count(c)');
		$query = $baseQueryBuilder->getQuery();
		$total = (int)$query->getSingleScalarResult();
		return $total;
	}

	public function getFilteredCollectionTotal() {
		$baseQueryBuilder = $this->repositoryWrapper->getBaseQuery();
		$baseQueryBuilder->select('count(c)');
		if ($this->criteria) {
			$baseQueryBuilder->andWhere($this->criteria);
		}
		$query = $baseQueryBuilder->getQuery();
		$query->setParameters($this->params);
		$total = (int)$query->getSingleScalarResult();
		return $total;
	}

	protected function compileFilters(QueryBuilder $qb, array $filterNode = null) {
		if (empty($filterNode)) {
			return null;
		}
		if ($filterNode['type'] == 'expression') {
			$result = $this->compileCriteria($qb, $filterNode);
		} else {
			if ($filterNode['type'] == 'and') {
				$result = $qb->expr()->andX();
			} else {
				$result = $qb->expr()->orX();
			}
			foreach ($filterNode['children'] as &$child) {
				$result->add($this->compileFilters($qb, $child));
			}
		}
		return $result;
	}

	protected function compileCriteria(QueryBuilder $qb, array $comparison) {
		$operator = $comparison['operator'];
		switch ($operator) {
			case 'le':
				$operator = 'lte';
				break;
			case 'ge':
				$operator = 'gte';
				break;
			case 'ne':
				$operator = 'neq';
				break;
		}
		if (!in_array($operator, array('lte','gte','neq','eq','lt','gt','like'))) {
			throw new BadRequestException('Invalid comparison operator '.$operator);
		}
		$criteria = $qb->expr()->$operator('c.' . $comparison['field'], '?' . count($this->params));
		$this->params[] = $comparison['value'];
		return $criteria;
	}
}