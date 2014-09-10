<?php

/**
 * Mysql Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Muhammad Sofyan <sofyan@octa7th.com>
 * @copyright 2013 - 2014 Hexastudio
 * @license   http://opensource.org/licenses/MIT
 * @version   1.3.0
 */

class Database
{
    /**
     * Mysqli object
     * @var mysqli
     * @since 0.9.5
     */
    private $_mysql;

    /**
     * Table name
     * @var string
     * @since 0.9.5
     */
    private $_table;

    /**
     * Last executed query
     * @var string
     * @since 0.9.5
     */
    public $last_query;

    /**
     * WHERE condition storage
     * @var array
     * @since 0.9.5
     */
    private $_where;

    /**
     * WHERE IN condition storage
     * @var array
     * @since 0.9.5
     */
    private $_where_in;

    /**
     * SELECT fields storage
     * @var array
     * @since 0.9.5
     */
    private $_select;

    /**
     * JOIN table and reference storage
     * @var array
     * @since 0.9.5
     */
    private $_join;

    /**
     * ORDER result by table column and direction storage
     * @var array
     * @since 0.9.5
     */
    private $_order;

    /**
     * number of result LIMIT to fetch storage
     * @var string
     * @since 0.9.5
     */
    private $_limit;

    /**
     * Store parameter type if we using mysql prepare statement
     * @var string
     * @since 0.9.5
     */
    private $_param_type;

    /**
     * Store parameter value if we using mysql prepare statement
     * @var array
     * @since 0.9.5
     */
    private $_param_value;

    /**
     * Predefined and user setting storage
     * @var array
     * @since 0.9.5
     */
    private $_setting;

    /**
     * Contains array number and text of current status.
     * 0 = OK / Everything works fine
     * 1 = Database connect error
     * 2 = Parameter construct is incorrect
     * 3 = Unknown error / Query Error
     * @var array
     * @since 0.9.5
     */
    private $_status;

    /**
     * Create new instance of mysql class
     * @param string  $host     MySql hostname
     * @param string  $username MySql username
     * @param string  $password MySql password
     * @param string  $db       MySql database name
     * @param integer $port     MySql port
     * @since 0.9.5
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
     * Add default value for query storage properties.
     * Initialize default value for object setting
     * @since 0.9.5
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
            'autoreset' => TRUE,
            'cleanNull' => TRUE
        );
    }

    /**
     * Define custom value for each setting.
     * Return setting value if $set is null
     * @param  string $key      : settings key
     * @param  mixed  $set      : value to set
     * @return mixed  $_setting : settings value
     * @since 0.9.5
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

    /**
     * Select field to fetch.
     * See in phpunit test for more usage example
     * @param  string $fields,...
     * @return Database
     * @since 0.9.5
     */
    public function select($fields)
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

    /**
     * Get total row from a table.
     * If table is null this method will return false.
     * @param  string   $table_name
     * @return int|bool Total row
     * @since  1.1.0
     */
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

    /**
     * Set condition as data filter.
     * See in phpunit test for more usage example
     * @return Database
     * @since 0.9.5
     */
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

    /**
     * Set condition as data filter.
     * See in phpunit test for more usage example
     * @return Database
     * @since 0.9.5
     */
    public function where_in()
    {
        $params = func_get_args();

        if(count($params) === 2)
        {
            $this->_where_in[] = $params;
        }

        return $this;
    }

    /**
     * Set condition as data filter.
     * See in phpunit test for more usage example
     * @return Database
     * @since 0.9.5
     */
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

    /**
     * Set condition as data filter.
     * See in phpunit test for more usage example
     * @return Database
     * @since 0.9.5
     * @unstable This method sometimes works not as you expected
     */
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
     * @return Database
     * @since 0.9.5
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
     * @return object this, call function 'other'
     * @since 0.9.5
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
     * @param  int $start : Start position to fetch data pointer
     * @param  int $count : Amount of rows to fetch
     * @return Database
     * @since 0.9.5
     */
    public function limit($start = 0, $count = 1000)
    {
        $this->_limit = "$start, $count";
        return $this;
    }

    /**
     * Use for joining table
     * See in phpunit test for more usage example
     * @return Database
     * @since 0.9.5
     */
    public function join()
    {
        $params = func_get_args();
        $join_method = array('inner', 'left', 'right', 'outer');

        if( ! empty($params))
        {
            $join_type = 'INNER';

            if(in_array($params[0], $join_method))
            {
                $method = array_shift($params);
                switch ($method)
                {
                    case 'inner':
                        $join_type = 'INNER';
                        break;
                    case 'left':
                        $join_type = 'LEFT';
                        break;
                    case 'right':
                        $join_type = 'RIGHT';
                        break;
                    case 'outer':
                        $join_type = 'OUTER';
                        break;
                    default:
                        break;
                }
            }
            $params[]      = $join_type;
            $this->_join[] = $params;
        }

        return $this;
    }

