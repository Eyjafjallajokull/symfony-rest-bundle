<?php

namespace Eyja\RestBundle\Repository;

use Doctrine\ORM\NoResultException;
use Eyja\RestBundle\Exception\NotFoundException;

/**
 * Returns single entity
 */
class GetSingleOperation extends AbstractOperation {
	/** @var string|int */
	protected $id;

	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	public function execute() {
		$idField = $this->repositoryWrapper->getIdField();

		$queryBuilder = $this->repositoryWrapper->getBaseQuery();
		$queryBuilder->andWhere('c.' . $idField . ' = ' . $this->id);
		$query = $queryBuilder->getQuery();
		try {
			$entity = $query->getSingleResult();
		} catch (NoResultException $e) {
			throw new NotFoundException('Entity not found');
		}
		return $entity;
	}
}