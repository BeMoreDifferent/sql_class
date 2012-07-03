<?php

/**
 * @
 *
 *
 */
class SQL {

	public $sLastError;
	// Holds the last error

	public $sLastMessage;

	public $sLastQuery;
	// Holds the last query

	public $sLastId;
	// Holds the id of the last insert query

	public $sLastResult;
	// Holds the SQL query result

	public $iRecords;
	// Holds the total number of records returned

	public $iAffected;
	// Holds the total number of records affected

	public $aRawResults;
	// Holds raw 'arrayed' results

	public $aResult;
	// Holds a single 'arrayed' result

	public $aResults;
	// Holds multiple 'arrayed' result

	public $DBtype;
	// Type of Database (MySQL or SQLite)

	//-----------------------------------------

	/**
	 * MySQL VARs
	 */
	private $sHostname;
	// MySQL Hostname

	private $sUsername;
	// MySQL Username

	private $sPassword;
	// MySQL Password

	private $sDatabase;
	// MySQL Database

	/**
	 * SQLite VARs
	 */
	private $sPath;
	//Path to SQLite DB

	//-----------------------------------------

	private $st;

	private $test;

	private $obj;

	private $starttime;

	private $query_set = FALSE;

	//-----------------------------------------
	/**
	 * @abstract mini sql
	 */
	private $array_query;
	private $mini_sql_query;
	//private $mini_sql;
	//-----------------------------------------

	/**
	 *
	 * @param $memory
	 */
	function __construct($memory = false) {

		if (!defined(TEST))
			$this -> test = false;
		else
			$this -> test = TEST;

		if (!empty($this -> test))
			$this -> benchStart();

		if ($memory != false) {
			$this -> DBtype = 'sqlite::memory:';
			
		} else {

			if (defined('MYSQL_HOST') && defined('MYSQL_USER') && defined('MYSQL_PASS') && defined('MYSQL_NAME')) {

				$this -> sHostname = MYSQL_HOST;
				$this -> sUsername = MYSQL_USER;
				$this -> sPassword = MYSQL_PASS;
				$this -> sDatabase = MYSQL_NAME;

				$this -> DBtype = 'MySQL';

			} elseif (defined('SQL_PATH')) {
				try {
					if (!file_exists(SQL_PATH)) {
						if (fopen(SQL_PATH, 'w+')) {
							$this -> SetMessage('Datebasefile ' . SQL_PATH . ' created');
						} else {
							throw new Exception('Cant creat Datebasefile ' . SQL_PATH);
						}
					}

				} catch(Exception $e) {
					$this -> SetMessage($e -> getMessage(), true);
				}

				$this -> sPath = SQL_PATH;

				$this -> DBtype = 'SQLite';

			} else {

				$this -> sLastError[] = 'You have to define a datebase type.';
				$this -> DBtype = NULL;
			}
		}
		$this -> sLastMessage[] = 'Set DB type to ' . $this -> DBtype;

		$this -> Connect();

	}

	//-----------------------------------------

	/**
	 * @abstract Connects class to database
	 */
	public function Connect() {

		$this -> Close();

		try {

			if ($this -> DBtype == 'SQLite') {

				$this -> sDBLink = new PDO("sqlite:" . $this -> sPath);
				$this -> SetMessage('Connect to database: SQLite');

			} elseif ($this -> DBtype == 'PgSQL') {

				$this -> sDBLink = new PDO("pgsql:dbname=" . $this -> sDatabase . "host=" . $this -> sHostname, $this -> sUsername, $this -> sPassword);
				$this -> SetMessage('Connect to database: PostgreSQL');

			} elseif ($this -> DBtype == 'MySQL') {

				$this -> sDBLink = new PDO("mysql:host=" . $this -> sHostname . ";dbname=" . $this -> sDatabase, $this -> sUsername, $this -> sPassword);
				$this -> SetMessage('Connect to database: MySQL');

			}elseif($this -> DBtype == 'sqlite::memory:') {
				
				$this->sDBLink = new PDO("sqlite::memory:");
				$this -> SetMessage('Connect to database: SQLite::memory:');

			} else {
				$this -> SetMessage('No database used', true);
			}

			if ($this -> test == false) {
				$this -> sDBLink -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} else {
				$this -> sDBLink -> setAttribute(PDO::ERRMODE_SILENT, PDO::ERRMODE_EXCEPTION);
			}

			if (!$this -> sDBLink)
				$this -> SetMessage('Connection failed->' . json_encode($this -> sDBLink -> errorInfo()), true);

		} catch(PDOException $e) {

			$this -> SetMessage($e -> getMessage(), true);

		}

	}

