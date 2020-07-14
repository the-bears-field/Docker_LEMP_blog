<?php
date_default_timezone_set('Asia/Tokyo');
header("Content-type: text/json; charset=UTF-8");

require_once __DIR__ . '/authenticateFunctions.php';
@session_start();

$nowYearAndMonth = (new Datetime)->format('Ym');
$nowYear         = (new Datetime)->format('Y');
$token           = $_SESSION['token'];

$loggedInUserDirectryName = sha1($_SESSION['id']);
if(!is_dir('pictures/'.$loggedInUserDirectryName)){
    mkdir('pictures/'.$loggedInUserDirectryName);
}

if(!is_dir('pictures/'.$loggedInUserDirectryName. '/'. $nowYearAndMonth)){
    mkdir('pictures/'.$loggedInUserDirectryName. '/'. $nowYearAndMonth);
}

if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']){
    echo $_POST['token']. "\n";
    echo $_SESSION['token']. "\n";
    die("不正なアクセスが行われました");
}

//独習PHP p.346〜
//アップロードファイル情報の取得
$ext = pathinfo($_FILES['upfile']['name']);
//アップロードを許可する拡張子を配列で定義
$perm = ['jpeg', 'jpg', 'gif', 'png'];
//アップロード処理そのものの成否をチェック
if ($_FILES['upfile']['error'] !== UPLOAD_ERR_OK) {
    $msg = [
        UPLOAD_ERR_INI_SIZE   => 'php.iniのupload_max_filesize制限を超えています。',
        UPLOAD_ERR_FORM_SIZE  => 'HTMLのMAX_FILE_SIZE制限を超えています。',
        UPLOAD_ERR_PARTIAL    => 'ファイルが一部しかアップロードされていません。',
        UPLOAD_ERR_NO_FILE    => 'ファイルはアップロードされませんでした。',
        UPLOAD_ERR_NO_TMP_DIR => '一時保存フォルダが存在しません。',
        UPLOAD_ERR_CANT_WRITE => 'ディスクへの書き込みに失敗しました。',
        UPLOAD_ERR_EXTENSION  => '拡張モジュールによってアップロードが中断されました。'
    ];
    $errMsg = $msg[$_FILES['upfile']['error']];
//拡張子が許可されているものであるか判定
} elseif (!in_array(strtolower($ext['extension']), $perm)) {
    $errMsg = '画像以外のファイルはアップロードできません。';
//ファイルの内容が画像であるかをチェック
} elseif (!@getimagesize($_FILES['upfile']['tmp_name'])) {
    $errMsg = 'ファイルの内容が画像ではありません。';
//エラーチェックが終わったらアップロード処理
} else {
    $src      = $_FILES['upfile']['tmp_name'];
    $fileType = pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION);
    $fileName = sha1((new Datetime)->format('YmdHis'). $src).'.'.$fileType;
    if (!move_uploaded_file($src, 'pictures/'.$loggedInUserDirectryName. '/'. $nowYearAndMonth.'/'.$fileName)) {
        $errMsg = 'アップロード処理に失敗しました。';
    }
    touch('pictures/'.$loggedInUserDirectryName. '/'. $nowYearAndMonth.'/'.$fileName, time());
}

$fileURL = 'pictures/'.$loggedInUserDirectryName. '/'. $nowYearAndMonth.'/'.$fileName;
//エラー発生時はエラーメッセージを表示
if (isset($errMsg)) {
    die('<div style="color: red;">'.$errMsg.'</div>');
}

//ファイル一式取得
$files = glob('pictures/'.$loggedInUserDirectryName. '/'. $nowYearAndMonth. '/*', GLOB_NOSORT);

//更新日が新しい順にソート
$sort_by_lastmod = function($a, $b){
    return filemtime($b) - filemtime($a);
};
usort($files, $sort_by_lastmod);

for($i = 0; $i < count($files); $i++){
    $files[$i] = '<div class="uploaded-images__item">'.
                 '<div class="uploaded-images__img-wrapper">'.
                 '<img class="uploaded-images__img" src="'. $files[$i]. '" imageID="'. substr(htmlspecialchars($files[$i]), 57, 40). '" token="'. $token. '">'.
                 '</div>'.
                 '<div class="uploaded-images__delete" name="deleting">'.
                 '<i class="far fa-trash-alt"></i>'.
                 '</div>'.
                 '</div>';
}

//-------------------------------------------------------------------------------------------------------------------

//年に該当する文字列だけを抽出
$imageDirectories     = glob('pictures/'. $loggedInUserDirectryName. '/*', GLOB_NOSORT);

for($i = 0; $i < count($imageDirectories); $i++){
    $years[$i] = substr($imageDirectories[$i], -6, -2);
}
$years = array_unique($years);
sort($years);

for($i = 0; $i < count($years); $i++){
    $i + 1 === count($years) ? $selected = ' selected' : $selected = '';
    $years[$i] = '<option value="'. $years[$i]. '"'. $selected. '>'. $years[$i]. '年</option>';
}

//月に該当する文字列だけを抽出
$thisYearDirectories  = glob('pictures/'. $loggedInUserDirectryName. '/'. $nowYear. '*', GLOB_NOSORT);

for($i = 0; $i < count($thisYearDirectories); $i++){
    if(substr($thisYearDirectories[$i], -2, -1) === '0'){
        $months[$i] = substr($thisYearDirectories[$i], -1);
    } else {
        $months[$i] = substr($thisYearDirectories[$i], -2);
    }
}
sort($months);

for($i = 0; $i < count($months); $i++){
    $i + 1 === count($months) ? $selected = ' selected' : $selected = '';
    $months[$i] = '<option value="'. $months[$i]. '"'. $selected. '>'. $months[$i]. '月</option>';
}


$result = [
    'files'    => $files,
    'years'    => $years,
    'months'   => $months,
    'fileURL'  => $fileURL
];
//保存出来たらファイル名をjsonで返す
echo json_encode($result);