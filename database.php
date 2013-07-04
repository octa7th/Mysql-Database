<?php

/**
 * Mysql Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Muhammad Sofyan <sofyan@octa7th.com>
 * @copyright Copyright (c) 2013
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version   0.9
 **/

class Database
{
	/**
	 * Mysqli object
	 * @var object
	 */
	private $_mysql;

	// private $_where;

	// private $_where_in;

	// private $_select;
	
	private $_setting;

	/**
	 * Contains array number and text of current status
	 * @var array
	 * 0 = OK / Everything works fine
	 * 1 = Database connect error
	 * 2 = Parameter construct is incorrect
	 */
	private $_status;
	
	function __construct($host = NULL, $username = NULL, $password = NULL, $db = NULL, $port = 3306)
	{
		if(is_null($host))
		{
			$this->status(2);
		}
		else
		{
			if( ! is_object($this->_mysql))
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
			'trim'    => FALSE,
			'escape'  => TRUE,
			'prepare' => TRUE
		);
	}

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

	public function sort()
	{
		$params = func_get_args();
		return call_user_func_array(array($this, 'order'), $params);
	}

	public function order($by, $direction = 'ASC', $table_name = NULL)
	{
		if($direction === 'ASC' OR $direction === 'DESC')
		{
			$this->_order[] = array($by, $direction, $table_name);
		}
		return $this;
	}

	public function limit($start = 0, $count = 1000)
	{
		$this->_limit = "$start, $count";
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
			$this->_table = $table_name;
			$query        = $this->_build_get_query($table_name);
			$this->_sql   = $query;

			
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
					$this->_reset();
					$stmt->execute();
					return $this->_dynamic_bind_results($stmt);
				}
				else
				{
					$this->status(3);
					$this->_reset();
					return array();
				}
			}
			else
			{
				if($result = $this->_mysql->query($query))
				{
					$this->_reset();
					return $this->result($result);
				}
				else
				{
					$this->status(3);
					$this->_reset();
					return array();
				}
			}
		}
		else
		{

		}
	}

	private function _build_get_query()
	{
		$select = $this->_build_select();
		$join   = $this->_build_join();
		$where  = $this->_build_where();
		$order  = $this->_build_order();
		$limit  = $this->_limit === '' ? '' : "\nLIMIT $this->_limit";
		return "SELECT $select \nFROM `$this->_table` $join $where $order $limit;";
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
					if($l === 2)
					{
						$sel[] = in_array($s[1], $tjoin) ? "`$s[1]`.`$s[0]`"
							   : ($s[0] === $s[1]        ? "`$table`.`$s[0]`"
							   : "`$table`.`$s[0]` AS '$s[1]'");
					}
					else
					{
						if(in_array($s[2], $tjoin))
						{
							$sel[] = "`$s[2]`.`$s[0]` AS '$s[1]'";
						}
					}
				}
				else if($l === 1 && $join)
				{
					$sel[] = "`$table`.`$s[0]`";
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
						$in = implode(', ', $w[1]);
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

	protected function _reset()
	{
		return;
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

	/**
	* This helper method takes care of prepared statements' "bind_result method
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
	*
	* @param mixed $str The mixed to escape.
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

	protected function _is_number(&$value)
	{
		$check_number = FALSE;

		if(is_number($value))
		{
			$check_number = TRUE;
		}
		else if(preg_match('/^(0|[1-9]\d*)$/', $value) === 1)
		{
			$value += 0;
			$check_number = TRUE;
		}

		return $check_number;
	}

	protected function _ref_values($array)
	{
		//Reference is required for PHP 5.3+
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

	public function __destruct() 
	{
		if($this->status() === 0) $this->_mysql->close();
	}
}

/* End of file database.php */