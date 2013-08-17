<?php

namespace Eyja\RestBundle\Utils;

use Metadata\Driver\LazyLoadingDriver;
use Metadata\MetadataFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RestResourceMetadata {
	/** @var  ContainerInterface */
	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	protected function getValidatorMetadata($class) {
		$metadataFactory = $this->container->get('validator.mapping.class_metadata_factory');
		return $metadataFactory->getMetadataFor($class);
	}

	protected function getDoctrineMetadata($class) {
		$d = $this->container->get('doctrine');
		/** @var \Doctrine\ORM\EntityManager $m */
		$m = $d->getManager();
		return $m->getClassMetadata($class);
	}

	protected function getSerializerMetadata($class) {
		$lld = new LazyLoadingDriver($this->container, 'jms_serializer.metadata_driver');
		$mf = new MetadataFactory($lld, 'Metadata\ClassHierarchyMetadata');
		$md = $mf->getMetadataForClass($class);
		return $md->propertyMetadata;
	}

	public function getFields($class) {
		$serializerMd = $this->getSerializerMetadata($class);
		$metadata = array();

		foreach ($serializerMd as $fieldName => $data) {
			$publicName = $data->serializedName ?: $fieldName;
			$metadata[$publicName] = array('type' => $data->type['name'], 'databaseName' => $fieldName);
		}
		return $metadata;
	}
}