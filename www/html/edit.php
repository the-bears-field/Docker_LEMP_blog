<?php
require_once __DIR__. '/authenticateFunctions.php';
require_once __DIR__. '/databaseConnection.php';

requireLoginedSession();

date_default_timezone_set('Asia/Tokyo');

function trimmingWords($string)
{
    // 全角スペースを半角へ
    $string = preg_replace('/(\xE3\x80\x80)/', ' ', $string);
    // 両サイドのスペースを消す
    $string = trim($string);
    // 改行、タブをスペースに変換
    $string = preg_replace('/[\n\r\t]/', ' ', $string);
    // 複数スペースを一つのスペースに変換
    $string = preg_replace('/\s{2,}/', ' ', $string);

    return $string;
}

if (!isset($_POST['posting'])) {
    $token = sha1(uniqid(random_bytes(16), true));
    $_SESSION['token'] = $token;
}

if (isset($_GET['postID'])) {
    $postID = intval($_GET['postID']);
    $sqlCommand  = "SELECT posts.post_id, posts.title, posts.post, GROUP_CONCAT(tags.tag_name SEPARATOR ' ') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                    LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                    LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                    LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                    GROUP BY posts.post_id
                    HAVING posts.post_id = :id";
    $pdo    = (new DatabaseConnection())->getPdo();
    $stmt   = $pdo->prepare($sqlCommand);
    $stmt->bindValue(':id', $postID, PDO::PARAM_INT);
    $stmt->execute();
    $stmt   = $stmt->fetch();
    $pdo    = null;
    $title  = htmlspecialchars($stmt['title'], ENT_QUOTES);
    $post   = htmlspecialchars($stmt['post'], ENT_QUOTES);
    $tags   = $stmt['tags'];
}

if (!isset($_GET['postID']) || $stmt['user_id'] !== $_SESSION['id']) {
    header('location: /');
    exit();
}

if (isset($_POST['posting'])) {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("不正なアクセスが行われました");
    } else {
        $post        = $_POST;

        $updatePost = new UpdatePostAndTags;
        $updatePost->setHttpPost($post);
        $updatePost->updateCommand();

        if($updatedTags){
            $updatedTags = trimmingWords($_POST['tags']);
            $updatedTags = preg_split('/[\s]/', $updatedTags, -1, PREG_SPLIT_NO_EMPTY);
            $updatedTags = array_unique($updatedTags);
            $updatedTags = array_values($updatedTags);
        } else {
            $updatedTags = [];
        }

        if($currentTags){
            $currentTags = trimmingWords($_POST['current-tags']);
            $currentTags = preg_split('/[\s]/', $currentTags, -1, PREG_SPLIT_NO_EMPTY);
            $currentTags = array_unique($currentTags);
            $currentTags = array_values($currentTags);
        } else {
            $currentTags = [];
        }

        try{
            $pdo  = (new DatabaseConnection())->getPdo();
            $stmt = $pdo->prepare('UPDATE posts SET title = :title, post = :post, updated_at = :updated_at WHERE post_id = :id');
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':post', $post, PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $updatedAt, PDO::PARAM_STR);
            $stmt->bindValue(':id', $postID, PDO::PARAM_STR);
            $stmt->execute();


            //tagの登録、削除作業
            //$updatedTagsにあって$currentTagsにないものを追加
            $addTags = array_diff($updatedTags, $currentTags);

            if($addTags){
                $addTags = array_values($addTags);

                for($i = 0; $i < count($addTags); ++$i){
                    $stmt = $pdo->prepare("CALL sp_add_tags(:tag_name, :post_id)");
                    $stmt->bindValue(":tag_name", $addTags[$i], PDO::PARAM_STR);
                    $stmt->bindValue(":post_id", $postID, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
            //$currentTagsにあって$updatedTagsにないものを削除
            $removeTags = array_diff($currentTags, $updatedTags);

            if($removeTags){
                $removeTags = array_values($removeTags);

                for($i = 0; $i < count($removeTags); ++$i){
                    $stmt = $pdo->prepare("CALL sp_remove_tags(:tag_name, :post_id)");
                    $stmt->bindValue(":tag_name", $removeTags[$i], PDO::PARAM_STR);
                    $stmt->bindValue(":post_id", $postID, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            $pdo  = null;
            unset($_SESSION['token']);
            header('Location: /');
        } catch (PDOException $e) {
            $errorMessage = 'データベースエラー';
            //$e->getMessage() でエラー内容を参照可能（デバッグ時のみ表示）
            echo $e->getMessage();
            die();
        }
    }
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

include_once __DIR__. '/views/editView.php';