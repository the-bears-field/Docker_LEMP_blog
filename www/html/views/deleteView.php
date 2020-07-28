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
        <div class="posts__message">以下の記事を削除しますか？</div>
        <article class="posts__content flex-direction-column">
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
                <? foreach($tags as $tag) : ?>
                <span class="tags__link"><? echo htmlspecialchars($tag, ENT_QUOTES); ?></span>
                <? endforeach ?>
            </div>
            <div class="posts__text margin-top-50px">
                <? echo (new HTMLPurifier())->purify($result['post'])."\n"; ?>
            </div>
            <form class="posts__button-wrapper flex-direction-row" method="post">
                <input type="hidden" name="token" value="<? echo $token ?>">
                <input type="hidden" name="tags" value="<? echo $result['tags']; ?>">
                <button class="button button--enabled" type="submit" name="cancel">キャンセル</button>
                <button class="button button--enabled button--warning" type="submit" name="deleting">削除</button>
            </form>
            <div id="like_button_container"></div>
        </article>
    </div>
</main>
<div class="sidebar right-sidebar">
    <div class="sidebar__inner right-sidebar__inner">
        <form class="search flex-direction-row" method="get" action="index.php">
            <input type="hidden" name="token" value="<? echo $token ?>">
            <input type="search" class="search__input" name="searchWord" placeholder="検索">
            <button class="search__button" type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
<!-- <script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="./assets/javascripts/like_button.js"></script> -->
</body>
</html>