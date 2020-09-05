<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// password_verfy()はphp 5.5.0以降の関数のため、バージョンが古くて使えない場合に使用
require "password.php";
require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";
require_once __DIR__. '/vendor/autoload.php';
// エラーメッセージ用日本語言語ファイルを読み込む場合（setLanguageメソッドの指定も必要）
require 'vendor/phpmailer/phpmailer/language/phpmailer.lang-ja.php';

// セッション開始
requireUnloginedSession();

// .env読込
$dotenv = (Dotenv\Dotenv::createImmutable(__DIR__))->load();

// エラーメッセージ、登録完了メッセージの初期化
$errorMessages  = [];

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
     && mb_strlen($_POST["password"]) >= 8)
     {
        $username          = $_POST["username"];
        $email             = $_POST["email"];
        $password          = $_POST["password"];
        $urlToken          = sha1(uniqid(random_bytes(16), true));
        $authenticationURL = 'http://'. $_SERVER['HTTP_HOST']. '/registration.php?url_token='. $urlToken;
        $mailSubject       = 'メールアドレスのご確認。';
        $mailMessage       = '<p>'. $username. '様'. '</p>'.
                             '<p>本ブログにご登録頂きありがとうございます。<br>'.
                             '24時間以内に下記アドレスへアクセスし、登録を完了して下さい。</p>'.
                             '<a href="'. $authenticationURL. '">'. $authenticationURL. '</a>';

        //言語、内部エンコーディングを指定
        mb_language("japanese");
        mb_internal_encoding("UTF-8");

        // インスタンスを生成（引数に true を指定して例外 Exception を有効に）
        $mail = new PHPMailer(true);

        //日本語用設定
        $mail->CharSet  = "iso-2022-jp";
        $mail->Encoding = "7bit";

        //エラーメッセージ用言語ファイルを使用する場合に指定
        $mail->setLanguage('ja', 'vendor/phpmailer/phpmailer/language/');

        try {
            //サーバの設定
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;       // デバグの出力を有効に（テスト環境での検証用）
            $mail->isSMTP();                                // SMTP を使用
            $mail->Host       = $_ENV['MAIL_HOST'];         // SMTP サーバーを指定
            $mail->SMTPAuth   = true;                       // SMTP authentication を有効に
            $mail->Username   = $_ENV['MAIL_USERNAME'];     // SMTP ユーザ名
            $mail->Password   = $_ENV['MAIL_PASSWORD'];     // SMTP パスワード
            $mail->Port       = $_ENV['MAIL_PORT'];         // TCP ポートを指定

            //受信者設定
            //※名前などに日本語を使う場合は文字エンコーディングを変換
            //差出人アドレス, 差出人名
            $mail->setFrom('no-reply@example.com', mb_encode_mimeheader($mailSubject));
            //受信者アドレス, 受信者名（受信者名はオプション）
            $mail->addAddress('test@example.com', mb_encode_mimeheader("受信者名"));
            //返信用アドレス（差出人以外に別途指定する場合）
            $mail->addReplyTo('info@example.com', mb_encode_mimeheader("お問い合わせ"));

            //コンテンツ設定
            $mail->isHTML(true);   // HTML形式を指定
            //メール表題（文字エンコーディングを変換）
            $mail->Subject = mb_encode_mimeheader($mailSubject);
            //HTML形式の本文（文字エンコーディングを変換）
            $mail->Body    = mb_convert_encoding($mailMessage,"JIS","UTF-8");
            // //テキスト形式の本文（文字エンコーディングを変換）
            // $mail->AltBody = mb_convert_encoding('テキストメッセージ',"JIS","UTF-8");

            $mail->send();  //送信
        } catch (Exception $e) {
            //エラー（例外：Exception）が発生した場合
            //エラー内容を参照（デバッグ時のみ表示）
            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            $errorMessages[] = '重大なエラーが発生しています。 運営にお問い合わせ願います。';
            include_once __DIR__. '/views/signupView.php';
            return false;
        }

        try {
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare("INSERT INTO temporary_users(name, email, password, url_token) VALUES(:name, :email, :password, :url_token)");
            $stmt->bindValue(":name", $username, PDO::PARAM_STR);
            $stmt->bindValue(":email", $email, PDO::PARAM_STR);
            $stmt->bindValue(":password", password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $stmt->bindValue(":url_token", $urlToken, PDO::PARAM_STR);
            $stmt->execute();
            $pdo  =  null;
        } catch (PDOException $e) {
            $errorMessages[] = "データベースエラー";
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            // echo $e->getMessage();
            include_once __DIR__. '/views/signupView.php';
            return false;
        }
    }
}

include_once __DIR__. '/views/signupView.php';