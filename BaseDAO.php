<?php
abstract class BaseDAO {
	
	public function __construct() {
	
	}
	
	public function __destruct() {
		$this->disconnect ();
		foreach ( $this as $key => $value ) {
			unset ( $this->$key );
		}
	}
	
	/**
	 * Connects to DB2 database.
	 *
	 * @throws Exception
	 * @return resource
	 */
	public function connect() {
		$connection = null;
		$conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=" . Config::get ( 'db.database_name' ) . ";HOSTNAME=" . Config::get ( 'db.hostname' ) . ";PORT=" . Config::get ( 'db.port' ) . ";PROTOCOL=TCPIP;UID=" . Config::get ( 'db.user' ) . ";PWD=" . Config::get ( 'db.password' ) . ";";
		$connection = db2_connect ( $conn_string, '', '' );
		if (! $connection) {
			$message = "Could not connect in the database: " . db2_stmt_errormsg ();
			throw new Exception ( $message );
		}
		return $connection;
	}
	
	/**
	 * Disconnects from database.
	 */
	public function disconnect() {
		$connection = $this->connect ();
		db2_close ( $connection );
	}
	
	/**
	 * Inserts a new record into database.
	 *
	 * @param unknown $sql
	 *        	the SQL statement for insert.
	 * @throws Exception
	 * @return unknown
	 */
	public function insertDb($sql) {
		$lastId = null;
		$connection = $this->connect ();
		$stmt = db2_exec ( $connection, $sql );
		$lastId = db2_last_insert_id ( $connection );
		if (! $stmt) {
			$message = "\nCould not insert into database. " . db2_stmt_errormsg ();
			throw new Exception ( $message );
		}
		$this->__destruct ();
		return $lastId;
	}
	
	/**
	 * Performs a SQL statement in database and returns rows.
	 *
	 * @param unknown $sql
	 *        	the SQL statement.
	 * @throws Exception
	 * @return multitype:
	 */
	public function selectDb($sql) {
		$rowArr = array ();
		$connection = $this->connect ();
		$stmt = db2_prepare ( $connection, $sql );
		$result = db2_execute ( $stmt );
		if ($result) {
			// echo "Result Set created.\n";
			while ( $row = db2_fetch_array ( $stmt ) ) {
				array_push ( $rowArr, $row );
			}
		} else {
			// echo "Result Set not created.\n";
			$message = "\nCould not select from database table. " . db2_stmt_errormsg ( $stmt );
			throw new Exception ( $message );
		}
		$this->__destruct ();
		return $rowArr;
	}
	
	/**
	 * Performs directly a SQL statement in database and returns success or not.
	 *
	 * @param unknown $sql
	 *        	the SQL statement.
	 * @throws Exception
	 * @return boolean
	 */
	public function queryDb($sql) {
		$success = false;
		$connection = $this->connect ();
		
		echo "\nsql: " . $sql;
		
		$result = db2_exec ( $connection, $sql );
		if ($result) {
			echo "queryDb ran Successfully.\n";
			$success = true;
		} else {
			$message = "\nCould not run the query in database table. " . db2_stmt_errormsg ();
			throw new Exception ( $message );
		}
		$this->__destruct ();
		return $success;
	}
	
	/**
	 * Gets a property value in the fetched row given a SQL statement.
	 *
	 * @param unknown $sql
	 *        	the sql statement.
	 * @throws Exception
	 * @return the property value.
	 */
	public function fetchObjectProperty($sql, $property) {
		$propertyValue = null;
		$connection = $this->connect ();
		$stmt = db2_prepare ( $connection, $sql );
		$result = db2_execute ( $stmt, array (0) );
		if ($result) {
			while ( db2_fetch_row ( $stmt ) ) {
				$propertyValue = db2_result ( $stmt, $property );
			}
		} else {
			$message = "\nCould not fetch the object from database table. " . db2_stmt_errormsg ( $stmt );
			throw new Exception ( $message );
		}
		$this->__destruct ();
		return $propertyValue;
	}
}

?>
