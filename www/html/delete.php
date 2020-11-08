<?
require_once __DIR__ . '/authenticateFunctions.php';
require_once __DIR__ . '/databaseConnection.php';
require_once __DIR__ . '/vendor/autoload.php';
//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

requireLoginedSession();

$token = sha1(uniqid(random_bytes(16), true));
$_SESSION['token'] = $token;

if (isset($_GET['postID'])) {
    $userData = new UserDataUsedInDelete;
    $result   = $userData->selectCommand();
    $result['tags'] === null ? $tags = [] : $tags = explode(' ', $result['tags']);
}

if(!isset($_GET['postID']) || $result['user_id'] !== $_SESSION['id']){
    header('location: /');
}

//記事の削除
if (isset($_POST['deleting'])) {
    if (!isset($_POST['token']) || $_POST['token'] === $_SESSION['token']) {
        die('不正なアクセスが行われました');
    }
    $userData->deleteCommand();
    header('location: /');
}

if (isset($_POST['cancel'])){
    header('location: /');
}

include_once __DIR__. '/views/deleteView.php';