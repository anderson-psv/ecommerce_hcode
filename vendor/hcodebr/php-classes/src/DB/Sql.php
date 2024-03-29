<?php 

namespace Hcode\DB;

use PDOException;

class Sql {

	const HOSTNAME = "mysql-db";
	const USERNAME = "root";
	const PASSWORD = "teste";
	const DBNAME   = "mysql";
	const ENCODE   = "utf8";

	private $conn;

	public function __construct()
	{
		$this->conn = new \PDO(
			"mysql:dbname=".Sql::DBNAME.";host=".Sql::HOSTNAME.";charset=".Sql::ENCODE, 
			Sql::USERNAME,
			Sql::PASSWORD
		);
	}

	private function setParams($statement, $parameters = array())
	{
		foreach ($parameters as $key => $value)
		{
			$this->bindParam($statement, $key, $value);
		}

	}

	private function bindParam($statement, $key, $value)
	{
		$statement->bindParam($key, $value);
	}

	public function query($rawQuery, $params = array())
	{
		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

	}

	public function select($rawQuery, $params = array()):array
	{
		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

}

 ?>