<?php

namespace Eyja\RestBundle\Repository;

/**
 * Creates entity
 */
class CreateOperation extends AbstractOperation {
	protected $entity;

	public function setEntity($entity) {
		$this->entity = $entity;
		return $this;
	}

	public function execute() {
		$this->repositoryWrapper->assignAssociatedEntities($this->entity);
		$manager = $this->repositoryWrapper->getManager();
		$manager->persist($this->entity);
		$manager->flush();
		return $this->entity;
	}
}