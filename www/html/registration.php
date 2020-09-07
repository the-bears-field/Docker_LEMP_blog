<?php
require_once __DIR__ . "/databaseConnection.php";

//現時刻を取得

//24時間前の時刻を取得
$registrationDeadlineDate = date("Y/m/d H:i:s", strtotime('-1 day'));

if(!isset($_GET['url_token'])){
    header('Location: /');
    return false;
}
$urlToken = $_GET['url_token'];
//DB接続
//仮ユーザーテーブルからURLトークンを検索
$pdo  = (new DatabaseConnection())->getPdo();
$stmt = $pdo->prepare("SELECT * FROM temporary_users WHERE url_token = :url_token");
$stmt->bindValue(":url_token", $urlToken, PDO::PARAM_STR);
$stmt->execute();
$temporaryUser = $stmt->fetch();

if(!$temporaryUser){
    $message = '仮登録から24時間以上経過している、もしくは仮登録されていません。再度登録を実行して下さい。';
    include_once __DIR__. '/views/registrationView.php';
    return false;
}

$temporaryRegistrationDate = $temporaryUser['created_at'];

if($temporaryRegistrationDate < $registrationDeadlineDate){
    //registration
    //Userテーブルに登録
    $name     = $temporaryUser['name'];
    $email    = $temporaryUser['email'];
    $password = $temporaryUser['password'];
    $stmt = $pdo->prepare("INSERT INTO user(name, email, password) VALUES(:name, :email, :password)");
    $stmt->bindValue(":name", $name, PDO::PARAM_STR);
    $stmt->bindValue(":email", $email, PDO::PARAM_STR);
    $stmt->bindValue(":password", $password, PDO::PARAM_STR);
    $stmt->execute();

    //仮ユーザーテーブルから該当するレコードを削除
    $stmt = $pdo->prepare("DELETE FROM temporary_users WHERE url_token = :url_token");
    $stmt->bindValue(":url_token", $urlToken, PDO::PARAM_STR);
    $stmt->execute();

    $message = '会員登録が完了しました。ログインページからログインしてください。';
} else {
    //rejection
    $stmt = $pdo->prepare("DELETE FROM temporary_users WHERE created_at < :registrationDeadlineDate");
    $stmt->bindValue(":registrationDeadlineDate", $registrationDeadlineDate, PDO::PARAM_STR);
    $stmt->execute();

    $message = '仮登録から24時間以上経過している、もしくは仮登録されていません。再度登録を実行して下さい。';
}

$pdo  =  null;

include_once __DIR__. '/views/registrationView.php';