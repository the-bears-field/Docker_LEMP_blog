<?php
// header("Content-type: text/json; charset=UTF-8");

@session_start();
@session_regenerate_id();

// if (!isset($_POST['process']) || $_POST['process'] !== 'logout') {
//     header('location: /');
//     exit();
// }

if (isset($_SESSION["name"])) {
    $errorMessage = "ログアウトしました";
} else {
    $errorMessage = "セッションがタイムアウトしました。";
}

// セッションの変数のクリア
$_SESSION = [];

// セッションクリア
@session_destroy();

header('location: /');
exit();
