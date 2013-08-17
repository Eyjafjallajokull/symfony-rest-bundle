<?php

namespace Eyja\RestBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Eyja\RestBundle\Exception\BadRequestException;
use Eyja\RestBundle\Exception\NotFoundException;
use Doctrine\ORM\EntityRepository;
use Eyja\RestBundle\Message\Collection;
use Eyja\RestBundle\QueryParams\FilterParser;
use Eyja\RestBundle\Repository\CreateOperation;
use Eyja\RestBundle\Repository\GetCollectionOperation;
use Eyja\RestBundle\Repository\GetSingleOperation;
use Eyja\RestBundle\Repository\RepositoryWrapper;
use Eyja\RestBundle\Routing\RestRoutes;
use Eyja\RestBundle\QueryParams\QueryParams;
use JMS\Parser\SyntaxErrorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RestRepositoryController
 */
class RestRepositoryController extends RestController {
	/** @var string This name will be used in routes for this resource eg /product/1 */
	protected $resourceName;
	/** @var array This array is used by route generator, it will create only routes for allowed actions */
	protected $allowedActions = array('getSingle', 'getCollection', 'create', 'update', 'delete');
	/** @var EntityRepository */
	protected $repository;
	/** @var string Symfony name of Doctrine entity */
	protected $repositoryName;
	/** @var RepositoryWrapper */
	protected $repositoryWrapper;
	/** @var QueryParams */
	protected $query;
	/** @var array|null */
	protected $allowedFilterFields;

	public function __construct($repositoryName, $resourceName) {
		$this->setRepositoryName($repositoryName);
		$this->setResourceName($resourceName);
	}

	/**
	 * Set repository name
	 *
	 * @param $repositoryName
	 */
	public function setRepositoryName($repositoryName) {
		$this->repositoryName = $repositoryName;
	}

	/**
	 * Return repository name
	 *
	 * @return string
	 */
	public function getRepositoryName() {
		return $this->repositoryName;
	}

	/**
	 * Return repository
	 *
	 * @return EntityRepository
	 */
	public function getRepository() {
		if ($this->repository === null) {
			$this->repository = $this->getDoctrine()->getRepository($this->getRepositoryName());
		}
		return $this->repository;
	}

	/**
	 * Return resource name
	 *
	 * @return string
	 */
	public function getResourceName() {
		return $this->resourceName;
	}

	/**
	 * Set resource Name
	 *
	 * @param string $resourceName
	 */
	public function setResourceName($resourceName) {
		$this->resourceName = $resourceName;
	}

	/**
	 * Set allowed actions
	 *
	 * @param array $allowedActions
	 */
	public function setAllowedActions(array $allowedActions) {
		$this->allowedActions = $allowedActions;
	}

	/**
	 * Return allowed actions
	 *
	 * @return array
	 */
	public function getAllowedActions() {
		return $this->allowedActions;
	}

	/**
	 * Set allowed filter fields
	 *
	 * @param array|null $allowedFilterFields
	 */
	public function setAllowedFilterFields($allowedFilterFields) {
		$this->allowedFilterFields = $allowedFilterFields;
	}

	/**
	 * Return allowed filter fields
	 *
	 * @return array
	 */
	public function getAllowedFilterFields() {
		return $this->allowedFilterFields;
	}

	/**
	 * Set container
	 *
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container = null) {
		parent::setContainer($container);
		$this->query = new QueryParams($this->getRequest()->query);
	}

	/**
	 * Initialize RepositoryWrapper
	 */
	protected function getRepositoryWrapper() {
		if ($this->repositoryWrapper === null) {
			$this->repositoryWrapper = new RepositoryWrapper($this->getDoctrine()->getManager(), $this->getRepository());
			$this->setQueryWhere($this->repositoryWrapper->getBaseQuery(false));
		}
		return $this->repositoryWrapper;
	}

	/**
	 * Add additional where's to query
	 *
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 */
	protected function setQueryWhere(QueryBuilder $queryBuilder) {
	}

	/**
	 * Return single entity
	 *
	 * @param string $id
	 *
	 * @return mixed
	 * @throws \Eyja\RestBundle\Exception\NotFoundException
	 */
	public function getSingleAction($id) {
		/** @var GetSingleOperation $operation */
		$operation = $this->getRepositoryWrapper()->getOperation('getSingle');
		$entity = $operation->setId($id)->execute();
		return $entity;
	}

