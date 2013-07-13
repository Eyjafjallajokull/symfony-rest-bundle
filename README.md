= Symfony REST Framework

**Work in progress**

// introduction

== Setup

To get started, download this bundle:

    composer require eyja/rest-bundle dev-master

== Minimal example

// REVIEW and TEST this workflow

Create new bundle for Your REST API:

    php app/console generate:bundle \
        --no-interaction --namespace=Acme/RestBundle

Include new bundle:
  
    # app/config/routing.yml
    acme_hello:
        prefix: /api/v1/
        resource: @AcmeRestBundle/Resources/config/routing.yml
        type: rest

Create doctrine entitiy:

    php app/console generate:doctrine:entity \
       --non-interaction --entity=AcmeRestBundle:Post \
       --fields="id:integer name:string(100)" --format=yml

Create controller:

    # src/Acme/DemoBundle/Controller/CatController.php
    <?php

    namespace Eyja\RestDemoBundle\Controller;

    use Eyja\RestBundle\Controller\RestRepositoryController;

    class CatController extends RestRepositoryController {
        public function getRepository() {
            return $this->getDoctrine()->getRepository('AcmeRestBundle:Cat');
        }

        public function getResourceName() {
            return 'catgory';
        }
    }

Register controller as service:

```
# src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
<?php

namespace Eyja\RestDemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EyjaRestDemoExtension extends Extension {
  /**
	 * {@inheritDoc}
	 */
	public function load(array $configs, ContainerBuilder $container) {
		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
	}
}


# src/Acme/DemoBundle/Resources/config/services.yml
services:
  acme_demo.controller.cat:
    class: Eyja\RestDemoBundle\Controller\RestProductController
    tags: [ { name: rest.controller } ]
```

And last step - validation:

```
# src/Acme/DemoBundle/Resources/config/validation.yml
Acme\DemoBundle\Entity\Cat:
    properties:
        id:
            - NotBlank: {groups: [update]}
        name:
            - NotBlank: {groups: [update, create]}
```
