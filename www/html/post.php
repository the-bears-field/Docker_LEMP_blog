<?php
require_once __DIR__. '/authenticateFunctions.php';
require_once __DIR__. '/databaseConnection.php';
require_once __DIR__. '/vendor/autoload.php';

@session_start();
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

if (isset($_GET['postID'])) {
    //タグ一覧取得
    $sqlCommand = 'SELECT tag_name FROM tags ORDER BY tags.tag_name ASC';
    $pdo        = (new databaseConnection())->getPdo();
    $tagsList   = $pdo->prepare($sqlCommand);
    $tagsList->execute();
    $tagsList   = $tagsList->fetchAll(PDO::FETCH_COLUMN);

    $postID     = intval($_GET['postID']);
    $sqlCommand = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                   LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                   LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                   LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                   GROUP BY posts.post_id
                   HAVING posts.post_id = :id";
    $stmt       = $pdo->prepare($sqlCommand);
    $stmt->bindValue(':id', $postID, PDO::PARAM_INT);
    $stmt->execute();
    $result     = $stmt->fetch();
    $result['tags'] === null ? $result['tags'] = [] : $result['tags'] = explode(',', $result['tags']);
}

if (!isset($_GET['postID'])) {
    header("Location: index.php");
}

include_once __DIR__. '/views/postView.php';