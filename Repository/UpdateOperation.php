<?php

namespace Eyja\RestBundle\Repository;


class UpdateOperation extends AbstractOperation {
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

    public function mergeEntities($oldEntity, $newEntity) {
        $metadata = $this->repositoryWrapper->getMetadata();
        foreach ($metadata->getFieldNames() as $fieldName) {
            $newValue = $metadata->getFieldValue($newEntity, $fieldName);
            if ($newValue !== null) {
                $metadata->setFieldValue($oldEntity, $fieldName, $newValue);
            }
        }
    }
}