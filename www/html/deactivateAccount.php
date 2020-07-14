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

if(isset($_SESSION['name']) && isset($_POST['password'])){
    $password = $_POST['password'];
    $userId   = $_SESSION['id'];
    try {
        $pdo  = (new DatabaseConnection())->getPdo();
        $stmt = $pdo->prepare("SELECT password FROM user WHERE user_id = :userId");
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $correntHashedPassword = $stmt->fetchColumn();
        $pdo  = null;
        $stmt = null;
    } catch (PDOException $e){
        $correntHashedPassword = null;
    }

    if(password_verify($password, $correntHashedPassword)){
        $token               = sha1(uniqid(random_bytes(16), true));
        $_SESSION['token']   = $token;

        $messageBoxContent   = '<p class="message-box__text">アカウント削除処理を実行します。<br>この処理は取り消すことができません。</p>'. "\n".
                                '<form id="deactivate-account" name="deactivate-account" method="post" action="account.php">'. "\n".
                                    '<input type="hidden" name="token" value="'. $token. '">'. "\n".
                                    '<input type="hidden" name="deactivate-account" value="">'. "\n".
                                '</form>'. "\n".
                                '<div class="message-box__send-links flex-direction-row">'. "\n".
                                    '<button class="button button--enabled cancel">キャンセル</button>'. "\n".
                                    '<button class="button button--enabled button--warning" form="deactivate-account" name="sending">削除</button>'. "\n".
                                '</div>';
        $result    = [
            'accept'            => TRUE,
            'messageBoxContent' => $messageBoxContent
        ];
    } else {
        $result = [
            'accept' => FALSE
        ];
    }

    echo json_encode($result);
}