    /**
     * Run raw query
     * @param  string $query sql query
     * @param  array  $data  Data to set in prepare statement
     * @return mixed
     * @since  1.1.0
     * @unstable This method sometimes works not as you expected
     */
    public function query($query, $data = array())
    {
        if(is_string($query))
        {
            $this->last_query = $query;

            $is_select = preg_match('/^\(*SELECT/i', $query);
            $is_select = $is_select OR preg_match('/^\(*SHOW/i', $query);
            $is_update = preg_match('/^\(*UPDATE/i', $query);
            $is_insert = preg_match('/^\(*INSERT/i', $query);
            $is_delete = preg_match('/^\(*DELETE/i', $query);

            if($this->setting('prepare'))
            {
                if($stmt = $this->_mysql->prepare($query))
                {
                    if( is_array($data) && (count($data) > 0) )
                    {
                        $params = $data;
                        $param_type = "";
                        foreach ($params as $v)
                        {
                            $param_type .= $this->_determine_type($v);
                        }
                        array_unshift($params, $param_type);
                        call_user_func_array(array($stmt, 'bind_param'), $this->_ref_values($params));
                    }
                    $this->reset(TRUE);

                    if($is_select)
                    {
                        $stmt->execute();
                        return $this->_dynamic_bind_results($stmt);
                    }
                    else if($is_update)
                    {
                        return $stmt->execute();
                    }
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);

                    if($is_select)
                    {
                        return array();
                    }
                    else
                    {
                        return FALSE;
                    }
                }
            }
            else
            {
                if($result = $this->_mysql->query($query))
                {
                    $this->reset(TRUE);

                    if($is_select)
                    {
                        return $this->result($result);
                    }
                    else
                    {
                        return TRUE;
                    }
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);

                    if($is_select)
                    {
                        return array();
                    }
                    else
                    {
                        return FALSE;
                    }
                }
            }
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Get data from table
     * @param  string $table_name
     * @return array
     * @since 0.9.5
     */
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
            return FALSE;
        }
    }

    /**
     * Get value from mysql constanta
     * @param  string $value
     * @return mixed
     * @since 0.9.5
     */
    public function get_value($value = NULL)
    {
        $data = array();
        $this->_is_number($value);

        if( ! is_null($value) )
        {
            $val = "'$value'";

            if(preg_match('/^MYSQL_CONST_(\w+)_MYSQL_CONST$/', $value, $regTest) === 1)
            {
                $val = $regTest[1];
            }

            $query            = "SELECT $val;";
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
                    $data = $this->_dynamic_bind_results($stmt);
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);
                    $data = array();
                }
            }
            else
            {
                if($result = $this->_mysql->query($query))
                {
                    $this->reset(TRUE);
                    $data = $this->result($result);
                }
                else
                {
                    $this->status(3);
                    $this->reset(TRUE);
                    $data = array();
                }
            }
        }

        return count($data) > 0 ? $data[0] : NULL;
    }

    /**
     * Function insert
     * @param  string $table_name The name of the table.
     * @param  array $data Data containing information for inserting into the DB.
     * @return boolean Boolean indicating whether the insert query was completed succesfully.
     * @since 0.9.5
     */
    public function insert($table_name = NULL, $data = array())
    {
        if(is_string($table_name))
        {
            if($this->setting('cleanNull')) $this->clean_null($data);
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
     * With a column having the AUTO_INCREMENT attribute.
     * @return int The value of the AUTO_INCREMENT field that was updated by the previous query
     * @since 1.0.5
     */
    public function insert_id()
    {
        return $this->_mysql->insert_id;
    }

    /**
     * Update data in table
     * @param  string $table_name
     * @param  array  $data Data to update
     * @return boolean indicating whether the update query was completed succesfully.
     * @since 0.9.5
     */
    public function update($table_name = NULL, $data = array())
    {
        if( is_string($table_name) && is_array($data) )
        {
            if($this->setting('cleanNull')) $this->clean_null($data);
            if($this->setting('escape')) $this->escape($data);
            if($this->setting('trim')) self::trim($data);

            $this->_table     = $table_name;
            $query            = $this->_build_update_query($data);
            $this->last_query = $query;

            $fire = $this->_run_query($query);

            if($this->setting('prepare'))
            {
                return $fire;
            }
            else
            {
                return $this->_mysql->affected_rows > 0;
            }
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Delete data in table
     * @param  string $table_name
     * @return boolean indicating whether the delete query was completed succesfully.
     * @since 0.9.7
     */
    public function delete($table_name)
    {
        if( is_string($table_name) )
        {
            $this->_table     = $table_name;
            $query            = $this->_build_delete_query();
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
     * @since 0.9.7
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

    /**
     * Build total query from current operation
     * @return string sql query
     * @since 1.1.0
     */
    private function _build_total_query()
    {
        $select = "COUNT(*) AS total";
        $join   = $this->_build_join();
        $where  = $this->_build_where();
        return "SELECT $select \nFROM `$this->_table` $join $where;";
    }

    /**
     * Build select query from $_select field container
     * @return string part SELECT of sql query
     * @since 0.9.5
     */
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

    /**
     * Build where query from $_where condition container
     * @return string part WHERE of sql query
     * @since 0.9.5
     */
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

    /**
     * Build join query from $_join container
     * @return string part JOIN of sql query
     * @since 0.9.5
     */
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

    /**
     * Build order query from $_order container
     * @return string part ORDER of sql query
     * @since 0.9.5
     */
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

    /**
     * Build insert query from current operation
     * @param array $data New data to insert
     * @return string sql query
     * @since 0.9.5
     */
    private function _build_insert_query($data)
    {
        $keys   = array();
        $values = array();
        foreach ($data as $k => $v)
        {
            $keys[] = "`$k`";

            if(preg_match('/^MYSQL_CONST_(\w+)_MYSQL_CONST$/', $v, $regTest) === 1)
            {
                $values[] = $regTest[1];
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

    /**
     * Build update query from current operation
     * @param array $data New data to update
     * @return string sql query
     * @since 0.9.5
     */
    private function _build_update_query($data)
    {
        $limit   = $this->_limit === '' ? '' : "\nLIMIT $this->_limit";
        $changes = array();

        foreach ($data as $k => $v)
        {
            if($this->setting('prepare'))
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

    /**
     * Build update query from current operation
     * @return string sql query
     * @since 0.9.5
     */
    public function _build_delete_query()
    {
        $where = $this->_build_where();
        return "DELETE FROM `$this->_table` \n$where;";
    }

    /**
     * Reset all the object properties that needed to build query to its default value
     * @param boolean $auto : Use $auto = TRUE after run a query
     * @since 0.9.5
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

    /**
     * Run query
     * @param string $query sql query
     * @return mixed Query result
     * @since 0.9.7
     */
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
     * @param  object $stmt Equal to the prepared statement object.
     * @return array The results of the SQL fetch.
     * @since  0.9.5
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
     * @param  object $result : mysql result object
     * @param  int    $ref    : mysql constant for displaying result data
     * @return array  $array  : result data
     * @since 0.9.5
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
     * @since 0.9.5
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
     * @return mixed trimmed data
     * @since 0.9.5
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
     * Function to get / set current status of this object.
     * I'm using this for error handling
     * Status display as an array, status code and status text
     *
     * @param number $set Status to set
     * @return array current status of this object
     * @since 0.9.5
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
     * @since 0.9.5
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

    /**
     * Format mysql constant to this class standard format.
     * So when build query this class will handle it without string
     * @param  string $const Mysql constant in string
     * @return string        Formatted mysql constant
     * @since  1.1.0
     */
    public static function mysql_const($const)
    {
        return "MYSQL_CONST_{$const}_MYSQL_CONST";
    }

    /**
     * Helper function to check number.
     * This also convert string that contains valid number
     *
     * @param mixed $value String or number to check
     * @return boolean is number
     * @since 0.9.5
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
     * Clean Associative array if element's value is NULL
     * @param array $data Associative array
     * @return Database
     * @since 1.2.0
     */
    public function clean_null(array &$data)
    {
        foreach($data as $key => $value)
        {
            if($value === NULL)
            {
                unset($data[$key]);
            }
        }

        return $this;
    }

    /**
     * Reference is required for PHP 5.3+
     * @param array
     * @return array with reference key
     * @since 0.9.5
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
     * @since 0.9.5
     */
    public function __destruct()
    {
        $db_status = $this->status();
        if($db_status['status'] === 0) $this->_mysql->close();
    }
}

/* End of file database.php */