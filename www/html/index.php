<?php
require_once __DIR__. '/authenticateFunctions.php';
require_once __DIR__. '/databaseConnection.php';
require_once __DIR__. '/vendor/autoload.php';

//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

/*
2020/07/21
トークンを用いたcsrf対策をしていたところ、
Google Chromeにおいて、勝手に$_SESSION['token']の値が変わる不具合が発覚。

2020/07/28
Chrome Loggerを導入し調査した結果、
どのページにアクセスしてもindex.phpが読み込まれていた。

2020/07/31
nginxのconfファイルを書き換えることで対応。
*/

//タグ一覧取得
$tagsList = (new AllTagsData)->selectCommand();

//1ページに表示するpostの件数
$postsCountNumber = 10;

isset($_GET['pageID']) ? $pageID = intval($_GET['pageID']) : $pageID = 1;

//表示を開始点を定義
if($pageID > 1){
    $beginPostsCount = ($pageID * $postsCountNumber) - $postsCountNumber;
} else {
    $beginPostsCount = 0;
}

// データベース接続
$displayPosts = new PostsDataUsedInIndex();
$displayPosts->setBeginPostsCount($beginPostsCount);
$displayPosts->setPostsCountNumber($postsCountNumber);
$result          = $displayPosts->selectCommand();
$totalPostsCount = $displayPosts->getTotalPostsCount();

// 通常処理
if(!isset($_GET['searchWord']) || !isset($_GET['tag']) || $_GET['searchWord'] === '' || (isset($_GET['searchWord']) && isset($_GET['tag']))){
    $searchWord   = '';
}

// タグ検索時の処理
if(isset($_GET['tag']) && $_GET['tag'] !== '' && !isset($_GET['searchWord'])){
    $searchWord   = $_GET['tag'];
}

// 検索した時の処理
if(isset($_GET['searchWord']) && $_GET['searchWord'] !== '' && !isset($_GET['tag'])){
    $searchWord   = $_GET['searchWord'];
}

if($totalPostsCount === 0 && $searchWord === ''){
    $searchResultMessage = 'まだ投稿されていません。';
    $result              = [];
    $paginatorTags       = '';
} elseif($totalPostsCount === 0) {
    $searchResultMessage = $searchWord. ' に一致する結果は見つかりませんでした。';
    $result              = [];
    $paginatorTags       = '';
} else {
    //ページネーションの処理
    //Pagerのオプションを定義
    $paginatorOptions = [
        'totalItems'            => $totalPostsCount,
        'mode'                  => 'Sliding',
        'delta'                 => 2,
        'perPage'               => $postsCountNumber,
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

    $paginator      = Pager::factory($paginatorOptions);
    $navigationLink = $paginator->getLinks();
    $paginatorTags  = $navigationLink['all'];
    //ページネーションの処理ここまで

    $stmt   = null;

    //DBから取得したデータのうち、"tags"を文字列から配列に変換
    for($i = 0; $i < count($result); ++$i){
        $result[$i]['tags'] === null ? $result[$i]['tags'] = [] : $result[$i]['tags'] = explode(',', $result[$i]['tags']);
    }

    //検索を実施していた場合、検索結果の文章を $searchResultMessage に代入
    !empty($searchWord) || $searchWord === '0' ? $searchResultMessage = $searchWord. ' の検索結果' : $searchResultMessage = null;

    !empty($searchResultMessage) && isset($_GET['tag']) ? $searchResultMessage = '#'. $searchResultMessage : $searchResultMessage;
}

include_once __DIR__ . '/views/indexView.php';
