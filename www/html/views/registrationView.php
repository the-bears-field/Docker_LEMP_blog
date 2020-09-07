<?
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
	exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<? include_once 'header.php'; ?>
<title>新規登録</title>
</head>
<body>
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
    <? if(!isset($_SESSION["name"])) : ?>
        <? echo "<a class=\"header__link\" href=\"login.php\">ログイン</a>\n"; ?>
        <? echo "<a class=\"header__link\" href=\"signUp.php\">新規登録</a>\n"; ?>
    <? endif ?>
    </div>
</header>
<main class="main main--no-sidebar">
<? echo $message; ?>
</main>
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>