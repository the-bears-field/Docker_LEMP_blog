<?php
require_once __DIR__ . "/databaseConnection.php";

if(!isset($_GET['url_token'])){
    header('Location: /');
    return false;
}

$userData = new UserDataUsedInRegistration;
$temporaryUser = $userData->selectCommand();

if(!$temporaryUser){
    $message = '仮登録から24時間以上経過している、もしくは仮登録されていません。再度登録を実行して下さい。';
    include_once __DIR__. '/views/registrationView.php';
    return false;
}

$deadlineDate              = $userData->getDeadlineDate();
$temporaryRegistrationDate = $temporaryUser['created_at'];

if($temporaryRegistrationDate < $deadlineDate){
    //registration
    $userData->insertCommand();
    $message = '会員登録が完了しました。ログインページからログインしてください。';
} else {
    //rejection
    $userData->deleteCommand();
    $message = '仮登録から24時間以上経過している、もしくは仮登録されていません。再度登録を実行して下さい。';
}

include_once __DIR__. '/views/registrationView.php';