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


/**
 * Sample
 */
$db->where('state', 'Jakarta')
     ->sort('first_name', 'DESC')
     ->limit(0, 5)
     ->get('student');

// Equals to "SELECT FROM `student` WHERE `state` = 'Jakarta' ORDER BY `first_name` DESC LIMIT 0, 5;"
```

Now i'm still working on API documentation on my website.
Feel free to contribute.