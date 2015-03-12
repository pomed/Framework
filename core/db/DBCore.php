<?php

require_once("core/db/DBObject.php");
require_once("core/db/DBSelector.php");

require_once("core/Tools.php");

class DBCoreException extends Exception {}

/**
 * Core database functionality.
 *
 * @category Asymptix PHP Framework
 * @author Dmytro Zarezenko <dmytro.zarezenko@gmail.com>
 * @copyright (c) 2009 - 2015, Dmytro Zarezenko
 * @license http://opensource.org/licenses/MIT
 */
class DBCore {
    /**
     * An array containing all the opened connections
     *
     * @var array $connections
     */
    protected $connections = array();

    /**
     * @var integer $index The incremented index of connections
     */
    protected $index = 0;

    /**
     * @var integer $currIndex The current connection index
     */
    protected $currIndex = 0;

    protected static $instance;

    protected $selector = null;

    /**
     * Returns an instance of this class
     *
     * @return DBCore
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function Instance() {
        return self::getInstance();
    }

    /**
     * Reset the internal static instance
     *
     * @return void
     */
    public static function resetInstance() {
        if (self::$instance) {
            self::$instance->reset();
            self::$instance = null;
        }
    }

    /**
     * Reset this instance of the manager
     *
     * @return void
     */
    public function reset() {
        foreach ($this->connections as $conn) {
            $conn->close();
        }
        $this->connections = array();
        $this->index = 0;
        $this->currIndex = 0;
    }

    /**
     * Seves a new connection to DBCore->connections.
     *
     * @param mysqli Object $connResource An object which represents the connection to a MySQL Server.
     * @param string $connName Name of the connection, if empty numeric key is used.
     *
     * @throws Exception If trying to save a connection with an existing name.
     */
    public static function connection($connResource = null, $connName = null) {
        if ($connResource == null) {
            return DBCore::getInstance()->getCurrentConnection();
        } else {
            DBCore::getInstance()->openConnection($connResource, $connName);
        }
    }

    /**
     * Seves a new connection to DBCore->connections.
     *
     * @param mysqli Object $connResource An object which represents the connection to a MySQL Server.
     * @param string $connName Name of the connection, if empty numeric key is used.
     *
     * @throws Exception If trying to save a connection with an existing name.
     */
    public function openConnection($connResource, $connName = null) {
        if ($connName !== null) {
            $connName = (string)$connName;
            if (isset($this->connections[$connName])) {
                throw new Exception("You trying to save a connection with an existing name");
            }
        } else {
            $connName = $this->index;
            $this->index++;
        }

        $this->connections[$connName] = $connResource;
    }

    /**
     * Get the connection instance for the passed name.
     *
     * @param string $connName Name of the connection, if empty numeric key is used.
     *
     * @return mysqli Object
     *
     * @throws Exception If trying to get a non-existent connection.
     */
    public function getConnection($connName) {
        if (!isset($this->connections[$connName])) {
            throw new Exception('Unknown connection: ' . $connName);
        }

        return $this->connections[$connName];
    }

    /**
     * Get the name of the passed connection instance.
     *
     * @param mysqli Object $connResource Connection object to be searched for.
     *
     * @return string The name of the connection.
     */
    public function getConnectionName($connResource) {
        return array_search($connResource, $this->connections, true);
    }

    /**
     * Closes the specified connection.
     *
     * @param mixed $connection Connection object or its name.
     */
    public function closeConnection($connection) {
        $key = false;
        if (is_object($connection)) { // TODO: change to isObject method
            $connection->close();
            $key = $this->getConnectionName($connection);
        } elseif (is_string($connection)) {
            $key = $connection;
        }

        if ($key !== false) {
            unset($this->connections[$key]);

            if ($key === $this->currIndex) {
                $key = key($this->connections);
                $this->currIndex = ($key !== null) ? $key : 0;
            }
        }

        unset($connection);
    }

    /**
     * Returns all opened connections
     *
     * @return array
     */
    public function getConnections() {
        return $this->connections;
    }