	/**
	 * Get collection of entities
	 *
	 * @throws \Eyja\RestBundle\Exception\BadRequestException
	 * @return \Eyja\RestBundle\Message\Collection
	 */
	public function getCollectionAction() {
		$limit = $this->query->getLimit($this->container->getParameter('eyja_rest.default_limit'));
		$offset = $this->query->getOffset();
		$filters = $this->query->getFilters();

		// validate filter fields
		// @todo find better place for this validation
		if ($filters) {
			$allowedFilterFields = $this->getAllowedFilterFields();
			if ($allowedFilterFields === null) {
				$metadata = $this->get('eyja_rest.metadata');
				$fieldsMetadata = $metadata->getFields($this->getRepository()->getClassName());
				$allowedFilterFields = array_keys($fieldsMetadata);
			}
			$this->validateFilterFields($allowedFilterFields, $filters);
		}

		// create repository operation
		/** @var GetCollectionOperation $operation */
		$operation = $this->getRepositoryWrapper()->getOperation('getCollection')
			->setLimit($limit)->setOffset($offset)->setFilters($filters);
		$results = $operation->execute();
		$total = $operation->getCollectionTotal();

		// create response message
		$response = new Collection();
		$response->setResults(new \ArrayIterator($results));
		$metadata = $response->getMetadata();
		$metadata->set('total', $total);
		$metadata->set('limit', $limit);
		$metadata->set('offset', $offset);
		if (!empty($filters)) {
			$total = $operation->getFilteredCollectionTotal();
			$metadata->set('totalFiltered', $total);
		}

		// create next/prev links
		$queryParams = $this->getRequest()->query;
		if ($limit + $offset < $total) {
			$newQuery = clone $queryParams;
			$newQuery->add(array('limit' => $limit, 'offset' => $offset + $limit));
			$url = $this->getRestUrl('getCollection', array(), $newQuery->all());
			$metadata->set('next', $url);
		}
		if ($offset > 0) {
			$newQuery = clone $queryParams;
			$newQuery->add(array('limit' => $limit, 'offset' => $offset - $limit ? : 0));
			$url = $this->getRestUrl('getCollection', array(), $newQuery->all());
			$metadata->set('previous', $url);
		}
		return $response;
	}

	private function validateFilterFields($allowedFilterFields, $filterNode) {
		if (!$filterNode) {
			return;
		}
		if ($filterNode['type'] == 'expression') {
			if (!in_array($filterNode['field'], $allowedFilterFields)) {
				throw new BadRequestException('Field "'.$filterNode['field'].'" is not allowed in filters');
			}
		} else {
			foreach ($filterNode['children'] as $child) {
				$this->validateFilterFields($allowedFilterFields, $child);
			}
		}
	}

	/**
	 * Save entity action
	 *
	 * @return mixed
	 */
	public function createAction() {
		$entity = $this->getRequest()->attributes->get('entity');
		$this->validateEntity($entity, 'create');

		/** @var CreateOperation $operation */
		$operation = $this->getRepositoryWrapper()->getOperation('create')->setEntity($entity);
		$entity = $operation->execute();

//        $id = $this->getRepositoryWrapper()->getIdValue($entity);
//        $url = $this->getRestUrl('getSingle', array('id' => $id));
//        @todo add this header to response  array('Location' => $url)
		// @todo status code should be 201
		return $entity;
	}

	/**
	 * Return url with domain and protocol
	 *
	 * @param $action
	 * @param array $parameters
	 * @param array $query
	 * @return string
	 */
	protected function getRestUrl($action, $parameters = array(), array $query = null) {
		$url = $this->generateUrl(RestRoutes::getRouteName($this->getResourceName(), $action), $parameters);
		$url = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . $url;
		if (!empty($query)) {
			$url .= '?' . http_build_query($query);
		}
		return $url;
	}

	/**
	 * Update entity
	 *
	 * @param string $id
	 *
	 * @return mixed
	 * @throws \Eyja\RestBundle\Exception\BadRequestException
	 */
	public function updateAction($id) {
		$oldEntity = $this->getSingleAction($id);
		$newEntity = $this->getRequest()->attributes->get('entity');

		// check ids
//		$idValue = $this->getRepositoryWrapper()->getIdValue($newEntity);
//		if ($idValue !== null && $idValue !== $id) {
//			throw new BadRequestException('ID value in entity should be unset or identical to ID in url.');
//		}

		$operation = $this->getRepositoryWrapper()->getOperation('update');
		$operation->mergeEntities($oldEntity, $newEntity);
		$operation->setEntity($oldEntity);

		$this->validateEntity($oldEntity, 'update');

		$entity = $operation->execute();
		return $entity;
	}

	/**
	 * Deletes entity
	 *
	 * @param string $id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction($id) {
		$entity = $this->getSingleAction($id);
		$this->getRepositoryWrapper()->getOperation('delete')->setEntity($entity)->execute();
		return new Response('', 204);
	}

	/**
	 * Validates entity
	 *
	 * @param mixed $entity
	 * @param null|array $groups
	 *
	 * @throws \Eyja\RestBundle\Exception\BadRequestException
	 */
	protected function validateEntity($entity, $groups = null) {
		if (empty($entity)) {
			$missingHeader = false;
			if ($this->getRequest()->headers->get('content-type')) {
				$missingHeader = true;
			}
			throw new BadRequestException('Empty entity. ' . ($missingHeader ? 'Content-type header is missing' : ''));
		}
		$violations = $this->get('validator')->validate($entity, $groups);
		if ($violations->count() != 0) {
			throw new BadRequestException('Entity validation error', $violations);
		}
	}
}