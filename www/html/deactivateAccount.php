<?php
header("Content-type: text/json; charset=UTF-8");

require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";
require_once __DIR__ . "/vendor/autoload.php";

requireLoginedSession();

//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

if(!isset($_SESSION['name']) && !isset($_POST['password'])){
    exit();
}

$password = $_POST['password'];
$userData = new UserDataUsedInAccount;
$correntHashedPassword = $userData->selectCommand();

if(!password_verify($password, $correntHashedPassword)){
    $result = [
        'accept' => FALSE
    ];
    echo json_encode($result);
    exit();
}

$token               = sha1(uniqid(random_bytes(16), true));
$_SESSION['token']   = $token;

$messageBoxContent   = <<< 'HTML'
    <p class="message-box__text">アカウント削除処理を実行します。<br>この処理は取り消すことができません。</p>
    <form id="deactivate-account" name="deactivate-account" method="post" action="account.php">
        <input type="hidden" name="token" value="'. $token. '">
        <input type="hidden" name="deactivate-account" value="">
    </form>
    <div class="message-box__send-links flex-direction-row">
        <button class="button button--enabled cancel">キャンセル</button>
        <button class="button button--enabled button--warning" form="deactivate-account" name="sending">削除</button>
    </div>
    HTML;
$result    = [
    'accept'            => TRUE,
    'messageBoxContent' => $messageBoxContent
];

echo json_encode($result);
