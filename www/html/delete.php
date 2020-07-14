<?
require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";
require_once __DIR__ . "/vendor/autoload.php";
//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

requireLoginedSession();

$token = sha1(uniqid(random_bytes(16), true));
$_SESSION["token"] = $token;
$userID            = $_SESSION['id'];

if (isset($_GET["postID"])) {
    $sqlCommand = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ' ') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                   LEFT JOIN post_tags ON posts.post_id = post_tags.post_id AND posts.post_id = :id
                   LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                   LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                   GROUP BY posts.post_id
                   HAVING posts.post_id = :id";
    $postID = $_GET["postID"];
    $pdo    = (new DatabaseConnection())->getPdo();
    $stmt   = $pdo->prepare($sqlCommand);
    $stmt   ->bindValue(":id", $postID, PDO::PARAM_STR);
    $stmt   ->execute();
    $result = $stmt->fetch();
    $result['tags'] === null ? $tags = [] : $tags = explode(' ', $result['tags']);
    $stmt   = null;
    $pdo    = null;
}

if(!isset($_GET['postID']) || $result['user_id'] !== $_SESSION['id']){
    header('location: /');
}

//記事の削除
if (isset($_POST["deleting"])) {
    if (!isset($_POST["token"]) || $_POST["token"] === $_SESSION["token"]) {
        die("不正なアクセスが行われました");
    } else {
        try{
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = :post_id");
            $stmt->bindValue(":post_id", $postID, PDO::PARAM_INT);
            $stmt->execute();
            $stmt = $pdo->prepare("DELETE FROM user_uploaded_posts WHERE user_id = :user_id AND post_id = :post_id");
            $stmt->bindValue(":user_id", $userID, PDO::PARAM_INT);
            $stmt->bindValue(":post_id", $postID, PDO::PARAM_INT);
            $stmt->execute();

            for ($i = 0; $i < count($tags); ++$i) {
                $stmt = $pdo->prepare("CALL sp_remove_tags(:tag_name, :post_id)");
                $stmt->bindValue(":tag_name", $tags[$i], PDO::PARAM_STR);
                $stmt->bindValue(":post_id", $postID, PDO::PARAM_INT);
                $stmt->execute();
            }
            $pdo  = null;
            header("Location: /index.php");
        } catch (PDOException $e) {
            $errorMessage = "データベースエラー";
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            echo $e->getMessage();
            die();
        }
    }
}

if (isset($_POST["cancel"])){
    header("Location: /index.php");
}

include_once __DIR__. '/views/deleteView.php';