	//----------------------------------------------------------------
	//------------ MINI SQL
	//----------------------------------------------------------------

	public function select($str = false, $return = false) {
		$this -> array_query['select'] = (!empty($str)) ? $str : '*';

		if ($return == true) {
			$this -> mini_sql();
			return $this -> output();
		} else {
			return $this;
		}
	}

	//-----------------------------------------

	public function from($str = false, $return = false) {
		$this -> array_query['from'] = (!empty($str)) ? $this -> cleanKey($str) : '';
		//$this -> $mini_sql_query = true;

		if ($return == true) {
			$this -> mini_sql();
			return $this -> output();
		} else {
			return $this;
		}
	}

	//-----------------------------------------

	public function where($str = false, $return = false) {
		$this -> array_query['where'] = (!empty($str)) ? $str : '';
		if ($return != false) {
			$this -> mini_sql();
			return $this -> output();
		} else {
			return $this;
		}
	}

	//-----------------------------------------

	public function order($str = false, $return = false) {
		$this -> array_query['order'] = (!empty($str)) ? $str : '';

		if ($return == true) {
			$this -> mini_sql();
			return $this -> output();
		} else {
			return $this;
		}
	}

	//-----------------------------------------

	private function mini_sql() {

		$this -> sLastQuery = "SELECT " . $this -> array_query['select'] . " FROM " . $this -> array_query['from'];
		$this -> sLastQuery .= (!empty($this -> array_query['where'])) ? ' WHERE ' . $this -> array_query['where'] : '';
		$this -> sLastQuery .= (!empty($this -> array_query['order'])) ? ' ORDER BY ' . $this -> array_query['order'] : '';

		try {
			$this -> obj = $this -> sDBLink -> prepare($this -> sLastQuery);
			$this -> query_set = true;

			$this -> SetMessage('Using custom SQL Prepared Statement: "' . $this -> sLastQuery . '"');

		} catch(PDOException $e) {
			$this -> SetMessage('Error in Custom SQL: "' . $this -> sLastQuery . '" -> "' . $e -> getMessage() . '"', true);
		}
		return $this;
	}

	//----------------------------------------------------------------
	//------------ SIMPLE QUERY FUNCTION
	//----------------------------------------------------------------

	public function query($query = false, $output = false) {
		if (empty($query)) {
			$this -> SetMessage('You have to creat a SQL- Query for sql::query()', true);
			return false;
		}

		$this -> sLastQuery = $query;
		$this -> query_set = true;

		try {
			$this -> obj = $this -> sDBLink -> prepare($this -> sLastQuery);

			$this -> SetMessage('Using custom SQL Prepared Statement: "' . $this -> sLastQuery . '"');

		} catch(PDOException $e) {
			$this -> SetMessage('Error in Custom SQL: "' . $this -> sLastQuery . '" -> "' . $e -> getMessage() . '"', true);
		}
		return $this;
	}

	//----------------------------------------------------------------
	//------------ CRUD MAIN FUNCTIONS
	//----------------------------------------------------------------

	/**
	 * @abstract get every row of a table
	 * @param $table [string] = name of table
	 */
	public function get($tabel = false) {

		if (empty($tabel) && $this -> query_set == FALSE) {
			$this -> SetMessage('You have to select a tabel for sql::get()', true);
			return false;
		}

		try {
			if ($this -> query_set == FALSE) :
				$this -> sLastQuery = 'SELECT * FROM ' . $this -> cleanKey($tabel);
				$this -> obj = $this -> sDBLink -> prepare($this -> sLastQuery);
				$this -> SetMessage('Data returned from table: "' . $this -> cleanKey($tabel) . '"');
			else :
				$this -> query_set = FALSE;
			endif;

			return $this -> output($this -> obj);

		} catch(PDOException $e) {

			$this -> SetMessage($e -> getMessage() . ' - ' . $this -> sLastQuery, true);
		}

	}

	//-----------------------------------------

