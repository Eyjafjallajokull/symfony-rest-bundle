<?php

namespace Eyja\RestBundle\Repository;

/**
 * Repository operation stub.
 */
abstract class AbstractOperation {
    /** @var \Eyja\RestBundle\Repository\RepositoryWrapper  */
    protected $repositoryWrapper;

    /**
     * Construct
     *
     * @param RepositoryWrapper $repositoryWrapper
     */
    public function __construct(RepositoryWrapper $repositoryWrapper) {
        $this->repositoryWrapper = $repositoryWrapper;
    }

    /**
     * Execute operation
     *
     * @return mixed
     */
    abstract public function execute();
}