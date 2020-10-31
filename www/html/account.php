<?php
require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";
require_once __DIR__ . "/vendor/autoload.php";

requireLoginedSession();

//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

if (!isset($_POST['sending'])) {
    $token = sha1(uniqid(random_bytes(16), true));
    $_SESSION['token'] = $token;
}

if (isset($_POST['sending'])) {
    if (!isset($_POST["token"]) || $_POST["token"] !== $_SESSION["token"]) {
        die("不正なアクセスが行われました");
    }

    $userId = $_SESSION['id'];
    $updatedAt   = (new Datetime())->format('Y-m-d H:i:s');

    //ユーザー名変更
    if (isset($_POST['username']) && isset($_POST['username']) !== $_SESSION['name']) {
        $post    = $_POST;
        $session = $_SESSION;

        $userData = new UserDataUsedInAccountByEditUserNameProcess;
        $userData->setSession($session);
        $userData->setHttpPost($post);
        $userData->updateCommand();
    }

    //email変更
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $post    = $_POST;
        $session = $_SESSION;

        $userData = new UserDataUsedInAccountByEditEmailProcess;
        $userData->setSession($session);
        $userData->setHttpPost($post);
        $userData->updateCommand();
    }

    //パスワード変更
    if (isset($_POST['current-password'])
     && isset($_POST['new-password'])
     && isset($_POST['password-confirmation'])
     && $_POST['new-password'] === $_POST['password-confirmation']) {
        $password    = $_POST['current-password'];
        $newPassword = $_POST['new-password'];

        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("SELECT password FROM user WHERE user_id = :userId");
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $correntPassword = $stmt->fetchColumn();
        } catch (PDOException $e) {
            console.log($e);
        }

        if (password_verify($password, $correntPassword)) {
            try {
                $stmt = $pdo->prepare("UPDATE user SET password = :password, updated_at = :updated_at WHERE user_id = :userId");
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

    //アカウント削除処理
    if(isset($_POST['deactivate-account'])){
        //削除対象ユーザーがアップロードした画像、ディレクトリも削除
        $loggedInUser = sha1($_SESSION['id']);

        if (file_exists('pictures/'. $loggedInUser)) {
            //削除対象ユーザーのディレクトリから年月ディレクトリ一覧を取得し、それぞれの中身を削除。
            $loggedInUserDirectories = glob('pictures/'. $loggedInUser. '/*');

            foreach ($loggedInUserDirectories as $loggedInUserDirectory) {
                $uploadedImages = glob($loggedInUserDirectory. '/{*,.[!.]*,..?*}', GLOB_BRACE);
                //ディレクトリの内容物が存在すれば全て削除
                if ($uploadedImages) {
                    foreach ($uploadedImages as $uploadedImage) {
                        unlink($uploadedImage);
                    }
                }
                //年月ディレクトリ削除
                rmdir($loggedInUserDirectory);
            }

            //削除対象ユーザーのディレクトリ削除
            rmdir('pictures/'. $loggedInUser);
        }

        $session = $_SESSION;

        $userData = new UserDataUsedInAccountByDeactivateUserProcess;
        $userData->setSession($session);
        $userData->deleteCommand();

        //ログアウト実行(セッションのリセット)
        $_SESSION = [];
        @session_destroy();
        header('Location: /');
        exit();
    }
}

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['id'])) {
    $userData = new UserDataUsedInAccountByNormalProcess;
    $userData->setSession($_SESSION);
    $stmt     = $userData->selectCommand();
}

include_once __DIR__. '/views/accountView.php';