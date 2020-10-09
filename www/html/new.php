<?php
require_once __DIR__. '/authenticateFunctions.php';
require_once __DIR__. '/databaseConnection.php';

requireLoginedSession();

date_default_timezone_set('Asia/Tokyo');

if (!isset($_POST['posting'])) {
    $token = sha1(uniqid(random_bytes(16), true));
    $_SESSION['token'] = $token;
}

if (isset($_POST['posting'])) {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die('不正なアクセスが行われました');
    }

    $title  = $_POST['title'];
    $post   = $_POST['post'];
    $tags   = $_POST['tags'];
    $userId = $_SESSION['id'];

    $InsertPost = new InsertPostAndTags();
    $InsertPost->setTitle($title);
    $InsertPost->setPost($post);
    $InsertPost->setTags($tags);
    $InsertPost->setUserId($userId);
    $InsertPost->insertCommand();
    unset($_SESSION['token']);
    header('Location: /index.php');
}

//画像ソート関係
$nowYearAndMonth          = (new Datetime)->format('Ym');
$nowYear                  = substr($nowYearAndMonth, 0, 4);
$nowMonth                 = substr($nowYearAndMonth, -2);
$loginnedUserDirectryName = sha1($_SESSION['id']);

//ログインユーザのディレクトリがなければ作成
if(!is_dir('pictures/'.$loginnedUserDirectryName)){
    mkdir('pictures/'.$loginnedUserDirectryName);
}
//対象となる年月のディレクトリがなければ作成
if(!is_dir('pictures/'.$loginnedUserDirectryName. '/'. $nowYearAndMonth)){
    mkdir('pictures/'.$loginnedUserDirectryName. '/'. $nowYearAndMonth);
}
//------------------------------------------------------------------------------------------------

//現時点の年の画像ディレクトリ一式を取得
$selectedYearDirectories = glob('pictures/'. $loginnedUserDirectryName. '/'. $nowYear. '*', GLOB_NOSORT);

//月に該当する文字列だけを抽出
//文頭が0の場合は0を除外
for($i = 0; $i < count($selectedYearDirectories); $i++){
    if(substr($selectedYearDirectories[$i], -2, -1) === '0'){
        $selectedYearDirectories[$i] = substr($selectedYearDirectories[$i], -1);
    } else {
        $selectedYearDirectories[$i] = substr($selectedYearDirectories[$i], -2);
    }
}

sort($selectedYearDirectories);
//------------------------------------------------------------------------------------------------

//picturesディレクトリの直下のディレクトリ一式取得
$picturesDirectories = glob('pictures/'. $loginnedUserDirectryName. '/*', GLOB_NOSORT);

//年に該当する文字列だけを抽出
for($i = 0; $i < count($picturesDirectories); $i++){
    $picturesDirectories[$i] = substr($picturesDirectories[$i], -6, -2);
}
//重複した年を削除
$picturesDirectories = array_unique($picturesDirectories);

sort($picturesDirectories);
//------------------------------------------------------------------------------------------------

//ファイル一式取得
$files = glob('pictures/'.$loginnedUserDirectryName. '/'. $nowYearAndMonth.'/*', GLOB_NOSORT);

//更新日が新しい順にソート
$sort_by_lastmod = function($a, $b){
    return filemtime($b) - filemtime($a);
};
usort($files, $sort_by_lastmod);

include_once __DIR__. '/views/newView.php';