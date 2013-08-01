<?php

namespace Eyja\RestBundle\Serializer;

use JMS\Serializer\GenericDeserializationVisitor;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class QueryDeserializationVisitor extends GenericDeserializationVisitor {
	protected function decode($str) {
		parse_str($str, $decoded);
		return $decoded;
	}
}