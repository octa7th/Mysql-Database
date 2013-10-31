<?php

/**
 * Mysql Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Muhammad Sofyan <sofyan@octa7th.com>
 * @copyright Copyright (c) 2013
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version   1.0.5
 */

class Database
{
    /**
     * Mysqli object
     * @var object
     */
    private $_mysql;

    /**
     * Last executed query
     * @var string
     */
    public $last_query;

    /**
     * WHERE condition storage
     * @var array
     */
    private $_where;

    /**
     * WHERE IN condition storage
     * @var array
     */
    private $_where_in;

    /**
     * SELECT fields storage
     * @var array
     */
    private $_select;

    /**
     * JOIN table and reference storage
     * @var array
     */
    private $_join;

    /**
     * ORDER result by table column and direction storage
     * @var array
     */
    private $_order;

    /**
     * number of result LIMIT to fetch storage
     * @var string
     */
    private $_limit;

    /**
     * Store parameter type if we using mysql prepare statement
     * @var string
     */
    private $_param_type;

    /**
     * Store parameter value if we using mysql prepare statement
     * @var array
     */
    private $_param_value;

    /**
     * Predefined and user setting storage
     * @var array
     */
    private $_setting;

    /**
     * Contains array number and text of current status
     * @var array
     * 0 = OK / Everything works fine
     * 1 = Database connect error
     * 2 = Parameter construct is incorrect
     * 3 = Unknown error / Query Error
     */
    private $_status;

    /**
     * Create new instance of mysql class
     * @param string  $host
     * @param string  $username
     * @param string  $password
     * @param string  $db
     * @param integer $port
     */
    function __construct($host = NULL, $username = NULL, $password = NULL, $db = NULL, $port = 3306)
    {
        if(is_null($host))
        {
            $this->status(2);
        }
        else
        {
            if( ! is_object($this->_mysql) )
            {
                $this->_mysql = new mysqli($host, $username, $password, $db, $port);
            }

            if($this->_mysql->errno !== 0)
            {
                $this->status(1);
            }
            else
            {
                $this->_init();
                $this->status(0);
            }
        }
    }

    /**
     * Add default value for query storage properties,
     * Initialize default value for object setting
     */
    private function _init()
    {
        $this->_where       = array();
        $this->_where_in    = array();
        $this->_select      = array();
        $this->_join        = array();
        $this->_order       = array();
        $this->_limit       = '';
        $this->_param_type  = '';
        $this->_param_value = array();

        $this->_setting = array(
            'trim'      => FALSE,
            'escape'    => TRUE,
            'prepare'   => TRUE,
            'autoreset' => TRUE
        );
    }

    /**
     * Define custom value for each setting,
     * Return setting value if $set is null
     * @param  string $key      : settings key
     * @param  mixed  $set      : value to set
     * @return mixed  $_setting : settings value
     */
    public function setting($key = NULL, $set = NULL)
    {
        if( ! is_null($key))
        {
            if( ! is_null($set))
            {
                $this->_setting[$key] = $set;
            }
            else
            {
                return $this->_setting[$key];
            }
        }
        else
        {
            return $this->_setting;
        }
    }

    public function select()
    {
        $params = func_get_args();

        $length = count($params);
        if( ! empty($params))
        {
            if($length === 1)
            {
                $expl = explode(',', $params[0]);

                foreach ($expl as $e)
                {
                    $this->_select[] = array(trim($e), trim($e));
                }
            }
            else if($length >= 2)
            {
                $expl0 = explode(',', $params[0]);
                $expl1 = explode(',', $params[1]);

                if(count($expl0) === count($expl1))
                {
                    foreach ($expl0 as $k => $e)
                    {
                        $select = array(
                            trim($e),
                            trim($expl1[$k])
                        );
                        if(isset($params[2])) $select[] = trim($params[2]);
                        $this->_select[] = $select;
                    }
                }
                else if(count($expl1) === 1 && $length === 2)
                {
                    foreach ($expl0 as $k => $e)
                    {
                        $select = array(
                            trim($e),
                            trim($e),
                            trim($params[1])
                        );
                        $this->_select[] = $select;
                    }
                }
            }
        }

        return $this;
    }

