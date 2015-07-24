<?php

require_once(realpath(dirname(__FILE__)) . "/../Tools.php");
require_once(realpath(dirname(__FILE__)) . "/../OutputStream.php");

/**
 * DB SQL query object.
 *
 * @category Asymptix PHP Framework
 * @author Dmytro Zarezenko <dmytro.zarezenko@gmail.com>
 * @copyright (c) 2015, Dmytro Zarezenko
 *
 * @git https://github.com/dzarezenko/Asymptix-PHP-Framework.git
 * @license http://opensource.org/licenses/MIT
 */
class DBQuery {

    const TYPE_SELECT = 'SELECT';
    const TYPE_INSERT = 'INSERT';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_DELETE = 'DELETE';

    /* Service variables */

    /**
     * Type of the SQL query.
     *
     * @var string
     */
    protected $type = self::TYPE_SELECT;

    /**
     * SQL conditions list.
     *
     * @var array
     */
    public $conditions = array();

    /**
     * SQL fields list for INSERT/UPDATE queries.
     *
     * @var array
     */
    public $fields = array();

    /**
     * SQL order list.
     *
     * @var array
     */
    public $order = null;

    /**
     * SQL limit value (may be pair array or integer value).
     *
     * @var mixed
     */
    public $limit = 1;

    /**
     * Creates new DBQuery object with SQL type initialized.
     *
     * @param string $type SQL query type.
     */
    public function __construct($type = self::TYPE_SELECT) {
        $this->setType($type);
    }

    /**
     * Validates SQL query type value.
     *
     * @param string $type SQL query type to validate.
     * @return boolean
     */
    public static function isValidQueryType($type) {
        return in_array($type, self::getQueryTypes());
    }

    /**
     * Sets SQL query type with additional query type validation.
     *
     * @param string $type SQL query type.
     * @throws DBCoreException If invalid query type provided.
     */
    public function setType($type = self::TYPE_SELECT) {
        if (self::isValidQueryType($type)) {
            $this->type = $type;
        } else {
            throw new DBCoreException("Invalid SQL query type '" . $type . "'");
        }
    }

    /**
     * Returns SQL query type.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Detects type of the SQL query.
     *
     * @param string $query SQL query or query template.
     *
     * @return string Type of the SQL query.
     * @throws DBCoreException If SQL query is invalid.
     */
    protected static function detectQueryType($query) {
        $chunks = explode(" ", trim($query));
        if (!isset($chunks[0])) {
            throw new DBCoreException("Invalid SQL query format (can't detect query type)");
        } else {
            $type = strtoupper($chunks[0]);

            if (!self::isValidQueryType($type)) {
                throw new DBCoreException("Invalid SQL query type '" . $type . "'");
            }

            return $type;
        }
    }

    /**
     * Detects type of the SQL query.
     *
     * @return string Type of the SQL query.
     * @throws DBCoreException If SQL query is invalid.
     */
    protected function detectType() {
        return self::detectQueryType($this->query);
    }

    /**
     * Returns DBQuery types array from DBQuery types constants list.
     *
     * @return array DBQuery types array.
     */
    public static function getQueryTypes() {
        $oClass = new ReflectionClass('DBQuery');
        $constantsList = $oClass->getConstants();

        return array_map(
            array('self', 'getQueryTypeTitle'),
            array_filter(
                array_keys($constantsList),
                array('self', 'filterQueryTypes')
            )
        );
    }

    /**
     * DBQuery type constants filter.
     *
     * @param string $type Name of the constant.
     * @return boolean
     */
    private static function filterQueryTypes($type) {
        return (substr($type, 0, 5) == "TYPE_");
    }

    /**
     * Gets query type title from DBQuery type constant name.
     *
     * @param string $constName Name of the constant.
     * @return string
     */
    private static function getQueryTypeTitle($constName) {
        return substr($constName, 5);
    }

}

?>