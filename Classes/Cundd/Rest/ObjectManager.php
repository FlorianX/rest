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

namespace Cundd\Rest;

use Cundd\Rest\Path\PathUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface as TYPO3ObjectManagerInterface;
use \TYPO3\CMS\Extbase\Object\ObjectManager as BaseObjectManager;

/**
 * Specialized Object Manager
 *
 * @package Cundd\Rest
 */
class ObjectManager extends BaseObjectManager implements TYPO3ObjectManagerInterface, ObjectManagerInterface, SingletonInterface
{
    /**
     * @var \Cundd\Rest\DataProvider\DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var \Cundd\Rest\Authentication\AuthenticationProviderInterface
     */
    protected $authenticationProvider;

    /**
     * Configuration provider
     *
     * @var \Cundd\Rest\Configuration\TypoScriptConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * @var \Cundd\Rest\Access\AccessControllerInterface
     */
    protected $accessController;

    /**
     * Returns the configuration provider
     *
     * @return \Cundd\Rest\Configuration\TypoScriptConfigurationProvider
     */
    public function getConfigurationProvider()
    {
        if (!$this->configurationProvider) {
            $this->configurationProvider = $this->get('Cundd\\Rest\\Configuration\\TypoScriptConfigurationProvider');
        }
        return $this->configurationProvider;
    }

    /**
     * Returns the Request Factory
     *
     * @return RequestFactoryInterface
     */
    public function getRequestFactory()
    {
        return $this->get('Cundd\\Rest\\RequestFactoryInterface');
    }

    /**
     * Returns the Response Factory
     *
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory()
    {
        return $this->get('Cundd\\Rest\\ResponseFactoryInterface');
    }

    /**
     * Returns the data provider
     *
     * @return \Cundd\Rest\DataProvider\DataProviderInterface
     */
    public function getDataProvider()
    {
        if (!$this->dataProvider) {
            list($vendor, $extension, $model) = PathUtility::getClassNamePartsForPath($this->getRequest()->path());

            $classes = array(
                // Check if an extension provides a Data Provider for the domain model
                'Tx_' . $extension . '_Rest_' . $model . 'DataProvider',
                ($vendor ? $vendor . '\\' : '') . $extension . '\\Rest\\' . $model . 'DataProvider',

                // Check if an extension provides a Data Provider
                'Tx_' . $extension . '_Rest_DataProvider',
                ($vendor ? $vendor . '\\' : '') . $extension . '\\Rest\\DataProvider',

                // Check for a specific builtin Data Provider
                'Cundd\\Rest\\DataProvider\\' . $extension . 'DataProvider',
            );

            $this->dataProvider = $this->get($this->getFirstExistingClass($classes,  'Cundd\\Rest\\DataProvider\\DataProviderInterface'));
        }
        return $this->dataProvider;
    }

    /**
     * Returns the Authentication Provider
     *
     * @return \Cundd\Rest\Authentication\AuthenticationProviderInterface
     */
    public function getAuthenticationProvider()
    {
        if (!$this->authenticationProvider) {
            list($vendor, $extension,) = PathUtility::getClassNamePartsForPath($this->getRequest()->path());

            // Check if an extension provides a Authentication Provider
            $authenticationProviderClass = 'Tx_' . $extension . '_Rest_AuthenticationProvider';
            if (!class_exists($authenticationProviderClass)) {
                $authenticationProviderClass = ($vendor ? $vendor . '\\' : '') . $extension . '\\Rest\\AuthenticationProvider';
            }

            // Use the found Authentication Provider
            if (class_exists($authenticationProviderClass)) {
                $this->authenticationProvider = $this->get($authenticationProviderClass);
            } else {
                // Use the default Authentication Provider
                $this->authenticationProvider = $this->get('Cundd\\Rest\\Authentication\\AuthenticationProviderCollection', array(
                    $this->get('Cundd\\Rest\\Authentication\\BasicAuthenticationProvider'),
                    $this->get('Cundd\\Rest\\Authentication\\CredentialsAuthenticationProvider'),
                ));
            }

            $this->authenticationProvider->setRequest($this->getRequest());
        }
        return $this->authenticationProvider;
    }

    /**
     * Returns the Access Controller
     *
     * @return \Cundd\Rest\Access\AccessControllerInterface
     */
    public function getAccessController()
    {
        if (!$this->accessController) {
            list($vendor, $extension,) = PathUtility::getClassNamePartsForPath($this->getRequest()->path());

            // Check if an extension provides a Authentication Provider
            $accessControllerClass = 'Tx_' . $extension . '_Rest_AccessController';
            if (!class_exists($accessControllerClass)) {
                $accessControllerClass = ($vendor ? $vendor . '\\' : '') . $extension . '\\Rest\\AccessController';
            }

            // Use the configuration based Authentication Provider
            if (!class_exists($accessControllerClass)) {
                $accessControllerClass = 'Cundd\\Rest\\Access\\ConfigurationBasedAccessController';
            }
            $this->accessController = $this->get($accessControllerClass);
            $this->accessController->setRequest($this->getRequest());
        }
        return $this->accessController;
    }

    /**
     * Returns the Handler which is responsible for handling the current request
     *
     * @return HandlerInterface
     */
    public function getHandler()
    {
        list($vendor, $extension,) = PathUtility::getClassNamePartsForPath($this->getRequest()->path());

        $classes = array(
            // Check if an extension provides a Data Provider
            'Tx_' . $extension . '_Rest_Handler',
            ($vendor ? $vendor . '\\' : '') . $extension . '\\Rest\\Handler',

            // Check for a specific builtin Data Provider
            'Cundd\\Rest\\Handler\\' . $extension . 'Handler',
        );

        return $this->get($this->getFirstExistingClass($classes, 'Cundd\\Rest\\HandlerInterface'));
    }

    /**
     * Returns the Cache instance
     *
     * @return \Cundd\Rest\Cache\Cache
     */
    public function getCache()
    {
        $cacheImplementation = $this->getConfigurationProvider()->getSetting('cacheClass');
        if ($cacheImplementation && $this->isRegistered($cacheImplementation)) {
            return $this->get($cacheImplementation);
        } else {
            return $this->get('Cundd\\Rest\\Cache\\Cache');
        }
    }

    /**
     * Returns the correct class name of the Persistence Manager for the current TYPO3 version
     *
     * @return string
     */
    public static function getPersistenceManagerClassName()
    {
        if (version_compare(TYPO3_version, '6.0.0') < 0) {
            return 'Tx_Extbase_Persistence_Manager';
        }
        return 'TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface';
    }

    /**
     * Returns the current request
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->getRequestFactory()->getRequest();
    }

    /**
     * Returns the first of the classes that exists
     *
     * @param string[] $classes
     * @param string $default
     * @return string
     * @throws \Exception
     */
    private function getFirstExistingClass(array $classes, $default = '')
    {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        if ($default === '') {
            throw new \Exception('No existing class found');
        }

        return $default;
    }

    /**
     * Resets the managed objects
     */
    public function reassignRequest()
    {
        $this->dataProvider = null;
        $this->authenticationProvider = null;
        $this->configurationProvider = null;
        $this->accessController = null;
    }
}
