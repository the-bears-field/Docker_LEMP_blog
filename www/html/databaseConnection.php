<?php
interface ISelect
{
    function selectCommand();
}

interface IInsert
{
    function insertCommand(): PDOStatement;
}

interface IUpdate
{
    function updateCommand(): PDOStatement;
}

interface IDelete
{
    function deleteCommand(): PDOStatement;
}

abstract class DBConnection
{
    const DB_NAME            = 'myblog';
    const HOST               = 'db';         //'172.21.0.2' or 'db'      IPアドレス、またはコンテナ名を入力
    const PORT               = '3306';
    const CHARACTOR_ENCODING = 'utf8mb4';
    const USER               = 'root';
    const PASSWORD_BCRYPT    = 'secret';

    public function getPdo(): PDO
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
            echo 'error '.$e->getMessage;
            die();
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        return $pdo;
    }
}

/**
* indexで使用
*/
class DisplayAllTagsInIndex extends DBConnection implements ISelect
{

    function selectCommand() {
        $sqlCommand = 'SELECT tag_name FROM tags ORDER BY tags.tag_name ASC';
        $pdo        = $this->getPdo();
        $tagsList   = $pdo->prepare($sqlCommand);
        $tagsList->execute();
        return $tagsList->fetchAll(PDO::FETCH_COLUMN);
    }
}

/**
* クラス設計が完成し次第、削除予定
*/
class DatabaseConnection
{
    const DB_NAME            = 'myblog';
    const HOST               = 'db';         //'172.21.0.2' or 'db'      IPアドレス、またはコンテナ名を入力
    const PORT               = '3306';
    const CHARACTOR_ENCODING = 'utf8mb4';
    const USER               = 'root';
    const PASSWORD_BCRYPT    = 'secret';
    protected $tableName;
    protected $sqlCommand;

    public function getPdo(): PDO
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
            echo 'error '.$e->getMessage;
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

    public function databaseFetch(string $sqlCommand): PDOStatement
    {
        $pdo = $this->getPdo();

        try{
            $stmt   = $pdo->prepare($sqlCommand);
            $stmt->bindValue();
            $stmt->execute();
            $result = $stmt->fetchAll();

        } catch(Extention $e) {
            echo 'error'.$e->getMessage;
            die();
        }

        return $result;
    }
}