    /**
     * Sets the current connection to $key
     *
     * @param mixed $key The connection key
     *
     * @throws Exception
     */
    public function setCurrentConnection($key) {
        if (!$this->contains($key)) {
            throw new Exception("Connection key '$key' does not exist.");
        }
        $this->currIndex = $key;
    }

    /**
     * Whether or not the DBCore contains specified connection.
     *
     * @param mixed $key The connection key
     *
     * @return boolean
     */
    public function contains($key) {
        return isset($this->connections[$key]);
    }

    /**
     * Returns the number of opened connections.
     *
     * @return integer
     */
    public function count() {
        return count($this->connections);
    }

    /**
     * Returns an ArrayIterator that iterates through all connections
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->connections);
    }

    /**
     * Get the current connection instance.
     *
     * @throws Exception If there are no open connections
     *
     * @return mysqli Object
     */
    public function getCurrentConnection() {
        $key = $this->currIndex;
        if (!isset($this->connections[$key])) {
            throw new Exception('There is no open connection');
        }
        return $this->connections[$key];
    }

    public static function Selector($className = null) {
        if (!empty($className)) {
            self::getInstance()->selector = new DBSelector($className);
        }
        return self::getInstance()->selector;
    }

    /**
     * Return qwestion marks string for IN(...) SQL construction.
     *
     * @param integer $length Length of the result string.
     *
     * @return string
     */
    public static function sqlQMString($length) {
        if ($length == 1) {
            return "?";
        }
        return implode(",", array_fill(0, $length, "?"));
    }

    /**
     * Return fields and qwestion marks string for SET field1=?, ... SQL construction.
     *
     * @param array<mixed> $fieldsList List of the table fields (syntax: array[fieldName] = fieldValue)
     * @param string $idFieldName Name of the primary key field.
     *
     * @return string
     */
    public static function createSQLQMValuesString($fieldsList, $idFieldName = "") {
        $sqlString = "";
        foreach ($fieldsList as $fieldName => $fieldValue) {
            if ($fieldName != $idFieldName) {
                $sqlString.= ", `" . $fieldName . "` = ?";
            }
        }
        return substr($sqlString, 2);
    }

    /**
     * Return fields and values string for SET field1=value1, ... SQL construction.
     *
     * @param array<mixed> $fieldsList List of the table fields (syntax: array[fieldName] = fieldValue)
     * @param string $idFieldName Name of the primary key field.
     *
     * @return string
     */
    public static function createSQLValuesString($fieldsList, $idFieldName) {
        $sqlString = "";
        foreach ($fieldsList as $fieldName => $fieldValue) {
            if ($fieldName != $idFieldName) {
                $sqlString.= ", `" . $fieldName . "` = '" . $fieldValue . "'";
            }
        }
        return substr($sqlString, 2);
    }

    /**
     * Returns SQL types string.
     * Type specification chars:
     *    i - corresponding variable has type integer
     *    d - corresponding variable has type double
     *    s - corresponding variable has type string
     *    b - corresponding variable is a blob and will be sent in packets
     *
     * @param array<mixed> $fieldsList List of the table fields (syntax: array[fieldName] = fieldValue)
     * @param string $idFieldName Name of the primary key field.
     * @return string
     */
    public static function createSQLTypesString($fieldsList, $idFieldName = "") {
        $typesString = "";
        foreach ($fieldsList as $fieldName => $fieldValue) {
            if ($fieldName != $idFieldName) {
                if (Tools::isDouble($fieldValue)) {
                    $typesString.= "d";
                } elseif (Tools::isInteger($fieldValue)) {
                    $typesString.= "i";
                } else {
                    $typesString.= "s";
                }
            }
        }
        return $typesString;
    }

    /**
     * Returns SQL types string of single type.
     *
     * @param string $type Type name.
     * @param integer $length Length of the SQL types string.
     * @return string
     */
    public static function sqlSingleTypeString($type, $length) {
        $typesList = array(
            'integer' => "i",
            'int'     => "i",
            'i'       => "i",
            'real'    => "d",
            'float'   => "d",
            'double'  => "d",
            'd'       => "d",
            'string'  => "s",
            'str'     => "s",
            's'       => "s",
            'boolean' => "b",
            'bool'    => "b",
            'b'       => "b"
        );
        $type = $typesList[$type];
        $typesString = "";
        while ($length > 0) {
            $typesString .= $type;
            $length --;
        }

        return $typesString;
    }

