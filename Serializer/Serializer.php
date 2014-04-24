<?php

namespace Eyja\RestBundle\Serializer;

use Eyja\RestBundle\Exception\BadRequestException;
use Eyja\RestBundle\Message\ExceptionMessage;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer as JMSSerializer;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Serializer {
	/** @var JMSSerializer */
	private $serializer;

	/** @var array */
	private $supportedSerializationTypes = array(
		'application/json' => 'json',
		'application/xml' => 'xml'
	);

	/** @var array */
	private $supportedDeserializationTypes = array(
		'application/json' => 'json',
		'application/xml' => 'xml',
		'application/x-www-form-urlencoded' => 'query'
	);

	const DEFAULT_CONTENT_TYPE = 'application/json';

	/**
	 * Constructor
	 *
	 * @param JMSSerializer $serializer
	 */
	public function __construct(JMSSerializer $serializer) {
		$this->serializer = $serializer;
	}

	/**
	 * Serialize response
	 *
	 * Serialization type is defined by Accept header.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param mixed $data
	 * @param null|string $defaultContentType
	 * @throws \Exception
	 */
	public function serializeResponse(Request $request, Response $response, $data, $defaultContentType = null) {
		$acceptableTypes = $request->getAcceptableContentTypes();
		$acceptableTypes = str_replace('*/*', self::DEFAULT_CONTENT_TYPE, $acceptableTypes);
		if ($formatQuery = $request->query->get('format')) {
			$acceptableTypes = array($formatQuery);
		}
		$acceptableSupportedTypes = array_intersect($acceptableTypes, array_keys($this->supportedSerializationTypes));

		if (count($acceptableSupportedTypes) > 0) {
			$contentType = array_values($acceptableSupportedTypes);
			$contentType = $contentType[0];
			$type = $this->supportedSerializationTypes[$contentType];
		} else if ($defaultContentType !== null) {
			$contentType = $defaultContentType;
			$type = $this->supportedSerializationTypes[$contentType];
		} else {
			throw new BadRequestException('Unsupported or empty value in Accept header.');
		}
		if (!$data instanceof ExceptionMessage) {
			$groups = $request->attributes->get('serialization_groups', array());
		} else {
			$groups = null;
		}
		$content = $this->serializeContent($data, $type, $groups);
		$response->setContent($content);
		$response->headers->set('content-type', $contentType);
	}

	/**
	 * Serialized content body
	 *
	 * @param string $content
	 * @param string $type
	 * @param array $groups
	 * @return string
	 */
	public function serializeContent($content, $type, array $groups = null) {
		$serializationContext = SerializationContext::create();
		$serializationContext->enableMaxDepthChecks();
		if (!empty($groups)) {
			$serializationContext->setGroups($groups);
		}
		return $this->serializer->serialize($content, $type, $serializationContext);
	}

	/**
	 * Deserialize request
	 *
	 * Content-Type header defines serialization type
	 *
	 * @param Request $request
	 * @param $objectClass
	 * @return mixed
	 * @throws \Exception
	 */
	public function deserialize(Request $request, $objectClass) {
		$contentType = $request->headers->get('content-type');
		$contentType = substr($contentType, 0, strpos($contentType, ";"));

		if ($this->isDeserializationTypeSupported($contentType)) {
			$type = $this->supportedDeserializationTypes[$contentType];
			return $this->serializer->deserialize($request->getContent(), $objectClass, $type);
		} else {
			throw new BadRequestException('Unsupported or empty value in Content-Type header.');
		}
	}

	/**
	 * @param string $contentType
	 * @return bool
	 */
	public function isSerializationTypeSupported($contentType) {
		return array_key_exists($contentType, $this->supportedSerializationTypes);
	}

	/**
	 * @param string $contentType
	 * @return bool
	 */
	public function isDeserializationTypeSupported($contentType) {
		return array_key_exists($contentType, $this->supportedDeserializationTypes);
	}

}