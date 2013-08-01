<?php

namespace Eyja\RestBundle\Repository;

/**
 * Deletes entity
 */
class DeleteOperation extends AbstractOperation {
	protected $entity;

	public function setEntity($entity) {
		$this->entity = $entity;
		return $this;
	}

	public function execute() {
		$manager = $this->repositoryWrapper->getManager();
		$manager->remove($this->entity);
		$manager->flush();
	}
}