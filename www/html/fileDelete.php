<?php
date_default_timezone_set('Asia/Tokyo');
header("Content-type: text/json; charset=UTF-8");

require_once __DIR__ . '/authenticateFunctions.php';
@session_start();

//整数かどうかを正規表現を用いて判別する関数
//https://www.php.net/manual/ja/function.is-int.php
function isInteger($input){
    return preg_match('@^[-]?[0-9]+$@',$input) === 1;
}

if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']){
    echo $_POST['token']. "\n";
    echo $_SESSION['token']. "\n";
    die("不正なアクセスが行われました");
}

$loggedInUser = sha1($_SESSION['id']);
$year         = $_POST['year'];
$imageID      = $_POST['imageID'];
strlen($_POST['month']) === 1 ? $month = '0'. $_POST['month'] : $month = $_POST['month'];

if(!isInteger($year) || !isInteger($month)){
    exit();
}

$imagePath = glob('pictures/'. $loggedInUser. '/'. $year. $month. '/'. $imageID. '.*', GLOB_NOSORT);

if($imagePath){
    unlink($imagePath[0]);
    echo json_encode('success');
} else {
    exit();
}
