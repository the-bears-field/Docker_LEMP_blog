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


abstract class DisplayPostsOnIndex extends DBConnection
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

class DisplayPostsOnIndexByNomalProcess extends DisplayPostsOnIndex implements ISelect
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

class DisplayPostsOnIndexByTagSearchProcess extends DisplayPostsOnIndex implements ISelect
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

class DisplayPostsOnIndexByWordsSearchProcess extends DisplayPostsOnIndex implements ISelect
{
    private $searchWords;
    private $whereAndLikeClause;

    function setSearchWords($string)
    {
        // 全角スペースを半角へ
        $string = preg_replace('/(\xE3\x80\x80)/', ' ', $string);
        // 両サイドのスペースを消す
        $string = trim($string);
        // 改行、タブをスペースに変換
        $string = preg_replace('/[\n\r\t]/', ' ', $string);
        // 複数スペースを一つのスペースに変換
        $string = preg_replace('/\s{2,}/', ' ', $string);
        //文字列を配列に変換
        $array = preg_split('/[\s]/', $string, -1, PREG_SPLIT_NO_EMPTY);
        //配列で重複している物を削除する
        $array = array_unique($array);
        //Keyの再定義
        $array = array_values($array);

        $this->searchWords = $array;
    }

    function getSearchWords() {
        return $this->searchWords;
    }

    function getWhereAndLikeClause() {
        return $this->whereAndLikeClause;
    }

    function setWhereAndLikeClause() {
        if(!isset($this->searchWords)){
            return '';
        }else{
            $whereAndLikeClause = '';
            $searchWords        = $this->searchWords;

            for ($i = 0; $i < count($searchWords); $i++) {
                if($i === 0){
                    $whereAndLikeClause .= ' WHERE post LIKE :'. strval($i);
                } else {
                    $whereAndLikeClause .= ' AND post LIKE :'. strval($i);
                }
            }

            for ($i = 0; $i < count($searchWords); $i++) {
                if($i === 0){
                    $whereAndLikeClause .= ' OR title LIKE :'. strval($i);
                } else {
                    $whereAndLikeClause .= ' AND title LIKE :'. strval($i);
                }
            }
            $whereAndLikeClause .= " ESCAPE '!'";

            $this->whereAndLikeClause = $whereAndLikeClause;
        }
    }

    function setTotalArticleCount() {
        if(!$this->whereAndLikeClause){
            return false;
        }
        $pdo               = $this->getPdo();
        $sqlCommand        = "SELECT COUNT(posts.post_id) FROM posts";
        $sqlCommand       .= $this->whereAndLikeClause;
        $totalArticleCount = $pdo->prepare($sqlCommand);
        $searchWords       = $this->searchWords;

        for ($i = 0; $i < count($searchWords); $i++) {
            $totalArticleCount->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
        }

        $totalArticleCount->execute();
        $totalArticleCount       = $totalArticleCount->fetchColumn();
        $this->totalArticleCount = intval($totalArticleCount);
    }

    function selectCommand() {
        $pdo         = $this->getPdo();
        $searchWords = $this->searchWords;

        $sqlCommand  = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                        LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                        LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                        LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id";

        if($this->totalArticleCount > 0){
            $sqlCommand .= $this->whereAndLikeClause;
            $sqlCommand .= 'GROUP BY posts.post_id ORDER BY posts.post_id DESC LIMIT :beginArticleDisplay, :countArticleDisplay';
            $stmt        = $pdo->prepare($sqlCommand);

            for ($i = 0; $i < count($searchWords); $i++) {
                $stmt->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
            }

            $stmt->bindValue(':beginArticleDisplay', $this->beginArticleDisplay, PDO::PARAM_INT);
            $stmt->bindValue(':countArticleDisplay', $this->countArticleDisplay, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
}

/**
* postで使用
*/
class DisplayPostsOnPost extends DBConnection implements ISelect
{
    private $postId;

    function setPostId($postId) {
        $this->postId = $postId;
    }

    function selectCommand() {
        if(!$this->postId){
            return false;
        }

        $pdo        = $this->getPdo();
        $sqlCommand = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                    LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                    LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                    LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                    GROUP BY posts.post_id
                    HAVING posts.post_id = :id";
        $stmt       = $pdo->prepare($sqlCommand);
        $stmt->bindValue(':id', $this->postId, PDO::PARAM_INT);
        $stmt->execute();
        $result     = $stmt->fetch();

        if($result){
            $result['tags'] === null ? $result['tags'] = [] : $result['tags'] = explode(',', $result['tags']);
            return $result;
        } else {
            return false;
        }
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