    public function where()
    {
        $params = func_get_args();

        if( ! empty($params))
        {
            if(count($params) >= 2)
            {
                if(is_array($params[1]))
                {
                    return $this->where_in($params[0], $params[1]);
                }
                else
                {
                    $this->_where[] = $params;
                }
            }
        }

        return $this;
    }

    public function where_in()
    {
        $params = func_get_args();

        if(count($params) === 2)
        {
            $this->_where_in[] = $params;
        }

        return $this;
    }

    public function like()
    {
        $params = func_get_args();
        if(count($params) >= 2)
        {
            $params[1] = "%$params[1]";
            call_user_func_array(array($this, 'where'), $params);
        }
        return $this;
    }

    public function regexp()
    {
        $params = func_get_args();
        if(count($params) >= 2)
        {
            if(preg_match('/^\/(.+)\/$/', $params[1]) === 1)
            {
                preg_match_all('/^\/(.+)\/$/', $params[1], $result);
                if(isset($result[1][0])) $params[1] = $result[1][0];
            }
            $params[1] = "^@$params[1]";
            call_user_func_array(array($this, 'where'), $params);
        }
        return $this;
    }

    /**
     * Order data by column name, ascending or descending
     * @param  string $by         : column name
     * @param  string $direction  : ascending / descending ('ASC' / 'DESC')
     * @param  string $table_name : table name (use this if you use join method)
     * @return object $this       : return the object for chaining method purpose
     */
    public function order($by, $direction = 'ASC', $table_name = NULL)
    {
        if($direction === 'ASC' OR $direction === 'DESC')
        {
            $this->_order[] = array($by, $direction, $table_name);
        }
        return $this;
    }

    /**
     * Alias for 'order' method
     * @return call function 'other'
     */
    public function sort()
    {
        $params = func_get_args();
        return call_user_func_array(array($this, 'order'), $params);
    }

    /**
     * Function to create a limit of data rows
     * if parameters is empty, as default limit data up to 1000 rows
     *
     * @param number $start Start position to fetch data (pointer)
     * @param number $count Amount of row(s) to fetch
     * @return object $this this object for chaining purpose
     */
    public function limit($start = 0, $count = 1000)
    {
        $this->_limit = "$start, $count";
        return $this;
    }

    public function join()
    {
        $params = func_get_args();
        $join_method = array('inner', 'left', 'right', 'outer');

        if( ! empty($params))
        {
            if(in_array($params[0], $join_method))
            {
                $method = array_shift($params);
                switch ($method)
                {
                    case 'inner':
                        $fun = 'INNER';
                        break;
                    case 'left':
                        $fun = 'LEFT';
                        break;
                    case 'right':
                        $fun = 'RIGHT';
                        break;
                    case 'outer':
                        $fun = 'OUTER';
                        break;
                    default:
                        break;
                }
            }
            else
            {
                $fun = 'INNER';
            }
            $params[]      = $fun;
            $this->_join[] = $params;
        }

        return $this;
    }

