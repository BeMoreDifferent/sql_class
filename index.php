<?php


//error_reporting(E_ALL);

include('inc/sql.class.php');

define('SQL_PATH', 'sqlite/database.sdb');
define('TEST', 'true');




$DB = new SQL();


//$DB->insert('test', array('title' => 'PDO', 'text' =>' sqlite rockt!!!!! "); die();') );
//$DB->delete('test',array('title'=>'PDO ist ganz toll'));
//$DB->update('test',array('title'=>'PDO'),array('title'=> 'PDO rockt'));
//$row = $DB->query("SELECT * FROM test WHERE id=6")->output();

$row =  $DB->get('test');

//$row = $DB->select('*')->from('test')->where('id = 6',true);

echo "<hr>" . $DB->Messages();
