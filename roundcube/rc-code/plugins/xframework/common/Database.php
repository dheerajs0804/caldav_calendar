<?php
namespace XFramework;

/**
 * Roundcube Plus Framework plugin.
 *
 * This class provides shortcut functions to the Roundcube database access.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

defined("BOOL") || define("BOOL", "bool");
defined("INT") || define("INT", "int");

abstract class Database
{
    const BOOL = "bool";
    const INT = "int";
    protected $rcmail;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->rcmail = \rcmail::get_instance();
    }

    public function getProvider()
    {
        return $this->rcmail->db->db_provider;
    }

    /**
     * Convert bool or int values into actual bool or int values. (PDO returns int and bool as strings, which later
     * causes problems when the values are sent to javascript.)
     *
     * @param array $data
     * @return array
     */
    public function fix(array &$data, $type, array $names)
    {
        foreach ($names as $name) {
            if ($type == BOOL) {
                $data[$name] = (bool)$data[$name];
            } else if ($type == INT) {
                $data[$name] = (int)$data[$name];
            }
        }
    }

    /**
     * Returns the last insert id.
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->rcmail->db->insert_id();
    }

    /**
     * Begins a transaction.
     *
     * @return type
     */
    public function beginTransaction()
    {
        return $this->rcmail->db->startTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return type
     */
    public function commit()
    {
        return $this->rcmail->db->endTransaction();
    }

    /**
     * Rolls back a transaction.
     *
     * @return type
     */
    public function rollBack()
    {
        return $this->rcmail->db->rollbackTransaction();
    }

    public function fetch($query, $parameters = array())
    {
        if (!($statement = $this->query($query, $parameters))) {
            return false;
        }

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a single row from the database.
     *
     * @param type $table
     * @param array $whereParams
     * @return type
     */
    public function row($table, array $whereParams)
    {
        $this->createWhereParams($whereParams, $where, $param);
        return $this->fetch("SELECT * FROM {" .$table . "} WHERE $where LIMIT 1", $param);
    }

    public function count($table, array $whereParams)
    {
        return $this->value("COUNT(*)", $table, $whereParams);
    }

    /**
     * Retrieves a single value from the database.
     *
     * @param string $field
     * @param string $table
     * @param array $whereParams
     * @return string|null
     */
    public function value($field, $table, array $whereParams)
    {
        $this->createWhereParams($whereParams, $where, $param);
        $row = $this->fetch("SELECT $field FROM {" .$table . "} WHERE $where LIMIT 1", $param);

        return $row ? $row[$field] : null;
    }

    /**
     * Retrieve multiple rows from the database as associate array.
     *
     * @param string $query
     * @param string|array $parameters
     * @return boolean
     */
    public function all($query, $parameters = array(), $resultKeyField = false)
    {
        if (!($statement = $this->query($query, $parameters))) {
            return false;
        }

        $array = $statement->fetchAll(\PDO::FETCH_ASSOC);

        // if $resultKeyField specified, place the requested field as the resulting array key
        if (!empty($array) && $resultKeyField) {
            $result = array();
            foreach ($array as $item) {
                $result[$item[$resultKeyField]] = $item;
            }
            return $result;
        }

        return $array;
    }

    /**
     * Inserts a record into the database.
     *
     * @param string $table
     * @param array $data
     * @param string $getValuesFromPost
     */
    public function insert($table, array $data)
    {
        $data = $this->fixWriteData($data);
        $fields = array();
        $markers = array();
        $values = array();

        foreach ($data as $field => $value) {
            $fields[] = $field;
            $markers[] = "?";
            $values[] = $value;
        }

        $fields = implode(",", $fields);
        $markers = implode(",", $markers);

        return (bool)$this->query("INSERT INTO {" . $table . "} ($fields) VALUES ($markers)", $values);
    }

    /**
     * Updates records in a table.
     *
     * @param string $table
     * @param array $data
     * @param array $whereParams
     * @return type
     */
    public function update($table, array $data, array $whereParams)
    {
        $data = $this->fixWriteData($data);
        $fields = array();
        $param = array();
        $where = array();

        foreach ($data as $key => $val) {
            $fields[] = "$key=?";
            $param[] = $val;
        }

        $this->createWhereParams($whereParams, $where, $param);
        $fields = implode(",", $fields);

        return (bool)$this->query("UPDATE {" . $table . "} SET $fields WHERE $where", $param);
    }

    /**
     * Removes records from a table.
     *
     * @param string $table
     * @param string $whereParams
     * @return bool
     */
    public function remove($table, array $whereParams, $addPrefix = true)
    {
        if ($addPrefix) {
            $table = "{" . $table . "}";
        }

        $this->createWhereParams($whereParams, $where, $param);

        return (bool)$this->query("DELETE FROM $table WHERE $where", $param);
    }

    /**
     * Truncates a table.
     *
     * @param string $table
     * @return bool
     */
    public function truncate($table, $addPrefix = true)
    {
        if ($addPrefix) {
            $table = "{" . $table . "}";
        }

        // we don't use truncate because of foreign key problems
        return $this->query("DELETE FROM $table");
    }

    /**
     * Run a database query. Returns PDO statement.
     *
     * @param string $query
     * @param string|array $parameters
     */
    public function query($query, $parameters = array())
    {
        return $this->rcmail->db->query(
            $this->prepareQuery($query),
            is_array($parameters) ? $parameters : array($parameters)
        );
    }

    /**
     * Returns the table name prefixed with the db_prefix config setting.
     *
     * @param type $table
     * @return type
     */
    public function getTableName($table, $quote = true)
    {
        $table = $this->rcmail->config->get("db_prefix") . $table;
        return $quote ? $this->rcmail->db->quote_identifier($table) : $table;
    }

    /**
     * Replaces table names in queries enclosed in { } prefixing them with the db_prefix config setting.
     * @param type $query
     * @return type
     */
    public function prepareQuery($query)
    {
        return preg_replace_callback("/\{([^\}]+)\}/", array($this, "pregQueryReplace"), $query);
    }

    /**
     * Executes a query or a collection of queries. Executing a collection of queries using query() won't work in
     * sqlite, only the first query will execute. Use this function instead.
     *
     * @param type $script
     * @return type
     * @codeCoverageIgnore
     */
    public function script($script)
    {
        // There's no ALTER IF NOT EXIST so we check if there's an alter statement in the script, extract the
        // first column to be added and check if it already exists. If it does, we don't run the script.
        // The current db versions of the plugins are stored in system > xframework_db_versions, but we're doing this
        // in case that information is missing (like in the case of xsignature, which added columns on its own without
        // the use of xframework)
        if (preg_match("/ALTER\s+TABLE\s+(\w+)\s+ADD\s+(\w+)\s+/i", $script, $match) && count($match) > 2) {
            if ($this->hasColumn($match[2], $this->getTableName($match[1], false), false)) {
                return true;
            }
        }

        return $this->rcmail->db->exec_script($script);
    }

    public function hasColumn($column, $table, $addPrefix = false)
    {
        return in_array($column, $this->getColumns($table, $addPrefix));
    }

    /**
     * Fixes the data that is about to be written to database, for example, RC will try to write bool false as an
     * empty string, which might cause problems with some databases.
     *
     * @param type $data
     * @return type
     */
    private function fixWriteData(array $data)
    {
        foreach ($data as $key => $val) {
            if (is_bool($val)) {
                $data[$key] = (int)$val;
            }
        }

        return $data;
    }

    protected function pregQueryReplace($matches)
    {
        return " " . $this->getTableName($matches[1]) . " ";
    }

    protected function createWhereParams($whereParameters, &$where, &$param)
    {
        is_array($where) || $where = array();
        is_array($param) || $param = array();

        foreach ($whereParameters as $key => $val) {
            if ($val === null) {
                $where[] = "$key IS NULL";
            } else {
                $where[] = "$key=?";
                $param[] = $val;
            }
        }

        $where = implode(" AND ", $where);
    }
}