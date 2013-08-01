<?php

namespace Eyja\RestBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Eyja\RestBundle\Exception\BadRequestException;
use Eyja\RestBundle\Exception\NotFoundException;
use Doctrine\ORM\EntityRepository;
use Eyja\RestBundle\Message\Collection;
use Eyja\RestBundle\OData\FilterParser;
use Eyja\RestBundle\Repository\CreateOperation;
use Eyja\RestBundle\Repository\GetCollectionOperation;
use Eyja\RestBundle\Repository\GetSingleOperation;
use Eyja\RestBundle\Repository\RepositoryWrapper;
use Eyja\RestBundle\Routing\RestRoutes;
use Eyja\RestBundle\Utils\RestRepositoryQueryParams;
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
	/** @var RestRepositoryQueryParams */
	protected $query;

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
	 * Set container
	 *
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container = null) {
		parent::setContainer($container);
		$this->query = new RestRepositoryQueryParams($this->getRequest());
	}

	/**
	 * Initialize RepositoryWrapper
	 */
	protected function getRepositoryWrapper() {
		if ($this->repositoryWrapper === null) {
			$this->repositoryWrapper = new RepositoryWrapper($this->getDoctrine()
				->getManager(), $this->getRepository());
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
		$limit = $this->query->getLimit();
		if ($limit < 0 || $limit > 200) {
			throw new BadRequestException('Invalid value for limit parameter');
		}
		$offset = $this->query->getOffset();
		if ($limit < 0) {
			throw new BadRequestException('Invalid value for offset parameter');
		}

		$fp = new FilterParser();
		$filters = $this->query->getFilters();
		if (!empty($filters)) {
			try {
				$filters = $fp->parse($filters);
			} catch (SyntaxErrorException $e) {
				throw new BadRequestException('Invalid filter definition. '.$e->getMessage(), null, $e);
			}
		}

		/** @var GetCollectionOperation $operation */
		$operation = $this->getRepositoryWrapper()->getOperation('getCollection')
			->setLimit($limit)->setOffset($offset);
		if (!empty($filters)) {
			$operation->setFilters($filters);
		}
		$results = $operation->execute();
		$total = $operation->getCollectionTotal();

		// create response message
		$response = new Collection();
		$response->setResults(new \ArrayIterator($results));
		$metadata = $response->getMetadata();
		$metadata->set('total', $total);
		$metadata->set('limit', $limit);
		$metadata->set('offset', $offset);

		// create next/prev links
		// @todo remember to include other query params (filter, sort)
		if ($limit + $offset < $total) {
			$url = $this->getRestUrl('getCollection', array(), array('limit' => $limit, 'offset' => $offset + $limit));
			$metadata->set('next', $url);
		}
		if ($offset > 0) {
			$url = $this->getRestUrl('getCollection', array(), array('limit' => $limit,
				'offset' => $offset - $limit ? : 0));
			$metadata->set('previous', $url);
		}
		return $response;
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