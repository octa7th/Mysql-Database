# Mysql Database Class

A dead simple mysqli wrapper for PHP to access mysql database.

This project is inspired by codeigniter database active record class. Feel free to contribute.


## Method

You can use this class in many ways, i'm gonna show you one by one. Using a demo table.

### Get

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
### Select

Select field in table that you want to get.

#### Ordinary SELECT
```php
$db->select('column1, column2'); // Equal to "SELECT `column1`, `column2` ..."
$db->get('table1');
```
#### SELECT AS
```php
$db->select('column1, column2', 'col1, col2'); // Equal to "SELECT `column1` AS 'col1', `column2` AS 'col2' ..."
$db->get('table1');
```