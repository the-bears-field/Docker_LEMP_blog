<?php
// password_verfy()はphp 5.5.0以降の関数のため、バージョンが古くて使えない場合に使用
require_once "password.php";
require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";

requireUnloginedSession();

//エラーメッセージの初期化
$errorMessage = "";

//ログインボタンが押された時の処理
if (isset($_POST['login'])) {
    //Emailの入力チェック
    if (is_null($_POST['email'])) {
        $errorMessage = "Emailが未入力です。";
    }

    if (is_null($_POST['password'])) {
        $errorMessage = "パスワードが未入力です。";
    }

    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];

        //エラー処理
        try{
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare('SELECT * FROM user WHERE email = :email');
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $pdo  =  NULL;

            $password = $_POST['password'];

            if ($userTableRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $userTableRow['password'])) {
                    // 現在のセッションIDを新しく生成したものと置き換える
                    session_regenerate_id(true);
                    $_SESSION['id']    = $userTableRow['user_id'];
                    $_SESSION['name']  = $userTableRow['name'];
                    $_SESSION['email'] = $userTableRow['email'];
                    header('Location: /');
                    exit();                             //処理終了
                } else {
                    //認証失敗
                    $errorMessage = "Emailあるいはパスワードに誤りがあります。";
                    include_once __DIR__. '/views/loginView.php';
                    exit();
                }
            } else {
                // 4. 認証成功なら、セッションIDを新規に発行する
                // 該当データなし
                $errorMessage = "Emailあるいはパスワードに誤りがあります。";
            }
        } catch(PDOException $e) {
            $errorMessage = 'データベースエラー';
            //$errorMessage = $stmt;
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            //echo $e->getMessage();
            die();
        }
    }
}

include_once __DIR__. '/views/loginView.php';