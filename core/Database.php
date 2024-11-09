<?php
namespace Core;
use PDO;
use PDOException;
class Database
{
    private static $instance = null;
    private $host = '';
    private $user = '';
    private $pass = '';
    private $dbname = '';

    private $dbh;
    private $error;

    public function __construct()
    {
        // Assign configuration values to class properties
        $this->host = config('DB_HOST');
        $this->user =  config('DB_USER');
        $this->pass = config('DB_PASS');
        $this->dbname = config('DB_NAME');

        // DSN for PDO connection
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        // Try to create a PDO instance and handle any errors
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            die($this->error);
        }
    }
    public static function getConnection()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->dbh;
    }

    public function prepare($sql)
    {
        return $this->dbh->prepare($sql);
    }

}
