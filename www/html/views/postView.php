<?
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
	exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<? include_once 'header.php'; ?>
<title>投稿記事</title>
</head>
<body>
<div class="message-box">
    <div class="message-box__window">
        <div class="message-box__content flex-direction-column">
            <p class="message-box__text"></p>
            <div class="message-box__send-links flex-direction-row">
                <button class="button button--enabled cancel">キャンセル</button>
                <button class="button button--enabled"></button>
            </div>
        </div>
    </div>
</div>

<header class="header flex-direction-row">
    <a class="header__logo" href="/">Kumano.code</a>
    <div class="header__nav">
        <div class="header__nav-icon">
            <span class="header__nav-line"></span>
            <span class="header__nav-line"></span>
            <span class="header__nav-line"></span>
        </div>
    </div>
    <div class="header__menu">
    <? if(isset($_SESSION["name"])) : ?>
        <a class="header__link" href="new.php">新規投稿</a>
        <a class="header__link" href="account.php">アカウント</a>
        <a class="header__link" href="logout.php">ログアウト</a>
    <? else : ?>
        <a class="header__link" href="login.php">ログイン</a>
        <a class="header__link" href="signUp.php">新規登録</a>
    <? endif ?>
    </div>
</header>
<main class="main main--has-right-sidebar flex-direction-column">
    <div class="main__posts posts">
        <article class="posts__content flex-direction-column">
            <? if(isset($_SESSION["name"]) && $result['user_id'] === $_SESSION['id']) : ?>
            <div class="posts__nav posts-nav">
                <span class="posts-nav__icon fas fa-ellipsis-h"></span>
                <div class="posts-nav__menu flex-direction-column">
                    <a class="posts-nav__link flex-direction-row" href="edit.php?postID=<? echo $result['post_id']; ?>">
                        <i class="fas fa-pen"></i>
                        <span class="posts-nav__link-text">編集</span>
                    </a>
                    <a class="posts-nav__link flex-direction-row margin-top-10px" href="delete.php?postID=<? echo $result['post_id']; ?>">
                        <i class="fas fa-trash-alt"></i>
                        <span class="posts-nav__link-text">削除</span>
                    </a>
                </div>
            </div>
            <? endif ?>
            <h1 class="posts__title margin-top-20px"><? echo htmlspecialchars($result['title'], ENT_QUOTES); ?></h1>
            <div class="posts__datetime datetime margin-top-10px flex-direction-row">
                <time class="datetime__text" datetime="<? echo htmlspecialchars(date('Y-n-j', strtotime($result['created_at'])), ENT_QUOTES); ?>">
                    <i class="far fa-clock"></i>
                    <? echo htmlspecialchars(date('Y年n月j日', strtotime($result['created_at'])), ENT_QUOTES); ?>
                </time>
                <? if($result['created_at'] !== $result['updated_at']) : ?>
                <time class="datetime__text" datetime="<? echo htmlspecialchars(date('Y-n-j', strtotime($result['updated_at'])), ENT_QUOTES); ?>">
                    <i class="fas fa-sync-alt"></i>
                    <? echo  htmlspecialchars(date('Y年n月j日', strtotime($result['updated_at'])), ENT_QUOTES)."\n"; ?>
                </time>
                <? endif ?>
            </div>
            <div class="posts__tags tags flex-direction-row">
                <? foreach($result['tags'] as $tag) : ?>
                <a class="tags__link" href="/?tag=<? echo htmlspecialchars($tag, ENT_QUOTES); ?>"><? echo htmlspecialchars($tag, ENT_QUOTES); ?></a>
                <? endforeach ?>
            </div>
            <div class="posts__text posts__text--post margin-top-50px">
                <? echo (new HTMLPurifier())->purify($result['post'])."\n"; ?>
            </div>
        </article>
    </div>
</main>
<aside class="sidebar right-sidebar">
    <div class="sidebar__inner right-sidebar__inner">
        <form class="sidebar__search search flex-direction-row" method="get" action="/">
            <input type="search" class="search__input" name="searchWord" placeholder="ブログ内で検索">
            <button class="search__button fas fa-search" type="submit"></button>
        </form>
    </div>
    <div class="sidebar__inner right-sidebar__inner flex-direction-column">
        <h2 class="sidebar__caption">タグ一覧</h2>
        <div class="sidebar__tags">
            <? foreach($tagsList as $tag) : ?>
            <a class="sidebar__tags-item" href="/?tag=<? echo htmlspecialchars($tag, ENT_QUOTES)?>"><? echo htmlspecialchars($tag, ENT_QUOTES); ?></a>
            <? endforeach ?>
        </div>
    </div>
</aside>
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>