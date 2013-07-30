<?php

namespace Eyja\RestBundle\Repository;

/**
 * Returns collection of entities
 */
class GetCollectionOperation extends AbstractOperation {
    /** @var int */
    protected $limit;
    /** @var int */
    protected $offset;

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

    public function execute() {
        $baseQueryBuilder = $this->repositoryWrapper->getBaseQuery();
        // fetch results
        $query = $baseQueryBuilder->getQuery();
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
}