	/**
	 * @abstract Adds a record to the database based on the array key names
	 *
	 * @param $db [string] = name of Table
	 * @param $value [array] = key => value
	 *
	 */
	public function insert($db = false, $value = false) {
		if (empty($db) && $this -> query_set == FALSE) {
			$this -> SetMessage('You have to select a table for sql::insert()', true);
			return false;
		}
		if (empty($value) && !is_array($value) && $this -> query_set == FALSE) {
			$this -> SetMessage('You have to insert a array for sql::insert()', true);
			return false;
		}
		if ($this -> query_set == true && empty($db)) {
			$this -> SetMessage('You have to insert a array for sql::insert()', true);
			return false;
		} elseif ($this -> query_set == true && !empty($db)) {
			$value = $db;
		}

		if ($this -> query_set == FALSE) :
			$this -> sLastQuery = "INSERT INTO " . $db . " (";

			$i = 0;
			$str = '';
			$multi = FALSE;
			foreach ($value as $k => $v) {
				if (!is_array($v)) {
					$str .= $this -> cleanKey($k) . ",";
					$i++;
				} else {

					foreach ($v as $key => $value) {
						$str .= $this -> cleanKey($key) . ",";
						$i++;

						$multi = TRUE;
					}
					break;
				}
			}
			$this -> sLastQuery .= substr($str, 0, -1) . ")  VALUES (:" . substr(str_replace(',', ',:', $str), 0, -2) . ")";

			try {
				$this -> st = $this -> sDBLink -> prepare($this -> sLastQuery);
			} catch(PDOException $e) {
				$this -> SetMessage($e -> getMessage() . ' - ' . $this -> sLastQuery, true);
			}
		else :

			$value = $this -> filterParameters($value);

			if (is_array($value[0]))
				$multi = true;

			$this -> query_set = FALSE;

			$this -> st = $this -> obj;

		endif;

		try {

			if ($multi == FALSE) {
				$this -> st -> execute($value);
			} else {
				foreach ($value as $k => $v) {
					$this -> st -> execute($v);
				}
			}

			//$this->sLastId = $this->st->lastInsertId();
			$this -> SetMessage('Data was insert into "' . $db . '" with id="' . $this -> sLastId . '"');

		} catch(PDOException $e) {
			$this -> SetMessage($e -> getMessage() . ' - ' . $this -> sLastQuery, true);
		}
	}

	//-----------------------------------------
	/**
	 * @abstract crUd Update -> changing one or more rows in tabele x
	 * @param $tabel [string] -> table - name
	 * @param $array [array] -> key (WHERE key) => value (= :value)
	 * @param $content [array] -> key (SET key) => value (= :value)
	 *
	 */
	public function update($tabel = false, $array = false, $content = false) {
		if (empty($tabel) && $this -> query_set == FALSE) {
			$this -> SetMessage('You have to select a table for sql::update()', true);
			return false;
		} elseif ($this -> query_set == FALSE) {
			$tabel = $this -> cleanKey($tabel);
		}

		if (empty($array) && $this -> query_set == FALSE) {
			$this -> SetMessage('You have to select a array to update something - sql::update()', true);
			return false;
		}

		if (empty($content) && $this -> query_set == FALSE) {
			$this -> SetMessage('You should select content to update the table - sql::update()', true);
			return false;
		}

		try {
			$key = array_keys($array);
			$key = $this -> cleanKey($key[0]);

			$set_key = array_keys($content);
			$set_key = $this -> cleanKey($set_key[0]);

			$this -> sLastQuery = 'UPDATE ' . $tabel . ' SET ' . $set_key . ' = :set_' . $set_key . ' WHERE ' . $key . ' = :' . $key;
			$this -> obj = $this -> sDBLink -> prepare($this -> sLastQuery);

			foreach ($array as $k => $v) {
				foreach ($content as $v_k => $value) {
					$prepared = array(':' . $k => $v, ':set_' . $v_k => $value);
					$this -> obj -> execute($prepared);
				}
			}

			$this -> iAffected = $this -> obj -> rowCount();

			$this -> SetMessage($this -> iAffected . ' rows were updated from table "' . $tabel . '" with key "' . $key . '"');
			return $this -> output($this -> obj);

		} catch(PDOException $e) {
			$this -> SetMessage($e -> getMessage() . ' - ' . $this -> sLastQuery, true);
		}
	}

	//-----------------------------------------
	/**
	 * @abstract deletes one or multiple rows of table
	 *
	 */
	public function delete($tabel = false, $array = false) {
		if (empty($tabel)) {
			$this -> SetMessage('You have to select a table for sql::delete()', true);
			return false;
		}

		if (empty($array)) {
			$this -> SetMessage('You have to select a array to delete something - sql::delete()', true);
			return false;
		}

		$key = array_keys($array);
		$key = $this -> cleanKey($key[0]);

		try {
			$this -> sLastQuery = 'DELETE FROM ' . $this -> cleanKey($tabel) . ' WHERE ' . $key . ' = :' . $key;

			$this -> obj = $this -> sDBLink -> prepare($this -> sLastQuery);

			foreach ($array as $key => $v) {
				$this -> obj -> bindParam(':' . $key, $v);
			}

			$this -> SetMessage('Data deleted from table: "' . $this -> cleanKey($tabel) . '"');

			return $this -> output($this -> obj);

		} catch(PDOException $e) {

			$this -> SetMessage($e -> getMessage() . ' - ' . $this -> sLastQuery, true);
		}

	}

