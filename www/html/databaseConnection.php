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
    public function deleteCommand();
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

    public function setTotalPostsCount() {
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
            $this->totalPostsCount = 0;
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
            $this->totalPostsCount = intval($totalPostsCount);
        }
    }

    public function selectCommand() {
        $pdo              = $this->pdo;
        $tag              = $this->tag;
        $totalPostsCount  = $this->totalPostsCount;
        $beginPostsCount  = $this->beginPostsCount;
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

    public function setHttpGet($get) {
        $searchWords = $get['searchWord'];
        $this->searchWords = $this->toArray($searchWords);
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

    public function setTotalPostsCount() {
        if(!$this->whereAndLikeClause){
            return false;
        }

        $pdo               = $this->pdo;
        $sqlCommand        = "SELECT COUNT(posts.post_id) FROM posts ";
        $sqlCommand       .= $this->whereAndLikeClause;
        $totalPostsCount   = $pdo->prepare($sqlCommand);
        $searchWords       = $this->searchWords;

        for ($i = 0; $i < count($searchWords); $i++) {
            $totalPostsCount->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
        }

        $totalPostsCount->execute();
        $totalPostsCount       = $totalPostsCount->fetchColumn();
        $this->totalPostsCount = intval($totalPostsCount);
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
        $post['tags']         ? $updatedTags = $this->toArray($post['tags'])         : $updatedTags = [];
        $post['current-tags'] ? $currentTags = $this->toArray($post['current-tags']) : $currentTags = [];
        $addTags           = array_diff($updatedTags, $currentTags);
        $removeTags        = array_diff($currentTags, $updatedTags);
        $this->addTags     = array_values($addTags);
        $this->removeTags  = array_values($removeTags);
        $this->title       = $post['title'];
        $this->post        = $post['post'];
    }

    public function setSession($session) {
        $this->userId = $session['id'];
    }

    public function setHttpGet($get){
        $this->postId = $get['postID'];
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

        if($this->addTags){
            $addTags = $this->addTags;

            for($i = 0; $i < count($addTags); ++$i){
                $stmt = $pdo->prepare("CALL sp_add_tags(:tag_name, :post_id)");
                $stmt->bindValue(":tag_name", $addTags[$i], PDO::PARAM_STR);
                $stmt->bindValue(":post_id", $this->postId, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        if($this->removeTags){
            $removeTags = $this->removeTags;

            for($i = 0; $i < count($removeTags); ++$i){
                $stmt = $pdo->prepare("CALL sp_remove_tags(:tag_name, :post_id)");
                $stmt->bindValue(":tag_name", $removeTags[$i], PDO::PARAM_STR);
                $stmt->bindValue(":post_id", $this->postId, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
}

/**
* loginで使用
*/
class UserDataUsedInLogin extends DBConnection implements ISelect, ISetHttpPost
{
    private $email;

    public function setHttpPost($post) {
        $this->email = $post['email'];
    }

    public function selectCommand() {
        try {
            $pdo    = $this->pdo;
            $stmt   = $pdo->prepare('SELECT * FROM user WHERE email = :email');
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->execute();
            $result =  $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch(PDOException $e) {
            $errorMessage = 'データベースエラー';
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            //echo $e->getMessage();
            die();
        }
    }
}

/**
* accountで使用
*/
abstract class UserDataUsedInAccount extends DBConnection implements ISetSession
{
    protected $userId;
    protected $updatedAt;

    public function __construct() {
        parent::__construct();
        $this->updatedAt = (new Datetime())->format('Y-m-d H:i:s');
    }

    public function setSession($session) {
        $this->userId = $session['id'];
    }
}

class UserDataUsedInAccountByEditUserNameProcess extends UserDataUsedInAccount implements IUpdate, ISetHttpPost
{
    private $userName;

    public function setHttpPost($post) {
        $this->userName = $post['username'];
    }

    public function updateCommand() {
        $userName   = $this->userName;
        $updatedAt  = $this->updatedAt;
        $userId     = $this->userId;
        $sqlCommand = <<<'SQL'
            UPDATE user
            SET name = :userName, updated_at = :updated_at
            WHERE user_id = :userId
            SQL;

        try{
            $pdo  = $this->pdo;
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userName', $userName, PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', $updatedAt, PDO::PARAM_STR);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $pdo  = null;
            $_SESSION['name'] = $userName;
        } catch (PDOException $e) {
            console.log($e);
        }
    }
}

class UserDataUsedInAccountByEditEmailProcess extends UserDataUsedInAccount implements IUpdate, ISetHttpPost
{
    private $email;
    private $password;

    public function setHttpPost($post) {
        $this->email    = $post['email'];
        $this->password = $post['password'];
    }

    public function updateCommand() {
        $email      = $this->email;
        $password   = $this->password;
        $updatedAt  = $this->updatedAt;
        $userId     = $this->userId;
        $sqlCommand = <<<'SQL'
            SELECT password FROM user
            WHERE user_id = :userId
            SQL;

        try {
            $pdo  = $this->pdo;
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $correntPassword = $stmt->fetchColumn();
        } catch (PDOException $e) {
            console.log($e);
        }

        if (password_verify($password, $correntPassword)) {
            $sqlCommand = <<<'SQL'
                UPDATE user
                SET email = :email, updated_at = :updated_at
                WHERE user_id = :userId
                SQL;

            try {
                $stmt = $pdo->prepare($sqlCommand);
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $stmt->bindValue(':updated_at', $updatedAt, PDO::PARAM_STR);
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $pdo  = null;
                $_SESSION['email'] = $email;
            } catch (PDOException $e) {
                console.log($e);
            }
        }
    }
}

class UserDataUsedInAccountByEditPasswordProcess extends UserDataUsedInAccount implements IUpdate, ISetHttpPost
{
    private $password;
    private $newPassword;

    public function setHttpPost($post) {
        $this->password    = $post['current-password'];
        $this->newPassword = $post['new-password'];
    }

    public function updateCommand() {
        $password    = $this->password;
        $newPassword = $this->newPassword;
        $updatedAt   = $this->updatedAt;
        $userId      = $this->userId;
        $sqlCommand  = <<<'SQL'
            SELECT password FROM user
            WHERE user_id = :userId
            SQL;

        try {
            $pdo  = $this->pdo;
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $correntPassword = $stmt->fetchColumn();
        } catch (PDOException $e) {
            console.log($e);
        }

        if (password_verify($password, $correntPassword)) {
            $sqlCommand = <<<'SQL'
                UPDATE user
                SET password = :password, updated_at = :updated_at
                WHERE user_id = :userId
                SQL;

            try {
                $stmt = $pdo->prepare($sqlCommand);
                $stmt->bindValue(':password', password_hash($newPassword, PASSWORD_DEFAULT), PDO::PARAM_STR);
                $stmt->bindValue(':updated_at', $updatedAt, PDO::PARAM_STR);
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $pdo  = null;
            } catch (PDOException $e) {
                console.log($e);
            }
        }
    }
}

class UserDataUsedInAccountByDeactivateUserProcess extends UserDataUsedInAccount implements IDelete
{
    public function deleteCommand() {
        $userId     = $this->userId;
        $pdo        = $this->pdo;

        //ユーザ削除
        $sqlCommand = <<< 'SQL'
            DELETE FROM user WHERE user_id = :userId
            SQL;
        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        //記事の削除に伴い、タグの関連付けも削除
        $sqlCommand = <<< 'SQL'
            DELETE pt FROM post_tags AS pt
            LEFT JOIN user_uploaded_posts AS up ON pt.post_id = up.post_id
            WHERE up.user_id = :userId
            SQL;

        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        //記事の削除に伴い、1件も関連付けされていないタグを削除
        //post_tagからtag_id取得
        $sqlCommand = <<< 'SQL'
            SELECT tag_id FROM post_tags
            SQL;
        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->execute();
            $remainTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $remainTags = array_unique($remainTags);
            $remainTags = array_values($remainTags);
        } catch (PDOException $e) {
            console.log($e);
        }

        //tagsからtag_id取得
        $sqlCommand = <<< 'SQL'
            SELECT tag_id FROM tags
            SQL;
        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->execute();
            $currentTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            console.log($e);
        }

        //削除対象を選定
        $removeTags = array_diff($currentTags, $remainTags);

        foreach($removeTags as &$removeTag){
            $removeTag = intval($removeTag);
        }

        //ループを回してtagを削除
        if ($removeTags) {
            for($i = 0; $i < count($removeTags); $i++){
                $sqlCommand = <<< 'SQL'
                    DELETE FROM tags WHERE tag_id = :tagId
                    SQL;
                $stmt = $pdo->prepare($sqlCommand);
                $stmt->bindValue(':tagId', $removeTags[$i], PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        $sqlCommand = <<< 'SQL'
            DELETE FROM tags WHERE tag_id = :tagId
            SQL;
        $stmt = $pdo->prepare($sqlCommand);
        $stmt->bindValue(':tagId', 10, PDO::PARAM_INT);
        $stmt->execute();

        //削除対象ユーザーが投稿した記事を全て削除する
        $sqlCommand = <<< 'SQL'
            DELETE p FROM posts AS p
            LEFT JOIN user_uploaded_posts AS up ON p.post_id = up.post_id
            WHERE up.user_id = :userId
            SQL;

        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        //postsとuserの関連テーブルから削除対象となっているユーザを削除
        $sqlCommand = <<< 'SQL'
            DELETE  FROM user_uploaded_posts
            WHERE user_id = :userId
            SQL;

        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }
    }
}

class UserDataUsedInAccountByNormalProcess extends UserDataUsedInAccount implements ISelect
{
    public function selectCommand() {
        $pdo    = $this->pdo;
        $userId = $this->userId;
        try {
            $stmt   = $pdo->prepare("SELECT name, email FROM user WHERE user_id = :userId");
            $stmt->bindValue(":userId", $userId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            console.log($e);
        }
    }
}

class UserDataUsedInAccountByPreDeactivateUserProcess extends UserDataUsedInAccount implements ISelect
{
    public function selectCommand() {
        $pdo    = $this->pdo;
        $userId = $this->userId;
        try {
            $stmt   = $pdo->prepare("SELECT password FROM user WHERE user_id = :userId");
            $stmt->bindValue(":userId", $userId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            console.log($e);
        }
    }
}

/**
* deleteで使用
*/
class UserDataUsedInDelete extends DBConnection implements ISelect, IDelete, ISetHttpGet, ISetSession
{
    private $userId;
    private $postId;
    private $tags;

    public function setHttpGet ($get) {
        $this->postId = $get['postID'];
    }

    public function setSession ($session) {
        $this->userId = $session['id'];
    }

    public function selectCommand () {
        $sqlCommand = <<< 'SQL'
            SELECT posts.post_id,
                   posts.title,
                   posts.post,
                   posts.created_at,
                   posts.updated_at,
                   GROUP_CONCAT(tags.tag_name SEPARATOR ' ') AS tags,
                   user_uploaded_posts.user_id AS user_id
            FROM posts
            LEFT JOIN post_tags ON posts.post_id = post_tags.post_id AND posts.post_id = :id
            LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
            LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
            GROUP BY posts.post_id
            HAVING posts.post_id = :id
            SQL;
        $postId = $this->postId;
        $pdo    = $this->pdo;

        try{
            $stmt   = $pdo->prepare($sqlCommand);
            $stmt->bindValue(":id", $postId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch();
            $result['tags'] === null ? $this->tags = [] : $this->tags = explode(' ', $result['tags']);
            return $result;
        } catch (PDOException $e) {
            console.log($e);
        }
    }

    public function deleteCommand () {
        $sqlCommand = <<< 'SQL'
            DELETE FROM posts WHERE post_id = :post_id
            SQL;
        $userId = $this->userId;
        $postId = $this->postId;
        $tags   = $this->tags;
        $pdo    = $this->pdo;

        try{
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(":post_id", $postId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        $sqlCommand = <<< 'SQL'
            DELETE FROM user_uploaded_posts
            WHERE user_id = :user_id AND post_id = :post_id
            SQL;

        try{
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
            $stmt->bindValue(":post_id", $postId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        for ($i = 0; $i < count($tags); ++$i) {
            $sqlCommand = <<< 'SQL'
                CALL sp_remove_tags(:tag_name, :post_id)
                SQL;
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':tag_name', $tags[$i], PDO::PARAM_STR);
            $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

/**
* signupで使用
*/
class UserDataUsedInSignUp extends DBConnection implements ISelect, IInsert, IsetHttpPost
{
    private $username;
    private $email;
    private $password;

    public function setHttpPost ($post) {
        $this->username = $post['username'];
        $this->email    = $post['email'];
        $this->password = $post['password'];
    }

    public function setUrlToken ($urlToken) {
        $this->urlToken = $urlToken;
    }

    public function selectCommand () {
        $pdo        = $this->pdo;
        $email      = $this->email;
        $sqlCommand = <<< 'SQL'
            SELECT email FROM user WHERE email = :email
            SQL;
        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            console.log($e);
        }
    }

    public function insertCommand () {
        $pdo      = $this->pdo;
        $username = $this->username;
        $email    = $this->email;
        $password = $this->password;
        $urlToken = $this->urlToken;

        $sqlCommand = <<< 'SQL'
            INSERT INTO temporary_users(name, email, password, url_token)
            VALUES(:name, :email, :password, :url_token)
            SQL;
        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':name', $username, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $stmt->bindValue(':url_token', $urlToken, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }
    }
}

/**
* registrationで使用
*/
class UserDataUsedInRegistration extends DBConnection implements ISelect, IInsert, IDelete, IsetHttpGet
{
    private $name;
    private $email;
    private $password;
    private $urlToken;
    private $deadlineDate;

    public function __construct () {
        parent::__construct();
        //24時間前の時刻を取得
        $this->deadlineDate = date("Y/m/d H:i:s", strtotime('-1 day'));
    }

    public function setHttpGet ($get) {
        $this->urlToken = $get['url_token'];
    }

    public function getDeadlineDate () {
        return $this->deadlineDate;
    }

    public function selectCommand () {
        $pdo        = $this->pdo;
        $urlToken   = $this->urlToken;
        $sqlCommand = <<< 'SQL'
            SELECT * FROM temporary_users WHERE url_token = :url_token
            SQL;
        $stmt = $pdo->prepare($sqlCommand);
        $stmt->bindValue(':url_token', $urlToken, PDO::PARAM_STR);
        $stmt->execute();
        $temporaryUser  = $stmt->fetch();
        $this->name     = $temporaryUser['name'];
        $this->email    = $temporaryUser['email'];
        $this->password = $temporaryUser['password'];
        return $temporaryUser;
    }

    public function insertCommand () {
        $pdo      = $this->pdo;
        $name     = $this->name;
        $email    = $this->email;
        $password = $this->password;
        $urlToken = $this->urlToken;

        //Userテーブルに登録
        $sqlCommand = <<< 'SQL'
            INSERT INTO user(name, email, password) VALUES(:name, :email, :password)
            SQL;

        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        //仮ユーザーテーブルから該当するレコードを削除
        $sqlCommand = <<< 'SQL'
            DELETE FROM temporary_users WHERE url_token = :url_token
            SQL;

        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':url_token', $urlToken, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }
    }

    public function deleteCommand () {
        $pdo          = $this->pdo;
        $deadlineDate = $this->deadlineDate;
        $sqlCommand   = <<< 'SQL'
            DELETE FROM temporary_users WHERE created_at < :deadlineDate
            SQL;

        try {
            $stmt = $pdo->prepare($sqlCommand);
            $stmt->bindValue(':deadlineDate', $deadlineDate, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }
    }
}
