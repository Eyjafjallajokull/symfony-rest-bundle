<?php

namespace Eyja\RestBundle\Repository;
use Doctrine\ORM\QueryBuilder;

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
    private $params = array();

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
    public function setFilters(array $filters) {
        $this->filters = $filters;
        return $this;
    }

    public function execute() {
        $baseQueryBuilder = $this->repositoryWrapper->getBaseQuery();

        $this->addFilters($baseQueryBuilder);

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

    protected function addFilters(QueryBuilder $qb, array $filters = null) {
        if ($filters == null) {
            $filters = $this->filters;
        }
	    if (empty($filters)) {
		    return;
	    }
        foreach ($filters['and'] as &$filter) {
            $filter = $this->filterToCriteria($qb, $filter);
        }
        $qb->andWhere(call_user_func_array(array($qb->expr(), 'andX'), $filters['and']));
    }

    protected function filterToCriteria(QueryBuilder $qb, array $filter) {
        $method = $filter[1];
        switch ($method) {
            case 'le': $method = 'lte'; break;
            case 'ge': $method = 'gte'; break;
        }
        $criteria = $qb->expr()->$method('c.'.$filter[0], '?'.count($this->params));
        $this->params[] = $filter[2];
        return $criteria;
    }
}