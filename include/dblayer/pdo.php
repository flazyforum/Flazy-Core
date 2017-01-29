<?php
/**
 * @copyright Copyright (C) 2016-2017 Flazy.eu
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */
if (!function_exists('pdo_mysql')) {
    die('��� PHP ����� �� ����� ���������� ��������� Improved MySQL (mysqli). ��� ����������, ���� �� ������ ������������ ���� ������ MySQL 4.1 (��� ����� ������� ������) ��� ������ ����� ������. ������� PHP ������������ ��� ��������� �������������� ����������.');
}

class DBLayer{
	var $prefix;
	var $link_id;
	var $query_result;

	var $saved_queries = array();
	var $num_queries = 0;

	var $datatype_transformations = array(
		'/^SERIAL$/'	=>	'INT(10) UNSIGNED AUTO_INCREMENT'
	);
	
    private $dbh;
    private $error;
	private $stmt;
	
   function __construct($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect){
	   
		$this->prefix = $db_prefix;

		// Was a custom port supplied with $db_host?
		if (strpos($db_host, ':') !== false) {
            list($db_host, $db_port) = explode(':', $db_host);
        }

        // Set DSN
		if(isset($db_port)){
			$dsn = 'mysql:host=' . $db_host . ';dbname=' . $db_name . ';port=' . $db_port;
		} else {
			$dsn = 'mysql:host=' . $db_host . ';dbname=' . $db_name;
		}
	   
        
        // Set options
		$options = array(
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
		);
        // Create a new PDO instanace
        try{
            $this->dbh = new PDO($dsn, $db_username, $db_password, $options);
        }
        // Catch any errors
        catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }
	
		function beginTransaction(){
			return $this->dbh->beginTransaction();
		}

		function endTransaction(){
			return $this->dbh->commit();
		}

		function cancelTransaction(){
			return $this->dbh->rollBack();
		}
	
	function query($sql, $unbuffered = false){
        if (strlen($sql) > 140000) {
            die('������� ������� ������. ��������.');
        }

        if (defined('FORUM_SHOW_QUERIES')) {
            $q_start = get_microtime();
        }


        $this->stmt = $this->dbh->prepare($sql);
	}

	function bind($param, $value, $type = null){
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
			}
		}
		$this->stmt->bindValue($param, $value, $type);
	}


}