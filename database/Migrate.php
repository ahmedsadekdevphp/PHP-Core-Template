<?php
namespace Database;
use Core\Database;
use PDOException;
class Migrate
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function executeSql($sql)
    {
        try {
            $this->conn->exec($sql);
            return "Table creation successful";
        } catch (PDOException $e) {
            return "Table creation failed: " . $e->getMessage();
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }
}

