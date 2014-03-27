# Mysql Database Class
A dead simple and powerfull mysqli wrapper for PHP to access mysql database. All you need is to call the file in your application. And create a new object using this Database class.

## Features
* Fetch data (SELECT) from tables using WHERE, WHERE IN, LIKE
* Support JOIN tables for SELECT data
* Use regex to SELECT data
* Insert new data
* Update existing data
* Delete / Remove data from table
* Escaping your query to prevent sqli
* Easy to configure and extend

## How to use
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

## Changelog
* v1.1.0 :
    * Remove function check word
    * Add new static method mysql_const
    * Add new method get_total
* v1.2.0 :
    * Create new method clean_null
    * Clean array data on method insert and update

Now i'm still working on API documentation on my website.
Feel free to contribute.

## License
### The MIT License (MIT)

Copyright (c) 2014, Muhammad Sofyan \<<octa7th@gmail.com>\>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.