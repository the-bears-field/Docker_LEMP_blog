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
    <div class="unlogged-user-form-div">
        <h1 class="unlogged-user-form-title">新規登録画面</h1>
        <? foreach($errorMessages as $errorMessage) : ?>
            <div class="error-message" style="color: #ff0000;"><? echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></div>
        <? endforeach ?>
        <div style="color: #ff0000;"><? echo htmlspecialchars($signUpMessage, ENT_QUOTES); ?></div>
        <form class="unlogged-user-form flex-direction-column" name="unlogged-user-form" action="signup.php" method="POST">
            <div class="flex-direction-row input-wrapper">
                <label class="input-wrapper__label">
                    <input type="text" class="input-wrapper__input" name='username' placeholder="User Name" value="<?php if (isset($_POST["username"])) {echo htmlspecialchars($_POST["username"], ENT_QUOTES);} ?>">
                </label>
            </div>
            <div class="flex-direction-row input-wrapper border-top-none">
                <label class="input-wrapper__label">
                    <input type="text" class="input-wrapper__input" name='email' placeholder="Email" value="<?php if (isset($_POST["email"])) {echo htmlspecialchars($_POST["email"], ENT_QUOTES);} ?>">
                </label>
            </div>
            <div class="flex-direction-row input-wrapper border-top-none">
                <label class="input-wrapper__label">
                    <input type="text" class="input-wrapper__input" name='email2' placeholder="Email" value="">
                </label>
            </div>
            <div class="flex-direction-row input-wrapper border-top-none">
                <label class="input-wrapper__label">
                    <input class="input-wrapper__input" type="password" name="password" placeholder="パスワードを入力">
                </label>
                <span class="password-toggle-icon">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <div class="flex-direction-row input-wrapper border-top-none">
                <label class="input-wrapper__label">
                    <input class="input-wrapper__input" type="password" name="password2" placeholder="再度パスワードを入力">
                </label>
                <span class="password-toggle-icon">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <button type="submit" class="button button--disabled margin-top-20px" name='signup' disabled="true">新規登録</button>
        </form>
    </div>
</main>
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>