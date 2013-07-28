<?php

namespace Eyja\RestBundle\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Eyja\RestBundle\Exception\BadRequestException;

class RepositoryWrapper {
	protected $manager;
	protected $repository;
	/** @var \Doctrine\ORM\Mapping\ClassMetadata  */
	protected $metadata;

	public function __construct(ObjectManager $manager, EntityRepository $repository) {
		$this->manager = $manager;
		$this->repository = $repository;
		$entityClass = $this->repository->getClassName();
		$this->metadata = $this->manager->getClassMetadata($entityClass);
	}

	public function getMetadata() {
		return $this->metadata;
	}

	public function getIdField() {
		$idFields = $this->metadata->getIdentifierFieldNames();
		return $idFields[0];
	}

	public function createQuery() {
		return $this->repository->createQueryBuilder('c');
	}

	public function save($entity = null) {
		if ($entity !== null) {
			$this->manager->persist($entity);
		}
		$this->manager->flush();
	}

	public function remove($entity) {
		$this->manager->remove($entity);
		$this->save();
	}

	/**
	 * @param $entity
	 *
	 * @throws BadRequestException
	 */
	public function assignAssociatedEntities($entity) {
		foreach ($this->metadata->getAssociationMappings() as $association) {
			if (!empty($association['sourceToTargetKeyColumns'])) {
				foreach ($association['sourceToTargetKeyColumns'] as $from => $to) {
					$id = $idValue = $this->metadata->getFieldValue($entity, $from);
					$associatedEntity = $this->manager->getRepository($association['targetEntity'])->find($id);
					if ($associatedEntity === null) {
						throw new BadRequestException('Related entity id not found', array($from => 'Id not found'));
					}
					$this->metadata->setFieldValue($entity, $association['fieldName'],
						$this->manager->getReference($association['targetEntity'], $id));
				}
			}
		}
	}

    public function getIdValue($entity) {
        return $this->metadata->getFieldValue($entity, $this->getIdField());
    }
}