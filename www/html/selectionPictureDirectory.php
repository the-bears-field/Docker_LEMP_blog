<?php
date_default_timezone_set('Asia/Tokyo');
header("Content-type: text/json; charset=UTF-8");

require_once __DIR__ . '/authenticateFunctions.php';
require_once __DIR__ . "/vendor/autoload.php";

@session_start();

//整数かどうかを正規表現を用いて判別する関数
//https://www.php.net/manual/ja/function.is-int.php
function isInteger($input){
    return preg_match('@^[-]?[0-9]+$@',$input) === 1;
}

if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']){
    die($_POST['token']. "\n". $_SESSION['token']);
}

$token = $_SESSION['token'];

if($_POST['process'] === 'yearsChange' && isInteger($_POST['year'])){
    $loginnedUser            = sha1($_SESSION['id']);
    $selectedYear            = $_POST['year'];
    $selectedYearDirectories = glob('pictures/'. $loginnedUser. '/'. $selectedYear. '*', GLOB_NOSORT);

    for($i = 0; $i < count($selectedYearDirectories); $i++){
        if(substr($selectedYearDirectories[$i], -2, -1) === '0'){
            $selectedYearDirectories[$i] = substr($selectedYearDirectories[$i], -1);
        } else {
            $selectedYearDirectories[$i] = substr($selectedYearDirectories[$i], -2);
        }
    }
    sort($selectedYearDirectories);

    for($i = 0; $i < count($selectedYearDirectories); $i++){
        $result[$i] = '<option value="'. $selectedYearDirectories[$i]. '">'. $selectedYearDirectories[$i]. '月</option>';
    }
    echo json_encode($result);
    exit();
}

if($_POST['process'] === 'selectionYearAndMonth' && isInteger($_POST['year']) && isInteger($_POST['month'])){
    $loginnedUser  = sha1($_SESSION['id']);
    $selectedYear  = $_POST['year'];
    $selectedMonth = $_POST['month'];

    if(strlen($selectedMonth) === 1){
        $selectedMonth = '0'. $selectedMonth;
    }

    $selectedImageDirectory = 'pictures/'. $loginnedUser. '/'. $selectedYear. $selectedMonth;

    //ファイル一式取得
    $files = glob($selectedImageDirectory. '/*', GLOB_NOSORT);

    //更新日が新しい順にソート
    $sort_by_lastmod = function($a, $b){
        return filemtime($b) - filemtime($a);
    };
    usort($files, $sort_by_lastmod);

    for($i = 0; $i < count($files); $i++){
        $files[$i] = '<div class="uploaded-images__item">'.
                     '<div class="uploaded-images__img-wrapper">'.
                     '<img src="'. $files[$i]. '"class="uploaded-images__img" imageID="'. substr(htmlspecialchars($files[$i]), 57, 40). '" token="'. $token. '">'.
                     '</div>'.
                     '<div class="uploaded-images__delete" name="deleting">'.
                     '<i class="far fa-trash-alt"></i>'.
                     '</div>'.
                     '</div>';
    }

    $result = [
        'files' => $files,
        'year'  => $selectedYear,
        'month' => $selectedMonth
    ];

    echo json_encode($result);
    exit();
}