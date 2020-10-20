<?php
require_once __DIR__. '/vendor/autoload.php';

// .env読込
$dotenv = (Dotenv\Dotenv::createImmutable(__DIR__))->load();

interface ISelect
{
    public function selectCommand();
}

interface IInsert
{
    public function insertCommand();
}

interface IUpdate
{
    public function updateCommand();
}

interface IDelete
{
    public function deleteCommand(): PDOStatement;
}

interface ISetHttpGet
{
    public function setHttpGet($get);
}

interface ISetHttpPost
{
    public function setHttpPost($post);
}

interface ISetSession
{
    public function setSession($session);
}

abstract class DBConnection
{
    protected $pdo;

    public function __construct()
    {
        $dsn = sprintf(
            "mysql:host=%s; port=%s; dbname=%s; charset=%s",
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARACTOR_ENCODING']
        );

        $pdoOption = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        try{
            $pdo   = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD_BCRYPT'], $pdoOption);
        } catch (Extention $e) {
            echo 'error '.$e->getMessage;
            die();
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $this->pdo = $pdo;
    }
}

class DBConnctionFactory
{
    
}

/**
* indexで使用
*/
class AllTagsData extends DBConnection implements ISelect
{
    public function selectCommand() {
        $sqlCommand = 'SELECT tag_name FROM tags ORDER BY tags.tag_name ASC';
        $pdo        = $this->pdo;
        $tagsList   = $pdo->prepare($sqlCommand);
        $tagsList->execute();
        return $tagsList->fetchAll(PDO::FETCH_COLUMN);
    }
}


abstract class PostsDataUsedInIndex extends DBConnection
{
    protected $beginPostsCount;
    protected $postsCountNumber;
    protected $totalPostsCount;

    public function setBeginPostsCount($beginPostsCount) {
        $this->beginPostsCount = $beginPostsCount;
    }

    public function setPostsCountNumber($postsCountNumber) {
        $this->postsCountNumber = $postsCountNumber;
    }

    public function getTotalPostsCount() {
        return $this->totalPostsCount;
    }

