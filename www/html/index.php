<?php
// namespace myblog\index;

require_once __DIR__. '/authenticateFunctions.php';
require_once __DIR__. '/databaseConnection.php';
require_once __DIR__. '/vendor/autoload.php';

//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

$token = sha1(uniqid(random_bytes(16), true));
$_SESSION['token'] = $token;

$sqlCommand = 'SELECT tag_name FROM tags ORDER BY tags.tag_name ASC';
$tagsList   = pdoPrepare($sqlCommand);
$tagsList->execute();
$tagsList   = $tagsList->fetchAll(PDO::FETCH_COLUMN);

$whereAndLikeClause = '';

//1ページに表示するpostの件数
$countArticleDisplay = 10;

isset($_GET['pageID']) ? $pageID = intval($_GET['pageID']) : $pageID = 1;

//表示を開始点を定義
if($pageID > 1){
    $beginArticleDisplay  = ($pageID * $countArticleDisplay) - $countArticleDisplay;
} else {
    $beginArticleDisplay = 0;
}

//通常処理
if(!isset($_GET['searchWord']) ||
   !isset($_GET['tag']) ||
   $_GET['searchWord'] === '' ||
   (isset($_GET['searchWord']) && isset($_GET['tag']))
   ){
    $searchWords       = [];
    $searchWord        = '';
    $sqlCommand        = "SELECT COUNT(posts.post_id) FROM posts";
    $totalArticleCount = pdoPrepare($sqlCommand);
    $totalArticleCount->execute();
    $totalArticleCount = $totalArticleCount->fetchColumn();
    $totalArticleCount = intval($totalArticleCount);

    if($totalArticleCount > 0){
        $sqlCommand = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                       LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                       LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                       LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                       GROUP BY posts.post_id
                       ORDER BY post_id DESC LIMIT :beginArticleDisplay, :countArticleDisplay";
        $stmt       = pdoPrepare($sqlCommand);
    }
}

//タグ検索時の処理
if(isset($_GET['tag']) && $_GET['tag'] !== '' && !isset($_GET['searchWord'])){
    $searchWord = $_GET['tag'];
    $sqlCommand = "SELECT COUNT( * ) FROM
                            (
                                SELECT tags.tag_name FROM post_tags
                                JOIN tags ON post_tags.tag_id = tags.tag_id
                                WHERE tag_name = :tag
                            ) AS is_find_tag";

    $isFindTag = pdoPrepare($sqlCommand);
    $isFindTag->bindValue(':tag', $searchWord, PDO::PARAM_STR);
    $isFindTag->execute();
    $isFindTag = $isFindTag->fetchColumn();
    // $isFindTag->fetchColumn() > 0 ? $isFindTag = TRUE : $isFindTag = FALSE;

    if($isFindTag){
        $sqlCommand = "SELECT COUNT( * ) FROM
                        (
                            SELECT posts.post_id, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags FROM posts
                            LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                            LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                            GROUP BY posts.post_id
                            HAVING tags LIKE :tag
                        ) AS tag_count";
        $totalArticleCount = pdoPrepare($sqlCommand);
        $totalArticleCount->bindValue(':tag', '%'. $searchWord. '%', PDO::PARAM_STR);
        $totalArticleCount->execute();
        $totalArticleCount = $totalArticleCount->fetchColumn();
        $totalArticleCount = intval($totalArticleCount);
    } else {
        $totalArticleCount = 0;
    }

    if($totalArticleCount > 0){
        $sqlCommand  = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                        LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                        LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                        LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id
                        GROUP BY posts.post_id
                        HAVING tags LIKE :tag
                        ORDER BY posts.post_id DESC LIMIT :beginArticleDisplay, :countArticleDisplay";
        $stmt        = pdoPrepare($sqlCommand);
        $stmt->bindValue(':tag', '%'. $searchWord. '%', PDO::PARAM_STR);
    }
}

