# Mysql Database Class
A dead simple and powerfull mysqli wrapper for PHP to access mysql database. All you need is to call the file in your application. And create a new object using this Database class.
```php
require 'database.php';

$host     = 'localhost';
$user     = 'root';
$password = 'password';
$db_name  = 'database_name';
$port     = 3306; // Optional

$db = new Database($host, $user, $password, $db_name, $port);
```
This project is inspired by codeigniter database active record class. Feel free to contribute.

## Method
You can use this class in many ways, i'm gonna show you one by one. Using a demo table.
##### student
|id |first_name|last_name|school_id|
|---|----------|---------|:-------:|
|1  |Muhammad  |Sofyan   |26       |
|2  |John      |Doe      |15       |
|3  |Jules     |Doe      |22       |
|4  |Sari      |Dewi     |22       |
|5  |Sarinah   |Alya     |23       |
|6  |John      |Lennon   |27       |

##### school
|id |name                |address   |
|---|--------------------|----------|
|26 |High School 26      |Utan Kayu |
|15 |High School 15      |Rawamangun|
|22 |Elementary School 22|Pramuka   |
|24 |High School 24      |Kayumanis |
|27 |Middle School 27    |Tegalan   |

### GET
Get data from table
Code: `$db->get($table_name)`
```php
// Equals to "SELECT * FROM `student`;"
$data = $db->get('student');
```


### SELECT
Select field in table that you want to get.
Code: `$db->select($field [,$alias] [,$table_name])`

##### Regular select
Code: `$db->select($field_name)`
```php
// Equals to "SELECT `first_name`, `last_name` ..."
$db->select('first_name, last_name');

$data = $db->get('student');
```


##### Select as
Code: `$db->select($field_name, $alias_name)`
```php
// Equals to "SELECT `first_name` AS 'fname', `last_name` AS 'lname' ..."
$db->select('first_name, last_name', 'fname, lname');

$data = $db->get('student');
```


### WHERE
Add condition to fetch data you desire.

##### Regular where
Code: `$db->where($field, $value)`
```php
// Equals to "... WHERE `first_name` = 'John' ..."
$db->where('first_name', 'John');

$data = $db->get('student');
```


##### Where in
Code: `$db->where($field, array())`
```php
// Equals to "... WHERE `last_name` IN ('Sofyan', 'Dewi', 'Alya') ..."
$db->where('last_name', array('Sofyan', 'Dewi', 'Alya'));

$data = $db->get('student');
```


##### Where like
Code: `$db->like($field, $value)`
```php
// Equals to "... WHERE `first_name` LIKE '%sari%' ..."
$db->like('first_name', 'sari');

$data = $db->get('student');
```


##### Where regexp
Code: `$db->regexp($field, $pattern)`
```php
// Equals to "... WHERE `first_name` REGEXP '^J' ..."
$db->regexp('first_name', '/^J/');

$data = $db->get('student');
```


### ORDER / SORT
Order data by field name
Code: `$db->order($field [,$direction] [,$table_name])` OR `$db->sort($field [,$direction] [,$table_name])`
Both will get same result
```php
// Equals to "... ORDER BY `first_name` DESC ..."
$db->sort('first_name', 'DESC');

$data = $db-get('student');
```


### LIMIT
Add limit when fetching data
Code: `$db->limit([$start] [,$amount])`
```php
// Equals to "... LIMI"
```


## Chaining Method
Guess what? Almost all method in this class support chaining method. Exception for 'get', 'insert', 'update', and 'delete' because those method return data / query result.

So rather than...
```php
$db->select('first_name, last_name');
$db->select('school_id');
$db->where('first_name', 'John');
$db->like('last_name', 'Doe');

$data = $db->get('student');
```
You can simply write your code like this:
```php
$data = $db->select('first_name, last_name')
	->select('school_id')
	->where('first_name', 'John')
	->like('last_name', 'Doe')
	->get('student');
```