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

namespace Cundd\Rest\Configuration;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

/**
 * Class TypoScriptConfigurationProvider
 *
 * @package Cundd\Rest\Configuration
 */
class TypoScriptConfigurationProvider implements SingletonInterface, ConfigurationProviderInterface
{
    /**
     * Settings read from the TypoScript
     *
     * @var array
     */
    protected $settings = null;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

    /**
     * Returns the setting with the given key
     *
     * @param string $keyPath
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getSetting($keyPath, $defaultValue = null)
    {
        $matchingSetting = $this->getSettings();

        $keyPathParts = explode('.', $keyPath);
        foreach ($keyPathParts as $key) {
            if (is_array($matchingSetting)) {
                if (isset($matchingSetting[$key . '.'])) {
                    $matchingSetting = $matchingSetting[$key . '.'];
                } elseif (isset($matchingSetting[$key])) {
                    $matchingSetting = $matchingSetting[$key];
                } else {
                    $matchingSetting = null;
                }
            } else {
                $matchingSetting = null;
            }
        }
        if (is_null($matchingSetting) && !is_null($defaultValue)) {
            return $defaultValue;
        }
        return $matchingSetting;
    }

    /**
     * Returns the settings read from the TypoScript
     *
     * @return array
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = array();

            $typoScript = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            if (isset($typoScript['plugin.'])
                && isset($typoScript['plugin.']['tx_rest.'])
                && isset($typoScript['plugin.']['tx_rest.']['settings.'])
            ) {
                $this->settings = $typoScript['plugin.']['tx_rest.']['settings.'];
            }
        }
        return $this->settings;
    }

    /**
     * Overwrites the settings
     *
     * @param array $settings
     * @internal
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }
}
