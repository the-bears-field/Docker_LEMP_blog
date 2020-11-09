<?php
require_once __DIR__. '/authenticateFunctions.php';
require_once __DIR__. '/databaseConnection.php';
require_once __DIR__. '/vendor/autoload.php';

@session_start();
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

if (isset($_GET['postID'])) {
    //タグ一覧取得
    $tagsList = (new AllTagsData)->selectCommand();
    $result   = (new SinglePostsData)->selectCommand();
    $result['tags'] === null ? $result['tags'] = [] : $result['tags'] = explode(' ', $result['tags']);

    if (!$result) {
        header("Location: index.php");
        exit();
    }
}

if (!isset($_GET['postID'])) {
    header("Location: index.php");
}

include_once __DIR__. '/views/postView.php';