    public function get($table_name = NULL)
    {
        if(is_string($table_name))
        {
            $this->_table     = $table_name;
            $query            = $this->_build_get_query($table_name);
            $this->last_query = $query;

            if($this->setting('prepare'))
            {
                if($stmt = $this->_mysql->prepare($query))
                {
                    if( ! empty($this->_param_value))
                    {
                        $params = $this->_param_value;
                        array_unshift($params, $this->_param_type);
                        call_user_func_array(array($stmt, 'bind_param'), $this->_ref_values($params));
                    }
                    $this->reset(TRUE);
                    $stmt->execute();
                    return $this->_dynamic_bind_results($stmt);
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);
                    return array();
                }
            }
            else
            {
                if($result = $this->_mysql->query($query))
                {
                    $this->reset(TRUE);
                    return $this->result($result);
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);
                    return array();
                }
            }
        }
        else
        {

        }
    }

    public function get_total($table_name = NULL)
    {
        if(is_string($table_name))
        {
            $this->_table     = $table_name;
            $query            = $this->_build_total_query($table_name);
            $this->last_query = $query;

            if($this->setting('prepare'))
            {
                if($stmt = $this->_mysql->prepare($query))
                {
                    if( ! empty($this->_param_value))
                    {
                        $params = $this->_param_value;
                        array_unshift($params, $this->_param_type);
                        call_user_func_array(array($stmt, 'bind_param'), $this->_ref_values($params));
                    }
                    $this->reset(TRUE);
                    $stmt->execute();
                    $total = $this->_dynamic_bind_results($stmt);
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);
                    $total = array();
                }
            }
            else
            {
                if($result = $this->_mysql->query($query))
                {
                    $this->reset(TRUE);
                    return $this->result($result);
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);
                    $total = array();
                }
            }

            return count($total) === 1 ? $total[0]['total'] : 0;
        }
        else
        {
            return FALSE;
        }
    }

    public function insert($table_name = NULL, $data = array())
    {
        if(is_string($table_name))
        {
            if($this->setting('escape')) $this->escape($data);
            if($this->setting('trim')) self::trim($data);

            $this->_table     = $table_name;
            $query            = $this->_build_insert_query($data);
            $this->last_query = $query;

            return $this->_run_query($query);
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Returns the ID generated by a query on a table
     * with a column having the AUTO_INCREMENT attribute.
     * @return int The value of the AUTO_INCREMENT field that was updated by the previous query
     */
    public function insert_id()
    {
        return $this->_mysql->insert_id;
    }

    public function update($table_name = NULL, $data = array())
    {
        if( is_string($table_name) && is_array($data) )
        {
            if($this->setting('escape')) $this->escape($data);
            if($this->setting('trim')) self::trim($data);

            $this->_table     = $table_name;
            $query            = $this->_build_update_query($data);
            $this->last_query = $query;

            return $this->_run_query($query);
        }
        else
        {
            return FALSE;
        }
    }

    public function delete($table_name)
    {
        if( is_string($table_name) )
        {
            $this->_table     = $table_name;
            $query            = $this->_build_delete_query($data);
            $this->last_query = $query;

            return $this->_run_query($query);
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Build SELECT query from another method, concatenated as one query string
     * @return string : mysql query
     */
    private function _build_get_query()
    {
        $select = $this->_build_select();
        $join   = $this->_build_join();
        $where  = $this->_build_where();
        $order  = $this->_build_order();
        $limit  = $this->_limit === '' ? '' : "\nLIMIT $this->_limit";
        return "SELECT $select \nFROM `$this->_table` $join $where $order $limit;";
    }

    private function _build_total_query()
    {
        $select = "COUNT(*) AS total";
        $join   = $this->_build_join();
        $where  = $this->_build_where();
        return "SELECT $select \nFROM `$this->_table` $join $where;";
    }

    private function _build_select()
    {
        $select = "*";
        $table  = $this->_table;
        $join   = ! empty($this->_join);
        $tjoin  = array();

        foreach ($this->_join as $t)
        {
            $tjoin[] = $t[0];
        }

        if( ! empty($this->_select))
        {
            $sel = array();

            foreach ($this->_select as $s)
            {
                $l = count($s);

                if($l >= 2 && $join)
                {
                    $s0 = $s[0] === '*' ? '*' : "`$s[0]`";

                    if($l === 2)
                    {
                        if($s[0] === $s[1] OR $table === $s[1])
                        {
                            $sel[] = "`$table`.$s0";
                        }
                        else if(in_array($s[1], $tjoin))
                        {
                            $sel[] = "`$s[1]`.$s0";
                        }
                        else
                        {
                            $sel[] = "`$table`.$s0 AS '$s[1]'";
                        }
                    }
                    else
                    {
                        if(in_array($s[2], $tjoin))
                        {
                            $sel[] = "`$s[2]`.$s0 AS '$s[1]'";
                        }
                    }
                }
                else if($l === 1 && $join)
                {
                    $s0 = $s[0] === '*' ? '*' : "`$s[0]`";
                    $sel[] = "`$table`.$s0";
                }
                else if($l >= 2)
                {
                    $sel[] = $s[0] === $s[1] ? "`$s[0]`" : "`$s[0]` AS '$s[1]'";
                }
            }
            $select = implode(', ', $sel);
        }
        return $select;
    }

    private function _build_where()
    {
        $where = '';
        $table = $this->_table;
        $join  = ! empty($this->_join);
        $wh    = array();

        if( ! empty($this->_where))
        {
            foreach ($this->_where as $w)
            {
                $l = count($w);

                $w[0] = ($l === 3 && $join) ? "$w[2]`.`$w[0]"
                      : ($l === 2 && $join  ? "$table`.`$w[0]"
                      : $w[0]);

                if($l >= 2)
                {
                    $op = '=';

                    if(preg_match('/^(>|<|=>|<=)\d/', $w[1]) === 1)
                    {
                        $op   = preg_replace('/^(>|<|=>|<=)(\d+)$/', '${1}', $w[1]);
                        $w[1] = preg_replace('/^(>|<|=>|<=)(\d+)$/', '${2}', $w[1]);
                    }
                    else if(preg_match('/^%.+$/', $w[1]) === 1)
                    {
                        $op   = "LIKE";
                        $w[1] .= "%";
                    }
                    else if(preg_match('/^(\^@).+$/', $w[1]) === 1)
                    {
                        $op   = "REGEXP";
                        $w[1] = preg_replace('/^(\^@)(.+)$/', '${2}', $w[1]);
                    }
                    if($this->setting('prepare'))
                    {
                        if($this->setting('trim')) self::trim($w[1]);
                        $this->_param_type   .= $this->_determine_type($w[1]);
                        $this->_param_value[] = $w[1];
                        $wh[] = "`$w[0]` $op ?";
                    }
                    else
                    {
                        if($this->setting('trim')) self::trim($w[1]);
                        if($this->setting('escape')) $this->escape($w[1]);
                        $wh[] = "`$w[0]` $op '$w[1]'";
                    }
                }
            }
        }
        if( ! empty($this->_where_in))
        {
            foreach ($this->_where_in as $w)
            {
                if(count($w) === 2)
                {
                    if($this->setting('prepare'))
                    {
                        foreach ($w[1] as $d)
                        {
                            $this->_param_type   .= $this->_determine_type($d);
                            $this->_param_value[] = $d;
                        }
                        $in = preg_replace('/, $/', '', str_repeat('?, ', count($w[1])));
                    }
                    else
                    {
                        if($this->setting('trim')) self::trim($w[1]);
                        if($this->setting('escape')) $this->escape($w[1]);
                        $in = '\''. implode('\', \'', $w[1]) . '\'';
                    }

                    $wh[] = "`$w[0]` IN ($in)";
                }
            }
        }
        if( ! empty($wh))
        {
            $where = "\nWHERE " . implode(" AND ", $wh);
        }

        return $where;
    }

    private function _build_join()
    {
        $join  = "";
        $table = $this->_table;

        if( ! empty($this->_join))
        {
            $jo = array();

            foreach ($this->_join as $j)
            {
                $jo[] = "$j[3] JOIN `$j[0]` ON (`$j[0]`.`$j[1]` = `$table`.`$j[2]`)";
            }

            $join = "\n" . implode(' ', $jo);
        }
        return $join;
    }

    private function _build_order()
    {
        $order = "";
        $table = $this->_table;
        $join  = ! empty($this->_join);

        if( ! empty($this->_order))
        {
            $or = array();

            foreach ($this->_order as $o)
            {
                $l = count($o);

                if($join && $l === 3)
                {
                    $o[2] = is_null($o[2]) ? $table : $o[2];
                    $or[] = "`$o[2]`.`$o[0]` $o[1]";
                }
                else
                {
                    $or[] = "`$o[0]` $o[1]";
                }
            }

            $order = "\nORDER BY " . implode(', ', $or);
        }
        return $order;
    }

    private function _build_insert_query($data)
    {
        $keys   = array();
        $values = array();
        foreach ($data as $k => $v)
        {
            $keys[] = "`$k`";

            if($this->_check_word($v))
            {
                $values[] = $v;
            }
            else if($this->setting('prepare'))
            {
                $values[] = "?";
                $this->_param_type   .= $this->_determine_type($v);
                $this->_param_value[] = $v;
            }
            else
            {
                $values[] = "'$v'";
            }
        }
        $key = implode(', ', $keys);
        $val = implode(', ', $values);
        return "INSERT INTO `$this->_table` ($key) VALUES ($val);";
    }

    private function _build_update_query($data)
    {
        $limit   = $this->_limit === '' ? '' : "\nLIMIT $this->_limit";
        $changes = array();

        foreach ($data as $k => $v)
        {
            if($this->_check_word($v))
            {
                $changes[] = "`$k` = $v";
            }
            else if($this->setting('prepare'))
            {
                $changes[] = "`$k` = ?";
                $this->_param_type   .= $this->_determine_type($v);
                $this->_param_value[] = $v;
            }
            else
            {
                $changes[] = "`$k` = '$v'";
            }
        }

        $where   = $this->_build_where();
        $change = implode(', ', $changes);

        return "UPDATE `$this->_table` \nSET $change \n$where;";
    }

    public function _build_delete_query()
    {
        $where = $this->_build_where();
        return "DELETE FROM `$this->_table` \n$where;";
    }

    /**
     * Reset all the object properties that needed to build query to its default value
     * @param  boolean $auto : Use $auto = TRUE after run a query
     */
    public function reset($auto = FALSE)
    {
        if($this->setting('autoreset') OR ! $auto)
        {
            $this->_where       = array();
            $this->_where_in    = array();
            $this->_select      = array();
            $this->_join        = array();
            $this->_order       = array();
            $this->_limit       = '';
            $this->_param_type  = '';
            $this->_param_value = array();
            unset($this->_table);
        }
    }

    protected function _run_query($query)
    {
        if($this->setting('prepare'))
        {
            if($stmt = $this->_mysql->prepare($query))
            {
                if( ! empty($this->_param_value))
                {
                    $params = $this->_param_value;
                    array_unshift($params, $this->_param_type);
                    call_user_func_array(array($stmt, 'bind_param'), $this->_ref_values($params));
                }
                $this->reset(TRUE);
                return $stmt->execute();
            }
            else
            {
                $this->status(3);
                $this->reset(TRUE);
                return array();
            }
        }
        else
        {
            if($result = $this->_mysql->query($query))
            {
                $this->reset(TRUE);
                return $result;
            }
            else
            {
                $this->status(3);
                $this->reset(TRUE);
                return FALSE;
            }
        }
    }

    /**
     * This helper method takes care of prepared statements' "bind_result" method
     * , when the number of variables to pass is unknown.
     *
     * @param object $stmt Equal to the prepared statement object.
     * @return array The results of the SQL fetch.
     */
    protected function _dynamic_bind_results($stmt)
    {
        $parameters = array();
        $results    = array();
        $meta       = $stmt->result_metadata();
        $row        = array();

        while ($field = $meta->fetch_field())
        {
            $row[$field->name] = NULL;
            $parameters[]      = &$row[$field->name];
        }

        call_user_func_array(array($stmt, "bind_result"), $parameters);

        while ($stmt->fetch())
        {
            $ar = array();
            foreach ($row as $key => $val)
            {
                $ar[$key] = $val;
            }
            array_push($results, $ar);
        }
        return $results;
    }

    /**
     * Primitive way to get to from mysql result object
     * @param  object   $result : mysql result object
     * @param  constant $ref    : mysql constant for displaying result data
     * @return array    $array  : result data
     */
    public function result($result, $ref = MYSQLI_ASSOC)
    {
        $array = array();
        while ($row = $result->fetch_array($ref))
        {
            $array[] = $row;
        }
        return $array;
    }

    /**
     * Escape harmful characters which might affect a query.
     * @param  mixed $data : array / string to escape
     * @return mixed $data : escaped data
     */
    public function escape(&$data)
    {
        if(is_array($data))
        {
            $a = array();
            foreach ($data as $key => $value)
            {
                $a[$key] = $this->escape($value);
            }
            $data = $a;
            return $data;
        }
        else
        {
            $data = $this->_mysql->real_escape_string($data);
            return $data;
        }
    }

    /**
     * Helper function to trim data
     * This will remove all unnecessary whitespace inside variable
     *
     * @param mixed $data value that you want to trim (reference)
     * @return trimmed data
     */
    public static function trim(&$data)
    {
        if(is_array($data))
        {
            $a = array();
            foreach ($data as $key => $value)
            {
                $a[$key] = self::trim($value);
            }
            $data = $a;
            return $data;
        }
        else
        {
            $data = trim($data);
            return $data;
        }
    }

    /**
     * Function to get / set current status of this object
     * I'm using this for error handling
     * Status display as an array, status code and status text
     *
     * @param number $set Status to set
     * @return current status of this object
     */
    public function status($set = NULL)
    {
        if(is_integer($set))
        {
            switch ($set)
            {
                case 0:
                    $this->_status = array('status' => 0 , 'status_text' => 'OK');
                    break;
                case 1:
                    $this->_status = array('status' => 1 , 'status_text' => 'Database connect error');
                    break;
                case 2:
                    $this->_status = array('status' => 2 , 'status_text' => 'Construct parameters are incorrect');
                    break;
                case 3:
                    $this->_status = array('status' => 3 , 'status_text' => $this->_mysql->error);
                    break;
                default:
                    $this->_status = array('status' => 9 , 'status_text' => 'Unknown error');
                    break;
            }
        }

        return $this->_status;
    }

    /**
     * This method is needed for prepared statements. They require
     * the data type of the field to be bound with "i" s", etc.
     * This function takes the input, determines what type it is,
     * and then updates the param_type.
     *
     * @param mixed $item Input to determine the type.
     * @return string The joined parameter types.
     */
    protected function _determine_type($item)
    {
        switch (gettype($item))
        {
            case 'NULL':
            case 'string':
                return 's';
                break;
            case 'integer':
                return 'i';
                break;
            case 'blob':
                return 'b';
                break;
            case 'double':
                return 'd';
                break;
        }
    }

    private function _check_word($word)
    {
        $words = array(
            'ACCESSIBLE',         'ADD',                 'ALL',                           'ALTER',             'ANALYZE',
            'AND',                'AS',                  'ASC',                           'ASENSITIVE',        'BEFORE',
            'BETWEEN',            'BIGINT',              'BINARY',                        'BLOB',              'BOTH',
            'BY',                 'CALL',                'CASCADE',                       'CASE',              'CHANGE',
            'CHAR',               'CHARACTER',           'CHECK',                         'COLLATE',           'COLUMN',
            'CONDITION',          'CONSTRAINT',          'CONTINUE',                      'CONVERT',           'CREATE',
            'CROSS',              'CURRENT_DATE',        'CURRENT_TIME',                  'CURRENT_TIMESTAMP', 'CURRENT_USER',
            'CURSOR',             'DATABASE',            'DATABASES',                     'DAY_HOUR',          'DAY_MICROSECOND',
            'DAY_MINUTE',         'DAY_SECOND',          'DEC',                           'DECIMAL',           'DECLARE',
            'DEFAULT',            'DELAYED',             'DELETE',                        'DESC',              'DESCRIBE',
            'DETERMINISTIC',      'DISTINCT',            'DISTINCTROW',                   'DIV',               'DOUBLE',
            'DROP',               'DUAL',                'EACH',                          'ELSE',              'ELSEIF',
            'ENCLOSED',           'ESCAPED',             'EXISTS',                        'EXIT',              'EXPLAIN',
            'FALSE',              'FETCH',               'FLOAT',                         'FLOAT4',            'FLOAT8',
            'FOR',                'FORCE',               'FOREIGN',                       'FROM',              'FULLTEXT',
            'GRANT',              'GROUP',               'HAVING',                        'HIGH_PRIORITY',     'HOUR_MICROSECOND',
            'HOUR_MINUTE',        'HOUR_SECOND',         'IF',                            'IGNORE',            'IN',
            'INDEX',              'INFILE',              'INNER',                         'INOUT',             'INSENSITIVE',
            'INSERT',             'INT',                 'INT1',                          'INT2',              'INT3',
            'INT4',               'INT8',                'INTEGER',                       'INTERVAL',          'INTO',
            'IS',                 'ITERATE',             'JOIN',                          'KEY',               'KEYS',
            'KILL',               'LEADING',             'LEAVE',                         'LEFT',              'LIKE',
            'LIMIT',              'LINEAR',              'LINES',                         'LOAD',              'LOCALTIME',
            'LOCALTIMESTAMP',     'LOCK',                'LONG',                          'LONGBLOB',          'LONGTEXT',
            'LOOP',               'LOW_PRIORITY',        'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH',             'MAXVALUE',
            'MEDIUMBLOB',         'MEDIUMINT',           'MEDIUMTEXT',                    'MIDDLEINT',         'MINUTE_MICROSECOND',
            'MINUTE_SECOND',      'MOD',                 'MODIFIES',                      'NATURAL',           'NOT',
            'NO_WRITE_TO_BINLOG', 'NULL',                'NUMERIC',                       'ON',                'OPTIMIZE',
            'OPTION',             'OPTIONALLY',          'OR',                            'ORDER',             'OUT',
            'OUTER',              'OUTFILE',             'PRECISION',                     'PRIMARY',           'PROCEDURE',
            'PURGE',              'RANGE',               'READ',                          'READS',             'READ_WRITE',
            'REAL',               'REFERENCES',          'REGEXP',                        'RELEASE',           'RENAME',
            'REPEAT',             'REPLACE',             'REQUIRE',                       'RESIGNAL',          'RESTRICT',
            'RETURN',             'REVOKE',              'RIGHT',                         'RLIKE',             'SCHEMA',
            'SCHEMAS',            'SECOND_MICROSECOND',  'SELECT',                        'SENSITIVE',         'SEPARATOR',
            'SET',                'SHOW',                'SIGNAL',                        'SMALLINT',          'SPATIAL',
            'SPECIFIC',           'SQL',                 'SQLEXCEPTION',                  'SQLSTATE',          'SQLWARNING',
            'SQL_BIG_RESULT',     'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT',              'SSL',               'STARTING',
            'STRAIGHT_JOIN',      'TABLE',               'TERMINATED',                    'THEN',              'TINYBLOB',
            'TINYINT',            'TINYTEXT',            'TO',                            'TRAILING',          'TRIGGER',
            'TRUE',               'UNDO',                'UNION',                         'UNIQUE',            'UNLOCK',
            'UNSIGNED',           'UPDATE',              'USAGE',                         'USE',               'USING',
            'UTC_DATE',           'UTC_TIME',            'UTC_TIMESTAMP',                 'VALUES',            'VARBINARY',
            'VARCHAR',            'VARCHARACTER',        'VARYING',                       'WHEN',              'WHERE',
            'WHILE',              'WITH',                'WRITE',                         'XOR',               'YEAR_MONTH',
            'ZEROFILL'
        );

        return in_array($word, $words);
    }

    /**
     * Helper function to check number
     * This also convert string that contains valid number
     *
     * @param mixed $value String or number to check
     */
    protected function _is_number(&$value)
    {
        $check_number = FALSE;

        if(is_int($value))
        {
            $check_number = TRUE;
        }
        else if(preg_match('/^(0|[1-9]\d*)$/', $value) === 1)
        {
            $value = intval($value);
            $check_number = TRUE;
        }

        return $check_number;
    }

    /**
     * Reference is required for PHP 5.3+
     */
    protected function _ref_values($array)
    {
        if (strnatcmp(phpversion(),'5.3') >= 0)
        {
            $refs = array();
            foreach($array as $key => $value)
            {
                $refs[$key] = &$array[$key];
            }
            return $refs;
        }
        return $array;
    }

    /**
     * Destruct magic method.
     * Close mysqli connection if there's no error
     */
    public function __destruct()
    {
        if($this->status() === 0) $this->_mysql->close();
    }
}

/* End of file database.php */