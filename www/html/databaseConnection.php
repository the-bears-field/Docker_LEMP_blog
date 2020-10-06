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
class DisplayAllTags extends DBConnection implements ISelect
{
    function selectCommand() {
        $sqlCommand = 'SELECT tag_name FROM tags ORDER BY tags.tag_name ASC';
        $pdo        = $this->getPdo();
        $tagsList   = $pdo->prepare($sqlCommand);
        $tagsList->execute();
        return $tagsList->fetchAll(PDO::FETCH_COLUMN);
    }
}


abstract class DisplayPostsInIndex extends DBConnection
{
    protected $beginArticleDisplay;
    protected $countArticleDisplay;
    protected $totalArticleCount;

    function setBeginArticleDisplay($beginArticleDisplay) {
        $this->beginArticleDisplay = $beginArticleDisplay;
    }

    function setCountArticleDisplay($countArticleDisplay) {
        $this->countArticleDisplay = $countArticleDisplay;
    }

    function getTotalArticleCount() {
        return $this->totalArticleCount;
    }

    function setTotalArticleCount() {
        $sqlCommand              = "SELECT COUNT(posts.post_id) FROM posts";
        $pdo                     = $this->getPdo();
        $totalArticleCount       = $pdo->prepare($sqlCommand);
        $totalArticleCount->execute();
        $totalArticleCount       = $totalArticleCount->fetchColumn();
        $this->totalArticleCount = intval($totalArticleCount);
    }
}

class DisplayPostsInIndexNormalProcess extends DisplayPostsInIndex implements ISelect
{
    function selectCommand() {
        $pdo = $this->getPdo();

        if($this->totalArticleCount > 0){
            $sqlCommand = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                        LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                        LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                        LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                        GROUP BY posts.post_id
                        ORDER BY post_id DESC LIMIT :beginArticleDisplay, :countArticleDisplay";
            $stmt       = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':beginArticleDisplay', $this->beginArticleDisplay, PDO::PARAM_INT);
            $stmt->bindValue(':countArticleDisplay', $this->countArticleDisplay, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
}

class DisplayPostsInIndexTagSearchProcess extends DisplayPostsInIndex implements ISelect
{
    private $tag;

    function setTag($tag){
        $this->tag = $tag;
    }

    function setTotalArticleCount() {
        $sqlCommand = "SELECT COUNT( * ) FROM (
                            SELECT tags.tag_name FROM post_tags
                            JOIN tags ON post_tags.tag_id = tags.tag_id
                            WHERE tag_name = :tag
                        ) AS is_find_tag";
        $pdo        = $this->getPdo();
        $isFindTag  = $pdo->prepare($sqlCommand);
        $isFindTag->bindValue(':tag', $this->tag, PDO::PARAM_STR);
        $isFindTag->execute();
        $isFindTag  = $isFindTag->fetchColumn();

        if(!$isFindTag){
            $this->totalArticleCount = 0;
        } else {
            $sqlCommand = "SELECT COUNT( * ) FROM (
                                SELECT posts.post_id, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags FROM posts
                                LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                                LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                                GROUP BY posts.post_id
                                HAVING tags LIKE :tag
                            ) AS tag_count";
            $totalArticleCount       = $pdo->prepare($sqlCommand);
            $totalArticleCount->bindValue(':tag', '%'. $this->tag. '%', PDO::PARAM_STR);
            $totalArticleCount->execute();
            $totalArticleCount       = $totalArticleCount->fetchColumn();
            $this->totalArticleCount = intval($totalArticleCount);
        }
    }

    function selectCommand() {
        $pdo = $this->getPdo();

        if($this->totalArticleCount > 0 || $this->totalArticleCount){
            $sqlCommand  = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                            LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                            LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                            LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                            GROUP BY posts.post_id
                            HAVING tags LIKE :tag
                            ORDER BY posts.post_id DESC LIMIT :beginArticleDisplay, :countArticleDisplay";
            $stmt        = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':tag', '%'. $this->tag. '%', PDO::PARAM_STR);
            $stmt->bindValue(':beginArticleDisplay', $this->beginArticleDisplay, PDO::PARAM_INT);
            $stmt->bindValue(':countArticleDisplay', $this->countArticleDisplay, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
}

class DisplayPostsInIndexWordsSearchProcess extends DisplayPostsInIndex implements ISelect
{
    function selectCommand() {

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