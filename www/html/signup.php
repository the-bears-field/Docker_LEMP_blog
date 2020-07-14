<?php
// password_verfy()はphp 5.5.0以降の関数のため、バージョンが古くて使えない場合に使用
require "password.php";
require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";

//セッション開始
requireUnloginedSession();

// エラーメッセージ、登録完了メッセージの初期化
$errorMessages  = [];
$signUpMessage = "";

//新規登録ボタンが押された時の処理
if (isset($_POST['signup'])) {
    // 1. ユーザIDの入力チェック
    if (empty($_POST['username'])) {
        $errorMessages[] = "ユーザー名が未入力です。";
    }

    if (empty($_POST["email"]) || empty($_POST["email2"])) {
        $errorMessages[] = "メールアドレスが未入力です。";
    } else if ($_POST["email"] != $_POST["email2"]) {
        $errorMessages[] = "メールアドレスに誤りがあります。";
    }
    
    if (empty($_POST["password"]) || empty($_POST["password2"])) {
        $errorMessages[] = "パスワードが未入力です。";
    } else if ($_POST["password"] != $_POST["password2"]) {
        $errorMessages[] = "パスワードに誤りがあります。";
    } else if (mb_strlen($_POST["password"]) < 8) {
        $errorMessages[] = "パスワードの文字数が短すぎます。";
    }

    if ($_POST["username"]
     && $_POST["password"]
     && $_POST["password2"]
     && $_POST["password"] === $_POST["password2"]
     && mb_strlen($_POST["password"]) >= 8){
        // 入力したユーザ名とパスワードを変数に格納
        $username = $_POST["username"];
        $email    = $_POST["email"];
        $password = $_POST["password"];

        // 3. エラー処理
        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("INSERT INTO user(name, email, password) VALUES(:name, :email, :password)");
            $stmt->bindValue(":name", $username, PDO::PARAM_STR);
            $stmt->bindValue(":email", $email, PDO::PARAM_STR);
            $stmt->bindValue(":password", password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $stmt->execute();

            // 登録した(DB側でauto_incrementした)IDを$useridに入れる
            $userid = $pdo->lastinsertid();
            $pdo    =  null;

            // ログイン時に使用するIDとパスワード
            $signUpMessage = "登録が完了しました。あなたの登録IDは ". $userid. " です。パスワードは ". $password. " です。";
        } catch (PDOException $e) {
            $errorMessage = "データベースエラー";
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            echo $e->getMessage();
        }
    }

}

include_once __DIR__. '/views/signupView.php';