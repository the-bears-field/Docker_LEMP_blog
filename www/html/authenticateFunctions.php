<?php
function requireUnloginedSession() {
    //セッション開始
    @session_start();
    //セッションID更新
    @session_regenerate_id();
    //ログインしていれば /index.php に遷移
    if (isset($_SESSION["name"])) {
        header("Location: /");
        exit;
    }
}

function requireLoginedSession() {
    //セッション開始
    @session_start();
    //セッションID更新
    @session_regenerate_id();
    //ログインしていなければ /login.php に移動
    if (!isset($_SESSION["name"])) {
        header("Location: /login.php");
        exit;
    }
}

//CSRFトークンの検証
//@param string $token
//@return bool 検証結果
function validateToken($token){
    return $token === generateToken();
}

//htmlspecialcharsのラッパー関数
//@param string $str
//@return string
function htmlSpecialCharsWrapper($str) {
    return htmlspecialchars($str, ENT_QUOTES,"UTF-8");
}