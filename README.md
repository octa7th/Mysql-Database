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
|5  |John      |Lennon   |27       |
|5  |John      |Lennon   |27       |

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
```php
// Equal to "SELECT * FROM `table1`;"
$data = $db->get('table1');
```
### SELECT
Select field in table that you want to get.

##### Regular select
```php
// Equal to "SELECT `column1`, `column2` ..."
$db->select('column1, column2');

$data = $db->get('table1');
```
##### Select as
```php
// Equal to "SELECT `column1` AS 'col1', `column2` AS 'col2' ..."
$db->select('column1, column2', 'col1, col2');

$data = $db->get('table1');
```
### WHERE
Add condition to fetch data you desire.

##### Regular where
```php
// Equal to "... WHERE `column1` = 'value1' ..."
$db->where('column1', 'value1');

$data = $db->get('table1');
```
##### Where in
```php
// Equal to "... WHERE `column1` IN ('value1', 'value2', 'value3') ..."
$db->where('column1', array('value1', 'value2', 'value3'));

$data = $db->get('table1');
```
##### Where like
```php
// Equal to "... WHERE `column1` LIKE '%value1%' ..."
$db->like('column1', 'value1');

$data = $db->get('table1');
```
##### Where regexp
```php
// Equal to "... WHERE `column1` REGEXP 'pattern' ..."
$db->regexp('column1', 'pattern');

$data = $db->get('table1');
```
## Chaining Method
Guess what? Almost all method in this class support chaining method. Exception for 'get', 'insert', 'update', and 'delete' because those method return data / query result.

So rather than...
```php
$db->select('column1, column2');
$db->select('column3');
$db->where('column1', 'value1');
$db->like('column2', 'value2');

$data = $db->get('table1');
```
You can simply write your code like this.
```php
$data = $db->select('column1, column2')
	->select('column3')
	->where('column1', 'value1')
	->like('column2', 'value2')
	->get('table1');
```