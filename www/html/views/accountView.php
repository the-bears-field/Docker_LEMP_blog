<?
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
	exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<? include_once 'header.php'; ?>
<title>アカウント情報</title>
</head>
<body>
<div class="message-box">
    <div class="message-box__window">
        <div class="message-box__content flex-direction-column">
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
    <!-- /.header__nav -->
    <div class="header__menu">
        <? if(isset($_SESSION["name"])) : ?>
            <a class="header__link" href="new.php">新規投稿</a>
            <a class="header__link" href="account.php">アカウント</a>
            <a class="header__link" href="logout.php">ログアウト</a>
        <? endif ?>
    </div>
    <!-- /.header__menu -->
</header>
<main class="main main--has-left-sidebar align-left">
    <div class="message"></div>
    <div class="main__default-message">
        <p>左側のリストから変更したい項目を選択して下さい。</p>
    </div>
</main>
<div class="sidebar left-sidebar settings-list">
    <div id="username" class="sidebar__inner left-sidebar__inner settings-list__link flex-direction-row">
        <section class="settings-list__item">
            <h1 class="settings-list__label">ユーザー名</h1>
            <p class="settings-list__content"><? echo $stmt['name'] ?></p>
        </section>
        <i class="fas fa-chevron-right settings-list__arrow"></i>
    </div>
    <!-- /.sidebar__inner .left-sidebar__inner .settings-list__link -->
    <div id="email" class="sidebar__inner left-sidebar__inner settings-list__link flex-direction-row">
        <section class="settings-list__item">
            <h1 class="settings-list__label">Email</h1>
            <p class="settings-list__content"><? echo $stmt['email'] ?></p>
        </section>
        <i class="fas fa-chevron-right settings-list__arrow"></i>
    </div>
    <!-- /.sidebar__inner .left-sidebar__inner .settings-list__link -->
    <div id="password" class="sidebar__inner left-sidebar__inner settings-list__link flex-direction-row">
        <section class="settings-list__item">
            <h1 class="settings-list__label">パスワード</h1>
            <p class="settings-list__content"></p>
        </section>
        <i class="fas fa-chevron-right settings-list__arrow"></i>
    </div>
    <!-- /.sidebar__inner .left-sidebar__inner .settings-list__link -->
    <div id="deactivate" class="sidebar__inner left-sidebar__inner settings-list__link flex-direction-row">
        <section class="settings-list__item">
            <h1 class="settings-list__label">アカウントを削除</h1>
            <p class="settings-list__content"></p>
        </section>
        <i class="fas fa-chevron-right settings-list__arrow"></i>
    </div>
    <!-- /.sidebar__inner .left-sidebar__inner .settings-list__link -->
</div>
<!-- /.sidebar .left-sidebar .settings-list -->
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>