//検索した時の処理
if(isset($_GET['searchWord']) && $_GET['searchWord'] !== '' && !isset($_GET['tag'])){
    $searchWord  = $_GET['searchWord'];
    $searchWord  = trimmingSearchWords($searchWord);
    $searchWords = preg_split('/[\s]/', $searchWord, -1, PREG_SPLIT_NO_EMPTY);
    //配列で重複している物を削除する
    $searchWords = array_unique($searchWords);

    //Keyの再定義
    $searchWords = array_values($searchWords);

    $countClause = "SELECT COUNT(posts.post_id) FROM posts";

    for ($i = 0; $i < count($searchWords); $i++) {
        if($i === 0){
            $whereAndLikeClause .= ' WHERE post LIKE :'. strval($i);
        } else {
            $whereAndLikeClause .= ' AND post LIKE :'. strval($i);
        }
    }

    for ($i = 0; $i < count($searchWords); $i++) {
        if($i === 0){
            $whereAndLikeClause .= ' OR title LIKE :'. strval($i);
        } else {
            $whereAndLikeClause .= ' AND title LIKE :'. strval($i);
        }
    }
    $whereAndLikeClause .= " ESCAPE '!'";

    $sqlCommand        = $countClause. $whereAndLikeClause;
    $totalArticleCount = pdoPrepare($sqlCommand);

    for ($i = 0; $i < count($searchWords); $i++) {
        $totalArticleCount->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
    }

    $totalArticleCount->execute();
    $totalArticleCount = $totalArticleCount->fetchColumn();
    $totalArticleCount = intval($totalArticleCount);

    $selectClause = "SELECT posts.post_id, posts.title, posts.post, posts.created_at, posts.updated_at, GROUP_CONCAT(tags.tag_name SEPARATOR ',') AS tags, user_uploaded_posts.user_id AS user_id FROM posts
                    LEFT JOIN post_tags ON posts.post_id = post_tags.post_id
                    LEFT JOIN tags ON post_tags.tag_id = tags.tag_id
                    LEFT JOIN user_uploaded_posts ON posts.post_id = user_uploaded_posts.post_id";

    if($totalArticleCount > 0){
        $sqlCommand  = $selectClause. $whereAndLikeClause. 'GROUP BY posts.post_id ORDER BY posts.post_id DESC LIMIT :beginArticleDisplay, :countArticleDisplay';
        $stmt        = pdoPrepare($sqlCommand);

        for ($i = 0; $i < count($searchWords); $i++) {
            $stmt->bindValue(':'. strval($i), '%'. preg_replace('/(?=[!_%])/', '!', $searchWords[$i]) .'%', PDO::PARAM_STR);
        }
    }
}
if($totalArticleCount === 0 && $searchWord === ''){
    $searchResultMessage = 'まだ投稿されていません。';
    $result              = [];
    $paginatorTags       = '';
} elseif($totalArticleCount === 0) {
    $searchResultMessage = $searchWord. ' に一致する結果は見つかりませんでした。';
    $result              = [];
    $paginatorTags       = '';
} else {
    //ページネーションの処理
    //Pagerのオプションを定義
    $paginatorOptions = [
        'totalItems'            => $totalArticleCount,
        'mode'                  => 'Sliding',
        'delta'                 => 2,
        'perPage'               => $countArticleDisplay,
        'prevImg'               => '<i class="fas fa-chevron-left"></i>',
        'nextImg'               => '<i class="fas fa-chevron-right"></i>',
        'firstPageText'         => '<i class="fas fa-chevron-double-left"></i>',
        'lastPageText'          => '<i class="fas fa-chevron-double-right"></i>',
        'firstPagePre'          => '',
        'firstPagePost'         => '',
        'lastPagePre'           => '',
        'lastPagePost'          => '',
        'separator'             => '',
        'curPageLinkClassName'  => 'pagenator__link_current',
        'linkClass'             => 'pagenator__link',
        'separator'             => '',
        'spacesBeforeSeparator' => 0,
        'spacesAfterSeparator'  => 0
        ];

    $paginator       = Pager::factory($paginatorOptions);
    $navigationLink  = $paginator->getLinks();
    $paginatorTags   = $navigationLink['all'];
    //ページネーションの処理ここまで

    //DBから記事データ取得
    $stmt->bindValue(':beginArticleDisplay', $beginArticleDisplay, PDO::PARAM_INT);
    $stmt->bindValue(':countArticleDisplay', $countArticleDisplay, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $stmt   = null;

    //DBから取得したデータのうち、"tags"を文字列から配列に変換
    for($i = 0; $i < count($result); ++$i){
        $result[$i]['tags'] === null ? $result[$i]['tags'] = [] : $result[$i]['tags'] = explode(',', $result[$i]['tags']);
    }

    //検索を実施していた場合、検索結果の文章を $searchResultMessage に代入
    !empty($searchWord) || $searchWord === '0' ? $searchResultMessage = $searchWord. ' の検索結果' : $searchResultMessage = null;

    !empty($searchResultMessage) && isset($_GET['tag']) ? $searchResultMessage = '#'. $searchResultMessage : $searchResultMessage;
}

function trimmingSearchWords($string)
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

function pdoPrepare($sqlCommand){
    $pdo        = (new DatabaseConnection())->getPdo();
    $pdoPrepare = $pdo->prepare($sqlCommand);
    return $pdoPrepare;
}

include_once __DIR__ . '/views/indexView.php';