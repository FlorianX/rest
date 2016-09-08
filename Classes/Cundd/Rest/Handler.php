<?php
/*
 *  Copyright notice
 *
 *  (c) 2016 Daniel Corn <info@cundd.net>, cundd
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 25.03.14
 * Time: 14:38
 */

namespace Cundd\Rest;

use Cundd\Rest\DataProvider\DataProviderInterface;
use Cundd\Rest\DataProvider\Utility;
use Traversable;

/**
 * Handler for requests
 *
 * @package Cundd\Rest
 */
class Handler implements CrudHandlerInterface
{
    /**
     * Current request
     *
     * @var Request
     */
    protected $request;

    /**
     * Unique identifier of the currently matching Domain Model
     *
     * @var string
     */
    protected $identifier;

    /**
     * Object Manager
     *
     * @var \Cundd\Rest\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Cundd\Rest\ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * Inject the object manager instance
     *
     * @param \Cundd\Rest\ObjectManager $objectManager
     */
    public function injectObjectManager(\Cundd\Rest\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Cundd\Rest\ResponseFactoryInterface $responseFactory
     */
    public function injectResponseFActory(\Cundd\Rest\ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Sets the current request
     *
     * @param \Cundd\Rest\Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        $this->identifier = null;
        return $this;
    }

    /**
     * Returns the current request
     *
     * @return \Cundd\Rest\Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            return $this->objectManager->getRequestFactory()->getRequest();
        }
        return $this->request;
    }

    /**
     * Returns the unique identifier of the currently matching Domain Model
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the unique identifier of the currently matching Domain Model
     *
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Returns the given property of the currently matching Model
     *
     * @param string $propertyKey
     * @return mixed
     */
    public function getProperty($propertyKey)
    {
        $dataProvider = $this->getDataProvider();
        $model = $dataProvider->getModelWithDataForPath($this->getIdentifier(), $this->getPath());
        if (!$model) {
            return $this->responseFactory->createSuccessResponse(null, 404);
        }
        return $dataProvider->getModelProperty($model, $propertyKey);
    }

    /**
     * Returns the data of the current Model
     *
     * @return array|integer Returns the Model's data on success, otherwise a descriptive error code
     */
    public function show()
    {
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        /* SHOW
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        $dataProvider = $this->getDataProvider();
        $model = $dataProvider->getModelWithDataForPath($this->getIdentifier(), $this->getPath());
        if (!$model) {
            return $this->responseFactory->createSuccessResponse(null, 404);
        }
        $result = $dataProvider->getModelData($model);
        if ($this->objectManager->getConfigurationProvider()->getSetting('addRootObjectForCollection')) {
            return array(
                Utility::singularize($this->getRequest()->getRootObjectKey()) => $result
            );
        }
        return $result;
    }

    /**
     * Replaces the currently matching Model with the data from the request
     *
     * @return array|integer Returns the Model's data on success, otherwise a descriptive error code
     */
    public function replace()
    {
        $dataProvider = $this->getDataProvider();

        $request = $this->getRequest();
        $data = $request->getSentData();
        $data['__identity'] = $this->getIdentifier();
        Dispatcher::getSharedDispatcher()->logRequest('update request', array('body' => $data));

        $oldModel = $dataProvider->getModelWithDataForPath($this->getIdentifier(), $this->getPath());
        if (!$oldModel) {
            return $this->responseFactory->createSuccessResponse(null, 404);
        }

        /** @var \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $model */
        $model = $dataProvider->getModelWithDataForPath($data, $this->getPath());
        if (!$model) {
            return $this->responseFactory->createSuccessResponse(null, 400);
        }

        $dataProvider->saveModelForPath($model, $this->getPath());
        $result = $dataProvider->getModelData($model);
        if ($this->objectManager->getConfigurationProvider()->getSetting('addRootObjectForCollection')) {
            return array(
                Utility::singularize($request->getRootObjectKey()) => $result
            );
        }
        return $result;
    }

    /**
     * Updates the currently matching Model with the data from the request
     *
     * @return array|integer Returns the Model's data on success, otherwise a descriptive error code
     */
    public function update()
    {
        $dataProvider = $this->getDataProvider();

        $request = $this->getRequest();
        $data = $request->getSentData();
        $data['__identity'] = $this->getIdentifier();
        Dispatcher::getSharedDispatcher()->logRequest('update request', array('body' => $data));

        $model = $dataProvider->getModelWithDataForPath($data, $this->getPath());

        if (!$model) {
            return $this->responseFactory->createSuccessResponse(null, 404);
        }

        $dataProvider->saveModelForPath($model, $this->getPath());
        $result = $dataProvider->getModelData($model);
        if ($this->objectManager->getConfigurationProvider()->getSetting('addRootObjectForCollection')) {
            return array(
                Utility::singularize($request->getRootObjectKey()) => $result
            );
        }
        return $result;
    }

    /**
     * Deletes the currently matching Model
     *
     * @return integer Returns 200 an success
     */
    public function delete()
    {
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        /* REMOVE																	 */
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        $dataProvider = $this->getDataProvider();
        $model = $dataProvider->getModelWithDataForPath($this->getIdentifier(), $this->getPath());
        if (!$model) {
            return $this->responseFactory->createSuccessResponse(null, 404);
        }
        $dataProvider->removeModelForPath($model, $this->getPath());
        return $this->responseFactory->createSuccessResponse(null, 200);
    }

    /**
     * Creates a new Model with the data from the request
     *
     * @return array|integer Returns the Model's data on success, otherwise a descriptive error code
     */
    public function create()
    {
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        /* CREATE																	 */
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        $dataProvider = $this->getDataProvider();

        $request = $this->getRequest();
        $data = $request->getSentData();
        Dispatcher::getSharedDispatcher()->logRequest('create request', array('body' => $data));

        /**
         * @var \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $model
         */
        $model = $dataProvider->getModelWithDataForPath($data, $this->getPath());
        if (!$model) {
            return $this->responseFactory->createSuccessResponse(null, 400);
        }

        $dataProvider->saveModelForPath($model, $this->getPath());
        $result = $dataProvider->getModelData($model);
        if ($this->objectManager->getConfigurationProvider()->getSetting('addRootObjectForCollection')) {
            return array(
                Utility::singularize($request->getRootObjectKey()) => $result
            );
        }
        return $result;
    }

    /**
     * List all Models
     *
     * @return array Returns all Models
     */
    public function listAll()
    {
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        /* LIST 																	 */
        /* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
        $dataProvider = $this->getDataProvider();

        $allModels = $dataProvider->getAllModelsForPath($this->getPath());
        if (!is_array($allModels) && $allModels instanceof Traversable) {
            $allModels = iterator_to_array($allModels);
        }

        $result = array_map(array($dataProvider, 'getModelData'), $allModels);
        if ($this->objectManager->getConfigurationProvider()->getSetting('addRootObjectForCollection')) {
            return array(
                $this->getRequest()->getRootObjectKey() => $result
            );
        }
        return $result;
    }

    /**
     * Configure the API paths
     */
    public function configureApiPaths()
    {
        $dispatcher = Dispatcher::getSharedDispatcher();

        /** @var HandlerInterface */
        $handler = $this;

        $dispatcher->registerPath($this->getPath(), function ($request) use ($handler, $dispatcher) {
            $handler->setRequest($request);

            /*
             * Handle a specific Model
             */
            $dispatcher->registerParameter('slug', function ($request, $identifier) use ($handler, $dispatcher) {
                $handler->setIdentifier($identifier);

                /*
                 * Get single property
                 */
                $getPropertyCallback = function ($request, $propertyKey) use ($handler) {
                    return $handler->getProperty($propertyKey);
                };
                $dispatcher->registerParameter('slug', $getPropertyCallback);

                /*
                 * Show a single Model
                 */
                $getCallback = function ($request) use ($handler) {
                    return $handler->show();
                };
                $dispatcher->registerGetMethod($getCallback);

                /*
                 * Replace a Model
                 */
                $replaceCallback = function ($request) use ($handler) {
                    return $handler->replace();
                };
                $dispatcher->registerPutMethod($replaceCallback);
                $dispatcher->registerPostMethod($replaceCallback);

                /*
                 * Update a Model
                 */
                $updateCallback = function ($request) use ($handler) {
                    return $handler->update();
                };
                $dispatcher->registerPatchMethod($updateCallback);

                /*
                 * Delete a Model
                 */
                $deleteCallback = function ($request) use ($handler) {
                    return $handler->delete();
                };
                $dispatcher->registerDeleteMethod($deleteCallback);
            });

            /*
             * Create a Model
             */
            $createCallback = function ($request) use ($handler) {
                return $handler->create();
            };
            $dispatcher->registerPostMethod($createCallback);

            /*
             * List all Models
             */
            $listCallback = function ($request) use ($handler) {
                return $handler->listAll();
            };
            $dispatcher->registerGetMethod($listCallback);
        });
    }

    protected function callExtbasePlugin($actionName, $pluginName, $controllerName = null, $extensionName = null, array $arguments = null)
    {

        $pluginNamespace = strtolower('tx_'. $extensionName . '_' . $pluginName);

        $_POST[$pluginNamespace]['controller'] = $controllerName;
        $_POST[$pluginNamespace]['action'] = $actionName;

        $keys = array_keys($arguments);
        foreach ($keys as $key) {
            $_POST[$pluginNamespace][$key] = $arguments[$key];
        }

        $configuration = [
            'extensionName' => $extensionName,
            'pluginName' => $pluginName
        ];

        if (!empty($vendorName)) {
            $configuration['vendorName'] = $vendorName;
        }

        $bootstrap = $this->objectManager->get(\TYPO3\CMS\Extbase\Core\Bootstrap::class);

        $response = $bootstrap->run('', $configuration);

        return $response;
    }

    /**
     * Returns the Data Provider
     *
     * @return DataProviderInterface
     */
    protected function getDataProvider()
    {
        return $this->objectManager->getDataProvider();
    }

    /**
     * Returns the current request's path
     *
     * @return string
     */
    protected function getPath()
    {
        return $this->getRequest()->path();
    }
}
