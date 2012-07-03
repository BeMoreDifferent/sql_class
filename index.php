<?php


//error_reporting(E_ALL);

include('inc/sql.class.php');

define('SQL_PATH', 'sqlite/database.sdb');
define('TEST', 'true');




$DB = new SQL();

/*
$DB->sLastQuery = "CREATE TABLE test (id INTEGER AUTO_INCREMENT,title VARCHAR(255) NOT NULL,text TEXT NOT NULL)";
$DB->sDBLink->query($DB->sLastQuery);
*/
//$DB->insert('test', array('title' => 'PDO', 'text' =>' sqlite rockt!!!!! "); die();') );
//$DB->delete('test',array('title'=>'PDO ist ganz toll'));

//$DB->update('test',array('title'=>'PDO'),array('title'=> 'PDO rockt'));

$insert = array(

		'id'=>9,
		'title'=>'fooobar 4',
		'text'=>'sinnloser text ohne sinn'

);


//$row = $DB->query("SELECT * FROM test WHERE id=6")->output();

//$DB->insert('test',$insert);
$row =  $DB->get('test');

//$row = $DB->select('*')->from('test')->where('id = 6',true);


print_r($DB);

echo "<hr>";
echo $DB->Messages();