	//----------------------------------------------------------------
	//------------ GENERAL TASKS
	//----------------------------------------------------------------

	/**
	 * @abstract Ausgabefunktion, welche die Ausgabe steuert
	 * @param $obj [objekt] = Das
	 */
	public function output($obj = false) {

		if (empty($obj) && empty($this -> obj))
			return false;
		elseif (empty($obj) && !empty($this -> obj)) {
			$obj = $this -> obj;
		}

		try {

			$obj -> execute();
			$obj -> setFetchMode(PDO::FETCH_ASSOC);

			$this -> sLastResult = $obj -> fetchAll();

			$this -> iRecords = count($this -> sLastResult);

			if (preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $obj -> queryString)) {
				$this -> SetMessage($this -> iRecords . ' rows were returned.');
			} elseif (preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $obj -> queryString)) {

				$this -> iRecords = $obj -> rowCount();
				$this -> SetMessage($obj -> rowCount() . ' rows got changed / deleted.');

			}

			if ($this -> iRecords > 0 || $obj -> rowCount() > 0) {
				return $this -> sLastResult;
			} else {
				return false;
			}

		} catch(PDOException $e) {
			$this -> error($e);
		}
	}

	//-----------------------------------------

	public function Close() {
		if ($this -> sDBLink) {
			$this -> sDBLink = NULL;
		}
	}

	//-----------------------------------------

	public function cleanAll($array) {
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_array($array[$key]))
					$array[$key] = $this -> filterParameters($array[$key]);
				if (is_string($array[$key]))
					$array[$key] = $this -> cleanKey($array[$key]);
			}
		}
		if (is_string($array))
			$array = $this -> cleanKey($array);

		return $array;
	}

	//-----------------------------------------

	/**
	 * @abstract Remove all non alpha numeric characters except a _
	 *
	 * @param $string [string] unsecure string
	 * @return [string] secure string
	 */
	private function cleanKey($string = '') {

		return preg_replace('/[^a-zA-Z0-9\_-]/', '', $string);

	}

	//-----------------------------------------

	public function filterParameters($array, $sub = false) {

		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_array($array[$key]) && $sub == false) {
					$array[$key] = $this -> filterParameters($array[$key], true);
				} else {
					$array[$key] = json_encode($array[$key]);
				}

				if (is_string($array[$key])) {
					$array[':' . $key] = $value;
					unset($array[$key]);
				}

			}
		} elseif (is_string($array[$key])) {
			$array[':' . $key] = $value;
			unset($array[$key]);
		}

		return $array;

	}

	//-----------------------------------------

	public function changeKey($array = false) {
		return $array = array_combine(array_map(create_function('$k', 'return ":".$k;'), array_keys($array)), array_values($array));
	}

	//-----------------------------------------

	private function benchStart() {
		if (!empty($this -> test)) {
			$mtime = microtime();
			$mtime = explode(" ", $mtime);
			$mtime = $mtime[1] + $mtime[0];
			$this -> starttime = $mtime;
		}
	}

	//-----------------------------------------

	private function benchEnd() {
		if (!empty($this -> test)) {
			$mtime = microtime();
			$mtime = explode(" ", $mtime);
			$mtime = $mtime[1] + $mtime[0];
			$endtime = $mtime;
			$totaltime = ($endtime - $this -> starttime);
			$this -> SetMessage("Die Datenbankabfragen haben " . $totaltime . " Sekunden gedauert.");
		}
	}

	//-----------------------------------------

	public function SetMessage($str, $err = false) {
		if ($err == true) {
			$this -> sLastError[] = $str;
		}

		$this -> sLastMessage[] = $str;

	}

	//-----------------------------------------

	public function Messages($err = false) {
		$str = '';

		if (!empty($this -> test))
			$this -> benchEnd();

		if (!empty($this -> sLastError)) {
			$str .= '<b>Errors:</b><br />';
			foreach ($this -> sLastError as $error) {
				$str .= $error . ' <br />';
			}
		}

		if (!empty($this -> sLastMessage) && $err != true) {
			$str .= '<b>Messages:</b><br />';
			foreach ($this -> sLastMessage as $msg) {
				$str .= $msg . ' <br />';
			}
		}

		return $str;
	}

}
