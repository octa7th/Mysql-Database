<?php

class DatabaseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var array
     */
    public $dbConfig;

    /**
     * Database (Mysqli-Wrapper) Object
     * @var Database
     */
    public $db;

    /**
     * @var mysqli
     */
    public $mysql;

    function __construct()
    {
        parent::__construct();
        $script_path = dirname(__FILE__) . '/mysqlSampleDatabase.sql';
        $this->dbConfig = array(
            'db_user' => 'root',
            'db_pass' => 'i3p1o2i3o2',
            'db_host' => 'localhost',
            'db_name' => 'classicmodels',
            'db_port' => 3306
        );

        $this->db = new Database(
            $this->dbConfig['db_host'],
            $this->dbConfig['db_user'],
            $this->dbConfig['db_pass'],
            $this->dbConfig['db_name'],
            $this->dbConfig['db_port']
        );
        $this->mysql = new mysqli(
            $this->dbConfig['db_host'],
            $this->dbConfig['db_user'],
            $this->dbConfig['db_pass'],
            $this->dbConfig['db_name'],
            $this->dbConfig['db_port']
        );
        $this->db->setting('prepare', FALSE);
    }

    public function testConnection()
    {
        $status = $this->db->status();
        $this->assertEquals(0, $status['status']);
    }

    public function testGet()
    {
        $data = $this->db->get('customers');
        $this->assertCount(122, $data);

        $dataTest = $this->_run_query("SELECT * FROM customers");
        $this->assertEquals($dataTest, $data);
    }

    public function testWhere()
    {
        $data = $this->db
            ->where('city', 'NYC')
            ->get('customers');
        $this->assertCount(5, $data);
        $dataTest = $this->_run_query("SELECT * FROM customers WHERE city = 'NYC'");
        $this->assertEquals($dataTest, $data);
    }

    public function testLike()
    {
        $data = $this->db
            ->like('customerName', 'inc')
            ->get('customers');
        $this->assertCount(21, $data);
        $dataTest = $this->_run_query("SELECT * FROM customers WHERE customerName like '%inc%'");
        $this->assertEquals($dataTest, $data);
    }

    public function testWhereIn()
    {
        $data = $this->db->where_in('city', array('london', 'madrid', 'milan'))->get('customers');
        $this->assertCount(8, $data);

        $dataTest = $this->_run_query("select * from customers where city in ('london', 'madrid', 'milan')");
        $this->assertEquals($dataTest, $data);
    }

    public function testOrder()
    {
        $data = $this->db
            ->where('country', 'USA')
            ->order('contactLastName', 'ASC')
            ->get('customers');
        $this->assertCount(36, $data);
        $dataTest = $this->_run_query("select * from customers where country = 'USA' order by contactLastName ASC");
        $this->assertEquals($dataTest, $data);

        $data = $this->db
            ->where('country', 'USA')
            ->order('contactLastName', 'DESC')
            ->get('customers');
        $dataTest = $this->_run_query("select * from customers where country = 'USA' order by contactLastName DESC");
        $this->assertEquals($dataTest, $data);
    }

    public function testLimit()
    {
        $data = $this->db
            ->limit(10, 5)
            ->get('customers');
        $this->assertCount(5, $data);
        $dataTest = $this->_run_query("select * from customers limit 10, 5");
        $this->assertEquals($dataTest, $data);
    }

    public function testJoin()
    {
        $data = $this->db
            ->join('customers', 'customerNumber', 'customerNumber')
            ->limit(0, 10)
            ->get('orders');
        $dataTest = $this->_run_query("select * from orders a join customers b on a.customerNumber = b.customerNumber limit 0,10");
        $this->assertEquals($dataTest, $data);

        $data = $this->db
            ->join('left', 'customers', 'customerNumber', 'customerNumber')
            ->limit(0, 10)
            ->get('orders');
        $dataTest = $this->_run_query("select * from orders a left join customers b on a.customerNumber = b.customerNumber limit 0,10");
        $this->assertEquals($dataTest, $data);

        $data = $this->db
            ->join('right', 'customers', 'customerNumber', 'customerNumber')
            ->limit(0, 10)
            ->get('orders');
        $dataTest = $this->_run_query("select * from orders a right join customers b on a.customerNumber = b.customerNumber limit 0,10");
        $this->assertEquals($dataTest, $data);
    }

    private function _run_query($sql = '')
    {
        $data = array();

        if($sql === '') return $data;

        $result = $this->mysql->query($sql);

        if($result instanceof mysqli_result)
        {
            while($row = $result->fetch_array(MYSQL_ASSOC))
            {
                $data[] = $row;
            }
        }

        return $data;
    }


}
 