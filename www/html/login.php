<?php
// password_verfy()はphp 5.5.0以降の関数のため、バージョンが古くて使えない場合に使用
require_once "password.php";
require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";

requireUnloginedSession();

//エラーメッセージの初期化
$errorMessage = "";

//ログインボタンが押されなかった場合の処理
if (!isset($_POST['login'])) {
    include_once __DIR__. '/views/loginView.php';
    exit();
}

//ログインボタンが押された場合の処理
//Emailの入力チェック
if (is_null($_POST['email'])) {
    $errorMessage = "Emailが未入力です。";
}

if (is_null($_POST['password'])) {
    $errorMessage = "パスワードが未入力です。";
}

if (!isset($_POST['email']) || !isset($_POST['password'])) {
    include_once __DIR__. '/views/loginView.php';
    exit();
}

$post     = $_POST;
$userData = new UserDataUsedInLogin;
$userData->setHttpPost($post);
$userData = $userData->selectCommand();

$password = $_POST['password'];

if ($userData && password_verify($password, $userData['password'])) {
    session_regenerate_id(true);
    $_SESSION['id']    = $userData['user_id'];
    $_SESSION['name']  = $userData['name'];
    $_SESSION['email'] = $userData['email'];
    header('Location: /');
    exit();
} else {
    //認証失敗
    $errorMessage = "Emailあるいはパスワードに誤りがあります。";
    include_once __DIR__. '/views/loginView.php';
    exit();
}

include_once __DIR__. '/views/loginView.php';