    private static function getFieldType($fieldValue) {
        if (Tools::isInteger($fieldValue)) {
            return "i";
        } elseif (isDouble($fieldValue)) {
            return "d";
        } elseif (isBoolean($fieldValue)) {
            return "b";
        } elseif (isString($fieldValue)) {
            return "s";
        } else {
            throw new Exception("Invalid field value type");
        }
    }

    /**
     * Returns list of ids by mixed values.
     *
     * @param mixed $ids Single variable, array<mixed> or string, separated by comma of ids.
     *
     * @return array<integer>
     */
    private static function getIdsList($ids) {
        if (is_string($ids)) {
            $ids = trim($ids);
            if (empty($ids)) {
                $idsList = array();
            } else {
                $idsList = explode(",", $ids);
            }
        } elseif (is_numeric($ids)) {
            $idsList = array($ids);
        } elseif (is_array($ids)) {
            $idsList = $ids;
        }
        foreach ($idsList as &$id) {
            settype($id, "integer");
        }

        return $idsList;
    }

    /**
     * Check database errors.
     *
     * @param object $dbObj
     */
    private static function checkDbError($dbObj) {
        if ($dbObj->error != "") {
            throw new DBCoreException($dbObj->error);
        }
    }

    /**
     * Bind parameters to the statment with dynamic number of parameters.
     *
     * @param resource $stmt Statement.
     * @param string $types Types string.
     * @param array $params Parameters.
     */
    private static function bindParameters($stmt, $types, $params) {
        $args   = array();
        $args[] = $types;

        foreach ($params as &$param) {
            $args[] = &$param;
        }
        call_user_func_array(array($stmt, 'bind_param'), $args);
    }

    /**
     * Return parameters from the statment with dynamic number of parameters.
     *
     * @param resource $stmt Statement.
     * @param array $params Parameters.
     */
    public static function bindResults($stmt) {
        $resultSet = array();
        $metaData = $stmt->result_metadata();
        $fieldsCounter = 0;
        while ($field = $metaData->fetch_field()) {
            if (!isset($resultSet[$field->table])) {
                $resultSet[$field->table] = array();
            }
            $resultSet[$field->table][$field->name] = $fieldsCounter++;
            $parameterName = "variable" . $fieldsCounter; //$field->name;
            $$parameterName = null;
            $parameters[] = &$$parameterName;
        }
        call_user_func_array(array($stmt, 'bind_result'), $parameters);
        if ($stmt->fetch()) {
            foreach ($resultSet as &$tableResult) {
                foreach ($tableResult as $fieldName => &$fieldValue) {
                    $fieldValue = $parameters[$fieldValue];
                }
            }
            return $resultSet;
        }
        self::checkDbError($stmt);
        return null;
    }

    /**
     * Execute update queries on database.
     *
     * @param string $query Query.
     * @param string $types Types string.
     * @param array $params Parameters.
     * @return integer Returns the number of affected rows on success, and -1 if the last query failed.
     */
    public static function doUpdateQuery($query, $types = "", $params = array()) {
        $stmt = self::connection()->prepare($query);
        self::checkDbError(self::connection());

        if ($params != null && count($params)!=0 && strlen($types)==count($params)) {
            self::bindParameters($stmt, $types, $params);
        } else {
            //TODO: error
        }

        $stmt->execute();
        self::checkDbError($stmt);

        $affectedRows = self::connection()->affected_rows;
        $stmt->close();

        return $affectedRows;
    }

