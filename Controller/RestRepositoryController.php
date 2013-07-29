<?php

namespace Eyja\RestBundle\Controller;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Eyja\RestBundle\Exception\BadRequestException;
use Eyja\RestBundle\Exception\NotFoundException;
use Doctrine\ORM\EntityRepository;
use Eyja\RestBundle\Message\Collection;
use Eyja\RestBundle\Repository\RepositoryWrapper;
use Eyja\RestBundle\Routing\RestRoutes;
use Eyja\RestBundle\Utils\RestRepositoryQueryParams;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RestRepositoryController
 */
class RestRepositoryController extends RestController {
	/** @var string */
	protected $resourceName;
	/** @var array */
	protected $allowedActions = array('getSingle', 'getCollection', 'create', 'update', 'delete');
    /** @var EntityRepository */
    protected $repository;
    /** @var string */
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
			$this->repositoryWrapper = new RepositoryWrapper($this->getDoctrine()->getManager(), $this->getRepository());
		}
		return $this->repositoryWrapper;
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
		$idField = $this->getRepositoryWrapper()->getIdField();

		$queryBuilder = $this->createQuery();
		$queryBuilder->andWhere('c.'.$idField .' = '.$id);
		$query = $queryBuilder->getQuery();
		try {
			$entity = $query->getSingleResult();
		} catch (NoResultException $e) {
			throw new NotFoundException('Entity not found');
		}
		return $entity;
	}

    /**
     * Get collection of entities
     *
     * @throws \Eyja\RestBundle\Exception\BadRequestException
     * @return array
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

        $baseQueryBuilder = $this->createQuery();
        // fetch results
		$query = $baseQueryBuilder->getQuery();
		$query->useResultCache(true);
		$query->setFirstResult($offset);
		$query->setMaxResults($limit);
		$results = $query->getResult();

        // fetch total rows
		$baseQueryBuilder->select('count(c)');
		$query = $baseQueryBuilder->getQuery();
		$total = (int)$query->getSingleScalarResult();

        // create response message
        $response = new Collection();
        $response->setResults(new \ArrayIterator($results));
        $metadata = $response->getMetadata();
        $metadata->set('total', $total);
        $metadata->set('limit', $limit);
        $metadata->set('offset', $offset);

        // create next/prev links
        if ($limit+$offset < $total) {
            $url = $this->getRestUrl('getCollection').'?'.
                http_build_query(array('limit'=>$limit, 'offset'=>$offset+$limit));
            $metadata->set('next', $url);
        }
        if ($offset > 0) {
            $url = $this->getRestUrl('getCollection').'?'.
                http_build_query(array('limit'=>$limit, 'offset'=>$offset-$limit ?: 0));
            $metadata->set('previous', $url);
        }
		return $response;
	}

	/**
	 * Add additional where's to query
	 *
	 * @param QueryBuilder $queryBuilder
	 *
	 * @return QueryBuilder
	 */
	protected function setQueryWhere(QueryBuilder $queryBuilder) {
		return $queryBuilder;
	}

	/**
	 * Create base query
	 *
	 * @return QueryBuilder
	 */
	protected function createQuery() {
		$queryBuilder = $this->getRepositoryWrapper()->createQuery();
		$this->setQueryWhere($queryBuilder);
		return $queryBuilder;
	}

	/**
	 * Save entity action
	 *
	 * @return mixed
	 */
	public function createAction() {
		$entity = $this->getRequest()->attributes->get('entity');

		$this->validateEntity($entity, 'create');
		$this->getRepositoryWrapper()->assignAssociatedEntities($entity);

		$this->getRepositoryWrapper()->save($entity);

//        $id = $this->getRepositoryWrapper()->getIdValue($entity);
//        $url = $this->getRestUrl('getSingle', array('id' => $id));
//        @todo add this header to response  array('Location' => $url)
		return $entity;
	}

    protected function getRestUrl($action, $parameters = array()) {
        $url = $this->generateUrl(RestRoutes::getRouteName($this->getResourceName(), $action), $parameters);
        $url = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . $url;
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

		$metadata = $this->getRepositoryWrapper()->getMetadata();

		// check ids
		$idValue = $this->getRepositoryWrapper()->getIdValue($newEntity);
		if ($idValue !== null && $idValue !== $id) {
			throw new BadRequestException('ID value in entity should be unset or identical to ID in url.');
		}

		// merge entities
		foreach ($metadata->getFieldNames() as $fieldName) {
			$newValue = $metadata->getFieldValue($newEntity, $fieldName);
			if ($newValue !== null) {
				$metadata->setFieldValue($oldEntity, $fieldName, $newValue);
			}
		}

		$this->getRepositoryWrapper()->assignAssociatedEntities($oldEntity);

		$this->validateEntity($oldEntity, 'update');
		$this->getRepositoryWrapper()->save();
		return $oldEntity;
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
			throw new BadRequestException('Empty entity. '.($missingHeader ? 'Content-type header is missing' : ''));
		}
		$violations = $this->get('validator')->validate($entity, $groups);
		if ($violations->count() != 0) {
			throw new BadRequestException('Entity validation error', $violations);
		}
	}

	/**
	 * Deletes entity
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function deleteAction($id) {
		$entity = $this->getSingleAction($id);
		$this->getRepositoryWrapper()->remove($entity);
        return new Response('', 204);
	}

}