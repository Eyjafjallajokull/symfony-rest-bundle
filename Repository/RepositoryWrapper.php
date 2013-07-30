<?php

namespace Eyja\RestBundle\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Eyja\RestBundle\Exception\BadRequestException;

/**
 * Class RepositoryWrapper
 * @package Eyja\RestBundle\Repository
 */
class RepositoryWrapper {
	protected $manager;
	protected $repository;
	/** @var \Doctrine\ORM\Mapping\ClassMetadata  */
	protected $metadata;
    protected $baseQuery;

    /**
     * Constructor
     *
     * @param ObjectManager $manager
     * @param EntityRepository $repository
     */
    public function __construct(ObjectManager $manager, EntityRepository $repository) {
		$this->manager = $manager;
		$this->repository = $repository;
		$entityClass = $this->repository->getClassName();
		$this->metadata = $this->manager->getClassMetadata($entityClass);
	}

    /**
     * Returns metadata object
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata|\Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getMetadata() {
		return $this->metadata;
	}

    /**
     * Returns doctrine manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager() {
        return $this->manager;
    }

    /**
     * Returns repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository() {
        return $this->repository;
    }

    /**
     * Return name of ID field
     *
     * @return string
     */
    public function getIdField() {
		$idFields = $this->metadata->getIdentifierFieldNames();
		return $idFields[0];
	}

    /**
     * Return ID value
     *
     * @param $entity
     * @return mixed
     */
    public function getIdValue($entity) {
        return $this->metadata->getFieldValue($entity, $this->getIdField());
    }

    /**
     * Return base query builder
     *
     * @param bool $clone If true return cloned query builder
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBaseQuery($clone = true) {
        if ($this->baseQuery === null) {
            $this->baseQuery = $this->repository->createQueryBuilder('c');
        }
        return $clone === true ? clone $this->baseQuery: $this->baseQuery;
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

    /**
     * Returns repository operation
     *
     * @param string $type
     * @return CreateOperation|DeleteOperation|GetCollectionOperation|GetSingleOperation|UpdateOperation
     * @throws \RuntimeException
     */
    public function getOperation($type) {
        switch ($type) {
            case 'getSingle': return new GetSingleOperation($this);
            case 'getCollection': return new GetCollectionOperation($this);
            case 'create': return new CreateOperation($this);
            case 'update': return new UpdateOperation($this);
            case 'delete': return new DeleteOperation($this);
            default: throw new \RuntimeException('Invalid operation type '.$type);
        }
    }
}