    /**
     * Execute select queries on database.
     *
     * @param string $query SQL query.
     * @param string $types Types string (ex: "isdb").
     * @param array $params Parameters in the same order like types string.
     *
     * @return mixed Statement object or FALSE if an error occurred.
     */
    public static function doSelectQuery($query, $types = "", $params = array()) {
        $stmt = self::connection()->prepare($query);
        self::checkDbError(self::connection());

        if ($params != null && count($params) != 0) {
            if (strlen($types) == count($params)) {
                self::bindParameters($stmt, $types, $params);
            } else {
                throw new DBCoreException("Number of types is not equal parameters number");
            }
        }

        $stmt->execute();
        self::checkDbError($stmt);

        $stmt->store_result();
        self::checkDbError($stmt);
        return $stmt;
    }

    /**
     * Gets sinle row result of SQL query.
     *
     * @param object $stmt Statment of the query.
     * @param mixed $value1 Value from database.
     * @return true if all right, else false.
     */
    private static function getSingleRowResult($stmt, &$value1) {
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($value1);
            $stmt->fetch();
            $stmt->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns list of database table fields.
     *
     * @param string $tableName Name of the table.
     * @return array<string> List of the database table fields (syntax: array[fieldName] = fieldType)
     */
    public static function getTableFieldsList($tableName) {
        if (!empty($tableName)) {
            $query = "DESCRIBE " . $tableName;
            $stmt = self::doSelectQuery($query);
            $stmt->bind_result($field, $type, $null, $key, $default, $extra);
            $fieldsList = array();
            while ($stmt->fetch()) {
                $fieldsList[$field] = array(
                    'type' => $type,
                    'default' => $default
                );
            }
            $stmt->close();

            return $fieldsList;
        }
        return array();
    }

    public static function displayTableFieldsList($tableName) {
        print("<pre>");
        if (!empty($tableName)) {
            $fieldsList = self::getTableFieldsList($tableName);
            if (!empty($fieldsList)) foreach ($fieldsList as $fieldName => $data) {
                print("'" . $fieldName . "' => ");
                if (strpos($data['type'], "varchar") === 0
                 || strpos($data['type'], "text") === 0
                 || strpos($data['type'], "enum") === 0
                 || strpos($data['type'], "char") === 0
                 || strpos($data['type'], "datetime") === 0
                 || strpos($data['type'], "timestamp") === 0
                 || strpos($data['type'], "date") === 0) {
                    print("\"" . $data['default'] . "\"");
                } elseif (strpos($data['type'], "int") === 0
                 || strpos($data['type'], "tinyint") === 0
                 || strpos($data['type'], "smallint") === 0) {
                    if (!empty($data['default'])) {
                        print($data['default']);
                    } else {
                        print(0);
                    }
                }
                print(", // " . $data['type'] . ", default '" . $data['default'] . "'\n");
            }
        }
        print("</pre>");
    }

    /**
     * Returns list of fields values with default indexes.
     *
     * @param array<mixed> $fieldsList List of the table fields (syntax: array[fieldName] = fieldValue)
     * @param string $idFieldName Name of the primary key field.
     * @return array<mixed>
     */
    private static function createValuesList($fieldsList, $idFieldName = "") {
        $valuesList = array();
        foreach ($fieldsList as $fieldName => $fieldValue) {
            if ($fieldName != $idFieldName) {
                $valuesList[] = $fieldValue;
            }
        }
        return $valuesList;
    }

    public static function insertDBObject($dbObject) {
        $fieldsList = $dbObject->getFieldsList();
        $idFieldName = $dbObject->getIdFieldName();

            if (Tools::isInteger($fieldsList[$idFieldName])) {
            $query = "INSERT INTO " . $dbObject->getTableName() . "
                          SET " . self::createSQLQMValuesString($fieldsList, $idFieldName);
            $typesString = self::createSQLTypesString($fieldsList, $idFieldName);
            $valuesList = self::createValuesList($fieldsList, $idFieldName);
        } else {
            $query = "INSERT INTO " . $dbObject->getTableName() . "
                          SET " . self::createSQLQMValuesString($fieldsList);
            $typesString = self::createSQLTypesString($fieldsList);
            $valuesList = self::createValuesList($fieldsList);
        }
        self::doUpdateQuery($query, $typesString, $valuesList);

        return (self::connection()->insert_id);
    }

    public static function updateDBObject($dbObject) {
        $fieldsList = $dbObject->getFieldsList();
        $idFieldName = $dbObject->getIdFieldName();

        $query = "UPDATE " . $dbObject->getTableName() . "
                  SET " . self::createSQLQMValuesString($fieldsList, $idFieldName) . "
                  WHERE " . $idFieldName . " = ?";
        $typesString = self::createSQLTypesString($fieldsList, $idFieldName);
        if (Tools::isInteger($fieldsList[$idFieldName])) {
            $typesString.= "i";
        } else {
            $typesString.= "s";
        }
        $valuesList = self::createValuesList($fieldsList, $idFieldName);
        $valuesList[] = $dbObject->getId();

        return self::doUpdateQuery($query, $typesString, $valuesList);
    }

    public static function deleteDBObject($dbObject) {
        if (!empty($dbObject) && is_object($dbObject)) {
            $query = "DELETE FROM " . $dbObject->getTableName() . " WHERE " . $dbObject->getIdFieldName() . " = ?";
            if (Tools::isInteger($dbObject->getId())) {
                $typesString = "i";
            } else {
                $typesString = "s";
            }
            self::doUpdateQuery($query, $typesString, array($dbObject->getId()));

            return (self::connection()->affected_rows);
        } else {
            return false;
        }
    }

    public static function selectDBObjectFromResultSet($dbObject, $resultSet) {
        $dbObject->setFieldsValues($resultSet[$dbObject->getTableName()]);
        return $dbObject;
    }

    /**
     * Returns DB object by database query statement.
     *
     * @param resource $stmt Database query statement.
     * @param string $className Name of the DB object class.
     * @return DBObject
     */
    public static function selectDBObjectFromStatement($stmt, $className) {
        if (is_object($className)) {
            $className = get_class($className);
        }

        if ($stmt->num_rows == 1) {
            $resultSet = self::bindResults($stmt);
            $dbObject = new $className();
            self::selectDBObjectFromResultSet($dbObject, $resultSet);

            //self::echoObject($dbObject);

            if (!is_null($dbObject) && is_object($dbObject) && $dbObject->getId()) {
                return $dbObject;
            } else {
                return null;
            }
        } elseif ($stmt->num_rows > 1) {
            throw new DBCoreException("More than single record of '" . $className . "' entity selected");
        }

        return null;
    }

    /**
     * Selects DBObject from database.
     *
     * @param string $query SQL query.
     * @param string $types Types string (ex: "isdb").
     * @param array $params Parameters in the same order like types string.
     * @param mixed $instance Instance of the some DBObject class or it's class name.
     *
     * @return DBObject Selected DBObject or NULL otherwise.
     */
    public static function selectDBObject($query, $types, $params, $instance) {
        $stmt = DBCore::doSelectQuery($query, $types, $params);
        $obj = null;
        if ($stmt) {
            $obj = DBCore::selectDBObjectFromStatement($stmt, $instance);

            $stmt->close();
        }

        return $obj;
    }

    /**
     * Returns list of DB objects by database query statement.
     *
     * @param resource $stmt Database query statement.
     * @param mixed $className Instance of the some DBObject class or it's class name.
     *
     * @return array<DBObject>
     */
    public static function selectDBObjectsFromStatement($stmt, $className) {
        if (is_object($className)) {
            $className = get_class($className);
        }

        if ($stmt->num_rows > 0) {
            $objectsList = array();
            while ($resultSet = self::bindResults($stmt)) {
                $dbObject = new $className();
                self::selectDBObjectFromResultSet($dbObject, $resultSet);
                //$objectsList[] = $dbObject;
                $objectsList[$dbObject->getId()] = $dbObject;
            }
            return $objectsList;
        } else {
            return array();
        }
    }

    /**
     * Selects DBObject list from database.
     *
     * @param string $query SQL query.
     * @param string $types Types string (ex: "isdb").
     * @param array $params Parameters in the same order like types string.
     * @param mixed $instance Instance of the some DBObject class or it's class name.
     *
     * @return DBObject Selected DBObject or NULL otherwise.
     */
    public static function selectDBObjects($query, $types, $params, $instance) {
        $stmt = DBCore::doSelectQuery($query, $types, $params);
        $obj = null;
        if ($stmt) {
            $obj = DBCore::selectDBObjectsFromStatement($stmt, $instance);

            $stmt->close();
        }

        return $obj;
    }

    public static function selectSingleValue($query) {
        $stmt = self::doSelectQuery($query);

        $stmt->bind_result($value);
        $stmt->fetch();
        $stmt->close();

        return $value;
    }

    public function __call($methodName, $methodParams) {
        if (strrpos($methodName, "ies") == strlen($methodName) - 3) {
            $methodName = substr($methodName, 0, strlen($methodName) - 3) . "ys";
        }

        /**
         * Get database record object by Id
         */
        if (preg_match("#get([a-zA-Z]+)ById#", $methodName, $matches)) {
            $dbSelector = new DBSelector($matches[1]);

            return $dbSelector->selectDBObjectById($methodParams[0]);
        }

        /**
         * Get database record object by some field value
         */
        if (preg_match("#get([a-zA-Z]+)By([a-zA-Z]+)#", $methodName, $matches)) {
            if (empty($methodParams[0])) {
                return null;
            }
            $dbSelector = new DBSelector($matches[1]);

            $fieldName = substr(strtolower(preg_replace("#([A-Z]{1})#", "_$1", $matches[2])), 1);

            return $dbSelector->selectDBObjectByField($fieldName, $methodParams[0]);
        }

        /**
         * Get all database records
         */
        if (preg_match("#get([a-zA-Z]+)s#", $methodName, $matches)) {
            return self::Selector()->selectDBObjects();
        }

        /**
         * Delete selected records from the database
         */
        if (preg_match("#delete([a-zA-Z]+)s#", $methodName, $matches)) {
            $className = $matches[1];
            $idsList = $methodParams[0];

            $idsList = array_filter($idsList, "isInteger");
            if (!empty($idsList)) {
                $itemsNumber = count($idsList);
                $types = self::sqlSingleTypeString("i", $itemsNumber);
                $dbObject = new $className();

                if (!isInstanceOf($dbObject, $className)) {
                    throw new Exception("Class with name '" . $className . "' is not exists");
                }

                $query = "DELETE FROM " . $dbObject->getTableName() . "
                          WHERE " . $dbObject->getIdFieldName() . "
                             IN (" . self::sqlQMString($itemsNumber) . ")";

                return self::doUpdateQuery($query, $types, $idsList);
            }
            return 0;
        }

        /**
         * Delete selected record from the database
         */
        if (preg_match("#delete([a-zA-Z]+)#", $methodName, $matches)) {
            return call_user_func(array(self::Instance(), $methodName . "s"), array($methodParams[0]));
        }

        /**
         * Set activation value of selected records
         */
        if (preg_match("#set([a-zA-Z]+)Activation#", $methodName, $matches)) {
            $className = $matches[1];
            if (strrpos($className, "ies") == strlen($className) - 3) {
                $className = substr($className, 0, strlen($className) - 3) . "y";
            } else {
                $className = substr($className, 0, strlen($className) - 1);
            }

            $idsList = $methodParams[0];
            $activationFieldName = $methodParams[1];
            $activationValue = $methodParams[2];

            if (empty($activationFieldName)) {
                throw new Exception("Invalid activation field name");
            }

            $idsList = array_filter($idsList, "isInteger");
            if (!empty($idsList)) {
                $itemsNumber = count($idsList);
                $types = self::sqlSingleTypeString("i", $itemsNumber);
                $dbObject = new $className();

                if (!isInstanceOf($dbObject, $className)) {
                    throw new Exception("Class with name '" . $className . "' is not exists");
                }

                $query = "UPDATE " . $dbObject->getTableName() . " SET `" . $activationFieldName . "` = '" . $activationValue ."'
                          WHERE " . $dbObject->getIdFieldName() . " IN (" . self::sqlQMString($itemsNumber) . ")";

                return self::doUpdateQuery($query, $types, $idsList);
            }
        }

        throw new DBCoreException('No such method "' . $methodName . '"');
    }

}

?>