    public function setTotalPostsCount() {
        $sqlCommand            = "SELECT COUNT(posts.post_id) FROM posts";
        $pdo                   = $this->pdo;
        $totalPostsCount       = $pdo->prepare($sqlCommand);
        $totalPostsCount->execute();
        $totalPostsCount       = $totalPostsCount->fetchColumn();
        $this->totalPostsCount = intval($totalPostsCount);
        $pdo                   = null;
    }
}

class PostsDataUsedInIndexByNomalProcess extends PostsDataUsedInIndex implements ISelect
{
    public function selectCommand() {
        $pdo = $this->pdo;

        if($this->totalPostsCount > 0){
            $sqlCommand = <<< 'SQL'
                SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                GROUP BY posts.post_id
                ORDER BY post_id DESC LIMIT :beginPostsCount, :postsCountNumber
                SQL;
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':beginPostsCount', $this->beginPostsCount, PDO::PARAM_INT);
            $stmt->bindValue(':postsCountNumber', $this->postsCountNumber, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
}

class PostsDataUsedInIndexByTagSearchProcess extends PostsDataUsedInIndex implements ISelect, ISetHttpGet
{
    private $tag;

    public function setHttpGet($get){
        $this->tag = $get['tag'];
    }

    public function setTotalArticleCount() {
        $tag        = $this->tag;
        $sqlCommand = <<< 'SQL'
            SELECT COUNT( * ) FROM (
                SELECT tags.tag_name FROM post_tags
                JOIN tags ON post_tags.tag_id = tags.tag_id
                WHERE tag_name = :tag
            ) AS is_find_tag
            SQL;
        $pdo        = $this->pdo;
        $isFindTag  = $pdo->prepare($sqlCommand);
        $isFindTag->bindValue(':tag', $tag, PDO::PARAM_STR);
        $isFindTag->execute();
        $isFindTag  = $isFindTag->fetchColumn();

        if(!$isFindTag){
            $this->totalArticleCount = 0;
        } else {
            $sqlCommand = <<< 'SQL'
                SELECT COUNT( * ) FROM (
                    SELECT posts.post_id, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags FROM posts
                    LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                    LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                    GROUP BY posts.post_id
                    HAVING tags LIKE :tag
                ) AS tag_count
                SQL;
            $totalPostsCount       = $pdo->prepare($sqlCommand);
            $totalPostsCount->bindValue(':tag', '%'. $tag. '%', PDO::PARAM_STR);
            $totalPostsCount->execute();
            $totalPostsCount       = $totalPostsCount->fetchColumn();
            $this->totalArticleCount = intval($totalPostsCount);
        }
    }

    public function selectCommand() {
        $pdo                 = $this->pdo;
        $tag                 = $this->tag;
        $totalPostsCount   = $this->totalPostsCount;
        $beginPostsCount = $this->beginPostsCount;
        $postsCountNumber = $this->postsCountNumber;

        if($totalPostsCount > 0 || $totalPostsCount){
            $sqlCommand  = <<< 'SQL'
                SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                GROUP BY posts.post_id
                HAVING tags LIKE :tag
                ORDER BY posts.post_id DESC LIMIT :beginPostsCount, :postsCountNumber
                SQL;
            $stmt        = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':tag', '%'. $tag. '%', PDO::PARAM_STR);
            $stmt->bindValue(':beginPostsCount', $beginPostsCount, PDO::PARAM_INT);
            $stmt->bindValue(':postsCountNumber', $postsCountNumber, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
}

class PostsDataUsedInIndexByWordsSearchProcess extends PostsDataUsedInIndex implements ISelect, ISetHttpGet
{
    private $searchWords;
    private $whereAndLikeClause;

    public function setHttpGet($get)
    {
        $tags = $get['searchWord'];
        // 全角スペースを半角へ
        $tags = preg_replace('/(\xE3\x80\x80)/', ' ', $tags);
        // 両サイドのスペースを消す
        $tags = trim($tags);
        // 改行、タブをスペースに変換
        $tags = preg_replace('/[\n\r\t]/', ' ', $tags);
        // 複数スペースを一つのスペースに変換
        $tags = preg_replace('/\s{2,}/', ' ', $tags);
        //文字列を配列に変換
        $array = preg_split('/[\s]/', $tags, -1, PREG_SPLIT_NO_EMPTY);
        //配列で重複している物を削除する
        $array = array_unique($array);
        //Keyの再定義
        $array = array_values($array);

        $this->searchWords = $array;
    }

    public function getSearchWords() {
        return $this->searchWords;
    }

    public function getWhereAndLikeClause() {
        return $this->whereAndLikeClause;
    }

    public function setWhereAndLikeClause() {
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

    public function setTotalArticleCount() {
        if(!$this->whereAndLikeClause){
            return false;
        }

        $pdo               = $this->pdo;
        $sqlCommand        = "SELECT COUNT(posts.post_id) FROM posts";
        $sqlCommand       .= $this->whereAndLikeClause;
        $totalPostsCount = $pdo->prepare($sqlCommand);
        $searchWords       = $this->searchWords;

        for ($i = 0; $i < count($searchWords); $i++) {
            $totalPostsCount->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
        }

        $totalPostsCount->execute();
        $totalPostsCount       = $totalPostsCount->fetchColumn();
        $this->totalArticleCount = intval($totalPostsCount);
    }

    public function selectCommand() {
        $pdo         = $this->pdo;
        $searchWords = $this->searchWords;

        $sqlCommand  = <<< 'SQL'
            SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
            LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
            LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
            LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
            SQL;

        if($this->totalPostsCount > 0){
            $sqlCommand .= $this->whereAndLikeClause;
            $sqlCommand .= 'GROUP BY posts.post_id ORDER BY posts.post_id DESC LIMIT :beginPostsCount, :postsCountNumber';
            $stmt        = $pdo->prepare($sqlCommand);

            for ($i = 0; $i < count($searchWords); $i++) {
                $stmt->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
            }

            $stmt->bindValue(':beginPostsCount', $this->beginPostsCount, PDO::PARAM_INT);
            $stmt->bindValue(':postsCountNumber', $this->postsCountNumber, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }
}

/**
* post,editで使用
*/
class SinglePostsData extends DBConnection implements ISelect, ISetHttpGet
{
    private $postId;

    public function setHttpGet($get) {
        $this->postId = intval($get['postID']);
    }

    public function selectCommand() {
        $postId = $this->postId;
        if(!$postId){
            return false;
        }

        $pdo        = $this->pdo;
        $sqlCommand = <<< 'SQL'
            SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ' ') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
            LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
            LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
            LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
            GROUP BY posts.post_id
            HAVING posts.post_id = :id
            SQL;
        $stmt   = $pdo->prepare($sqlCommand);
        $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result;
    }
}

/**
* newで使用
*/
class InsertPostAndTags extends DBConnection implements IInsert, ISetHttpPost
{
    private $title;
    private $post;
    private $tags;
    private $userId;

    public function setHttpPost($post){
        $this->title = $post['title'];
        $this->post = $post['post'];

        $tags = $post['tags'];
        // 全角スペースを半角へ
        $tags = preg_replace('/(\xE3\x80\x80)/', ' ', $tags);
        // 両サイドのスペースを消す
        $tags = trim($tags);
        // 改行、タブをスペースに変換
        $tags = preg_replace('/[\n\r\t]/', ' ', $tags);
        // 複数スペースを一つのスペースに変換
        $tags = preg_replace('/\s{2,}/', ' ', $tags);
        $tags = preg_split('/[\s]/', $tags, -1, PREG_SPLIT_NO_EMPTY);
        $tags = array_unique($tags);
        $tags = array_values($tags);
        $this->tags = $tags;
    }

    public function setUserId($userId){
        $this->userId = $userId;
    }

    public function insertCommand() {
        $title  = $this->title;
        $post   = $this->post;
        $userId = $this->userId;
        $tags   = $this->tags;

        try{
            $pdo  = $this->pdo;
            $stmt = $pdo->prepare("INSERT INTO posts(title, post) VALUES(:title, :post)");
            $stmt->bindValue(":title", $title, PDO::PARAM_STR);
            $stmt->bindValue(":post", $post, PDO::PARAM_STR);
            $stmt->execute();
            $lastInsertPostId = $pdo->lastInsertID();
            $stmt = $pdo->prepare("INSERT INTO user_uploaded_posts(user_id, post_id) VALUES(:user_id, :post_id)");
            $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
            $stmt->bindValue(":post_id", $lastInsertPostId, PDO::PARAM_INT);
            $stmt->execute();

            if ($tags) {
                //tagsに格納されている数だけloop処理の必要あり。
                for($i = 0; $i < count($tags); ++$i){
                    $stmt = $pdo->prepare("CALL sp_add_tags(:tag_name, :post_id)");
                    $stmt->bindValue(":tag_name", $tags[$i], PDO::PARAM_STR);
                    $stmt->bindValue(":post_id", $lastInsertPostId, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "データベースエラー";
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            echo $e->getMessage();
            die();
        }
    }
}

/**
* editで使用
*/
class UpdatePostAndTags extends DBConnection implements IUpdate, ISetHttpGet, ISetHttpPost, ISetSession
{
    private $title;
    private $post;
    private $addTags;
    private $removeTags;
    private $userId;
    private $postId;
    private $updatedAt;

    public function __construct() {
        parent::__construct();
        $this->updatedAt = (new Datetime())->format('Y-m-d H:i:s');
    }

    private function toArray($string) {
        $string = preg_replace('/(\xE3\x80\x80)/', ' ', $string);
        // 両サイドのスペースを消す
        $string = trim($string);
        // 改行、タブをスペースに変換
        $string = preg_replace('/[\n\r\t]/', ' ', $string);
        // 複数スペースを一つのスペースに変換
        $string = preg_replace('/\s{2,}/', ' ', $string);
        //文字列を配列に変換
        $array = preg_split('/[\s]/', $string, -1, PREG_SPLIT_NO_EMPTY);
        $array = array_unique($array);
        $array = array_values($array);
        return $array;
    }

    public function setHttpPost($post) {
        $post['tags']         ? $updatedTags = toArray($post['tags'])         : $updatedTags = [];
        $post['current-tags'] ? $currentTags = toArray($post['current-tags']) : $currentTags = [];
        $this->title       = $post['title'];
        $this->post        = $post['post'];
        $this->addTags     = array_diff($updatedTags, $currentTags);
        $this->removeTags  = array_diff($currentTags, $updatedTags);
    }

    public function setSession($session) {
        $this->userId = $session['id'];
    }

    public function setHttpGet($get){
        $this->postId = $get;
    }

    public function updateCommand() {
        try{
            $pdo  = $this->pdo;
            $stmt = $pdo->prepare('UPDATE posts SET title = :title, post = :post, updated_at = :updated_at WHERE post_id = :id');
            $stmt->bindValue(':title', $this->title, PDO::PARAM_STR);
            $stmt->bindValue(':post', $this->post, PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $this->updatedAt, PDO::PARAM_STR);
            $stmt->bindValue(':id', $this->postId, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            $errorMessage = "データベースエラー";
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            echo $e->getMessage();
            die();
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
        $pdo = $this->pdo;

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