<?php
echo "<pre>";

require 'database.php';

$c = array(
	'host' => 'localhost',
	'user' => 'root',
	'pass' => '',
	'name' => 'chord',
	'port' => 3306
);

$db = new Database($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);

$data = array();
$db->setting('prepare', FALSE);
// $db->setting('autoreset', FALSE);
/*$data = $db
	->where('name', array('Ungu', 'Peterpan', 'Dewa'))
	// ->where('name', 'Padi')
	// ->select('artist_id, uri, name')
	// ->where('id', '<=6')
	// ->regexp('id', '/^[1-9]$/')
	
	// ->select('uri, name, artist_id', 'url, nama_lagu, artist_id')
	// ->select('name', 'nama_artist', 'artist')
	// ->join('artist', 'id', 'artist_id')
	// ->like('name', 'ada', 'artist')
	// ->order('name', 'DESC', 'artist')
	// ->order('name')
	->limit(0, 20)
	->get('artist');*/
$data = array(
	'name' => 'Sofyan Test2',
	'uri'  => 'sofyan-test2'
);
$insert = $db->insert('artist', $data);

echo "<pre>";
if( ! empty($db->_select))
{
	echo "\n=================\n";
	echo "SELECT";
	echo "\n-----------------\n";
	print_r($db->_select);
}
if( ! empty($db->_join))
{
	echo "\n=================\n";
	echo "JOIN";
	echo "\n-----------------\n";
	print_r($db->_join);
}
if( ! empty($db->_where))
{
	echo "\n=================\n";
	echo "WHERE";
	echo "\n-----------------\n";
	print_r($db->_where);
}
if( ! empty($db->_where_in))
{
	echo "\n=================\n";
	echo "WHERE IN";
	echo "\n-----------------\n";
	print_r($db->_where_in);
}
echo "\n=================\n";
echo "RESULT";
echo "\n-----------------\n";
var_dump($insert);
print_r($data);
echo "\n=================\n";
echo "STATUS";
echo "\n-----------------\n";
print_r($db->status());
echo "\n=================\n";
echo "QUERY";
echo "\n-----------------\n";
echo "$db->last_query\n";
echo "\n";
echo "</pre>";
die();