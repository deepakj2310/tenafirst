<?php

namespace Includes\baseClasses;

abstract class KCAppModel
{


    public $tableName;

    function __construct($table)
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'kc_' . $table;
    }

    /**
     * It will insert a row into a table if the row does not already exist
     * 
     * @param array data The data to be inserted into the table.
     * @param array conditionValue This is an array of the fields and values you want to check for.
     * @param condition This is the condition to use in the query. It can be =, !=, >, <, >=, <=, IN,
     * NOT IN, etc.
     */
    public function insertOnCondition(array $data, array $conditionValue, $condition = '=')
    {

        global $wpdb;

        if (empty($data))
            return false;


        try {
            $table_col = implode(',', array_keys($data));
            $col_val = implode(',', array_map(function ($val) {
                if (is_numeric($val))
                    return $val;
                return '"' . $val . '"';
            }, array_values($data)));

            $sql = "INSERT INTO {$this->tableName} ({$table_col})
            SELECT {$col_val}
            WHERE NOT EXISTS (
                SELECT * FROM {$this->tableName}
                WHERE";

            $conditionCounter = 1;
            foreach ($conditionValue as $field => $value) {
                if ($conditionCounter > 1) {
                    $sql .= ' AND ';
                }

                switch (strtolower($condition)) {
                    case 'in':
                        if (!is_array($value) || empty($value))
                            throw new Exception(__("Values for IN query must be an array.", 'kivicare-api'), 1);
                        $sql .= $wpdb->prepare('`%s` IN (%s)', $field, implode(',', $value));
                        break;
                    default:
                        $sql .= $wpdb->prepare('`' . $field . '` ' . $condition . ' %s', $value);
                        break;
                }

                $conditionCounter++;
            }
            $sql .= ");";

            // As this will always return an array of results if you only want to return one record make $returnSingleRow TRUE

            return  $wpdb->query($sql);
        } catch (Exception $ex) {
            return false;
        }
    }
    /**
     * Update a table record in the database
     *
     * @param array $data - Array of data to be updated
     * @param array $conditionValue - Key value pair for the where clause of the query
     *
     * @return bool|false|int object
     */
    public function update(array $data, array $conditionValue)
    {
        global $wpdb;

        if (empty($data)) {
            return false;
        }

        return $wpdb->update($this->tableName, $data, $conditionValue);
    }


    /**
     * Delete row on the database table
     *
     * @param  array  $conditionValue - Key value pair for the where clause of the query
     *
     * @return Int - Num rows deleted
     */
    public function delete(array $conditionValue)
    {
        global $wpdb;

        return $wpdb->delete($this->tableName, $conditionValue);
    }


    /**
     * Get all from the selected table
     *
     * @param  String $orderBy - Order by column name
     *
     * @return array|object
     */

    public function get_all($orderBy = NULL)
    {
        global $wpdb;

        $sql = 'SELECT * FROM `' . $this->tableName . '`';

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . esc_sql($orderBy);
        }

        $all = $wpdb->get_results($sql);

        return $all;
    }

    /**
     * Get a value by a condition
     *
     * @param array $conditionValue - A key value pair of the conditions you want to search on
     * @param String $condition - A string value for the condition of the query default to equals
     *
     * @param bool $returnSingleRow
     *
     * @return bool|result
     */
    public function get_by(array $conditionValue, $condition = '=', $returnSingleRow = FALSE, $args = array('orderBy' => false, 'limit' => 12, 'offset' => 0))
    {
        global $wpdb;
        extract($args);

        try {
            $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE ';

            $conditionCounter = 1;
            foreach ($conditionValue as $field => $value) {
                if ($conditionCounter > 1) {
                    $sql .= ' AND ';
                }

                switch (strtolower($condition)) {
                    case 'in':
                        if (!is_array($value) || empty($value)) {
                            throw new Exception(__("Values for IN query must be an array.", 'kc-lang'), 1);
                        }

                        $sql .= $wpdb->prepare('`%s` IN (%s)', $field, implode(',', $value));
                        break;

                    default:
                        $sql .= $wpdb->prepare('`' . $field . '` ' . $condition . ' %s', $value);
                        break;
                }

                $conditionCounter++;
            }

            if (is_array($orderBy))
                $sql  .= $wpdb->prepare(" ORDER BY ".$orderBy['by']." ". $orderBy['order'] );

            if ($limit > 0)
                $sql  .= $wpdb->prepare(" LIMIT %d", $limit);

            if ($offset > 0)
                $sql  .= $wpdb->prepare( "OFFSET %d", $limit);

            // As this will always return an array of results if you only want to return one record make $returnSingleRow TRUE

            if ($returnSingleRow) {
                $result = $wpdb->get_row($sql);
            } else {

                $result = $wpdb->get_results($sql);
            }

            return $result;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function get_var(array $conditionValue, $column)
    {
        global $wpdb;
        $condition = '=';
        try {
            $sql = "SELECT {$column} FROM {$this->tableName} WHERE ";

            $conditionCounter = 1;

            foreach ($conditionValue as $field => $value) {
                if ($conditionCounter > 1) {
                    $sql .= ' AND ';
                }

                $sql .= $wpdb->prepare('`' . $field . '` ' . $condition . ' %s', $value);

                $conditionCounter++;
            }

            return $wpdb->get_var($sql);
        } catch (Exception $ex) {
            return false;
        }
    }
}
