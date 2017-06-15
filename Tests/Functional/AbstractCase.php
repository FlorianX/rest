<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 24.12.14
 * Time: 12:45
 */

namespace Cundd\Rest\Tests\Functional;

require_once __DIR__ . '/../Bootstrap.php';

class AbstractCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    public function setUp()
    {
        parent::setUp();
        $this->objectManager = new \TYPO3\CMS\Extbase\Object\ObjectManager();
    }

    /**
     * Build a new request with the given URI
     *
     * @param string $uri
     * @param string $format
     * @return \Cundd\Rest\Request
     */
    public function buildRequestWithUri($uri, $format = null)
    {
        $uri = filter_var($uri, FILTER_SANITIZE_URL);

        $path = strtok($uri, '/');

        $request = new \Cundd\Rest\Request(null, $uri);
        $request->initWithPathAndOriginalPath($path, $path);
        $request->injectConfigurationProvider(
            $this->objectManager->get('Cundd\\Rest\\ObjectManager')->getConfigurationProvider()
        );
        if ($format) {
            $request->format($format);
        }

        return $request;
    }

    /**
     * @param string $className
     * @param string $namespace
     * @param string $extends
     * @throws \Exception
     */
    protected function createClass($className, $namespace = '', $extends = '')
    {
        if (func_num_args() === 1 && is_array($className)) {
            list($className, $namespace, $extends) = $className;
        }
        if (!is_string($className)) {
            throw new \InvalidArgumentException('$className must be a string');
        }
        if (!is_string($namespace)) {
            throw new \InvalidArgumentException('$namespace must be a string');
        }
        if (!is_string($extends)) {
            throw new \InvalidArgumentException('$extends must be a string');
        }

        $namespace = trim($namespace, '\\');
        if (class_exists("$namespace\\$className")) {
            printf('Class %s already exists' . PHP_EOL, "$namespace\\$className");

            return;
        }

        $code = array();
        if ($namespace) {
            $code[] = "namespace $namespace;";
        }
        $code[] = "class $className";
        if ($extends) {
            $code[] = "extends $extends";
        }
        $code[] = '{}';

        eval(implode(' ', $code));

        if (!class_exists("$namespace\\$className")) {
            throw new \Exception(sprintf('Could not create class %s', "$namespace\\$className"));
        }
    }

    /**
     * Imports a data set represented as XML into the test database,
     *
     * @param string $path Absolute path to the XML file containing the data set to load
     * @return void
     * @throws \Exception
     */
    protected function importDataSet($path)
    {
        if (method_exists('\TYPO3\CMS\Core\Tests\FunctionalTestCase', 'importDataSet')) {
            parent::importDataSet($path);

            return;
        }

        if (!is_file($path)) {
            throw new \Exception(
                'Fixture file ' . $path . ' not found',
                1376746261
            );
        }

        $database = $this->getDatabaseConnection();

        $xml = simplexml_load_file($path);
        $foreignKeys = array();

        /** @var $table \SimpleXMLElement */
        foreach ($xml->children() as $table) {
            $insertArray = array();

            /** @var $column \SimpleXMLElement */
            foreach ($table->children() as $column) {
                $columnName = $column->getName();
                $columnValue = null;

                if (isset($column['ref'])) {
                    list($tableName, $elementId) = explode('#', $column['ref']);
                    $columnValue = $foreignKeys[$tableName][$elementId];
                } elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
                    $columnValue = null;
                } else {
                    $columnValue = (string)$table->$columnName;
                }

                $insertArray[$columnName] = $columnValue;
            }

            $tableName = $table->getName();
            $result = $database->exec_INSERTquery($tableName, $insertArray);
            if ($result === false) {
                $this->markTestSkipped(
                    sprintf(
                        'Error when processing fixture file: %s. Can not insert data to table %s: %s',
                        $path,
                        $tableName,
                        $database->sql_error()
                    )
                );
            }
            if (isset($table['id'])) {
                $elementId = (string)$table['id'];
                $foreignKeys[$tableName][$elementId] = $database->sql_insert_id();
            }
        }
    }

    /**
     * Get DatabaseConnection instance - $GLOBALS['TYPO3_DB']
     *
     * This method should be used instead of direct access to
     * $GLOBALS['TYPO3_DB'] for easy IDE auto completion.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param mixed  $propertyValue
     * @param string $propertyKey
     * @param object $object
     * @return object
     */
    public function injectPropertyIntoObject($propertyValue, $propertyKey, $object)
    {
        $reflectionMethod = new \ReflectionProperty(get_class($object), $propertyKey);
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->setValue($object, $propertyValue);

        return $object;
    }
}
