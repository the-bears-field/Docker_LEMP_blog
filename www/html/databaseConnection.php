<?php
class DatabaseConnection
{
    const DB_NAME            = 'myblog';
    const HOST               = 'db';         //'172.21.0.2' or 'db'      IPアドレス、またはコンテナ名を入力
    const PORT               = '3306';
    const CHARACTOR_ENCODING = 'utf8mb4';
    const USER               = 'root';
    const PASSWORD_BCRYPT    = 'secret';
    private $tableName;
    private $sqlCommand;

    public function getPdo ()
    {
        $dsn       = sprintf(
                        "mysql:host=%s; port=%s; dbname=%s; charset=%s",
                        self::HOST,
                        self::PORT,
                        self::DB_NAME,
                        self::CHARACTOR_ENCODING
                    );

        $pdoOption = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        try{
            $pdo   = new PDO($dsn, self::USER, self::PASSWORD_BCRYPT, $pdoOption);
        } catch (Extention $e) {
            echo 'error'.$e->getMessage;
            die();
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        return $pdo;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($value)
    {
        if(is_string($value) && isset($value)){
            $this->tableName = $value;
        }
    }

    public function getSqlCommand()
    {
        return $this->sqlCommand;
    }

    public function setSqlCommand()
    {
        if(is_string($value) && isset($value)){
            $this->sqlCommand = $value;
        }
    }
}