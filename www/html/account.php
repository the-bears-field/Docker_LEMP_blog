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

    //ユーザー名変更
    if (isset($_POST['username']) && isset($_POST['username']) !== $_SESSION['name']) {
        $userName = $_POST['username'];

        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("UPDATE user SET name = :userName WHERE user_id = :userId");
            $stmt->bindValue(':userName', $userName, PDO::PARAM_STR);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $pdo  = null;
            $_SESSION['name'] = $userName;
        } catch (PDOException $e) {

        }
    }

    //email変更
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email    = $_POST['email'];
        $password = $_POST['password'];

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
                $stmt = $pdo->prepare("UPDATE user SET email = :email WHERE user_id = :userId");
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $pdo  = null;
                $_SESSION['email'] = $email;

            } catch (PDOException $e) {
                console.log($e);
            }
        }

    }

    //パスワード変更
    if (isset($_POST['current-password'])
     && isset($_POST['new-password'])
     && isset($_POST['password-confirmation'])
     && $_POST['new-password'] === $_POST['password-confirmation']
    ) {
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
                $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE user_id = :userId");
                $stmt->bindValue(':password', password_hash($newPassword, PASSWORD_DEFAULT), PDO::PARAM_STR);
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

        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = :userId");
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        //記事の削除に伴い、タグの関連付けも削除
        $sql = "DELETE pt FROM post_tags AS pt
                LEFT JOIN user_uploaded_posts AS up ON pt.post_id = up.post_id
                WHERE up.user_id = :userId";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

        //削除対象ユーザーが投稿した記事を全て削除する
        $sql = "DELETE p FROM posts AS p
                LEFT JOIN user_uploaded_posts AS up ON p.post_id = up.post_id
                WHERE up.user_id = :userId";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            console.log($e);
        }

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
    $pdo  = (new DatabaseConnection())->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = :id");
    $stmt->bindValue(":id", $_SESSION['id'], PDO::PARAM_STR);
    $stmt->execute();
    $stmt = $stmt->fetch();
    $pdo  =  NULL;
}

include_once __DIR__. '/views/accountView.php';