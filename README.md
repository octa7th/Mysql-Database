# Mysql Database Class

A dead simple mysqli wrapper for PHP to access mysql database.

This project is inspired by codeigniter database active record class. Feel free to contribute.


## Method

You can use this class in many ways, i'm gonna show you one by one. Using a demo table.

### GET

Get data from table
```php
require 'database.php';

$host     = 'localhost';
$user     = 'root';
$password = 'password';
$db_name  = 'database_name';
$port     = 3306; // Optional

$db   = new Database($host, $user, $password, $db_name, $port);
$data = $db->get('table1'); // Equal to "SELECT * FROM `table1`;"
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
// Equal to "... WHERE `column1` LIKE '%value1%'  ..."
$db->like('column1', 'value1');

$data = $db->get('table1');
```
##### Where regexp
```php
// Equal to "... WHERE `column1` REGEXP 'pattern'  ..."
$db->regexp('column1', 'pattern');

$data = $db->get('table1');
```