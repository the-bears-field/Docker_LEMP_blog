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
    <? if(isset($_POST['signup']) && !$warningMessages && !$errorMessages) : ?>
    <div class="flex-direction-column">
        <p>send it</p>
        <p><? echo htmlspecialchars($mailSubject, ENT_QUOTES) ?></p>
        <p><? echo htmlspecialchars($username, ENT_QUOTES) ?>様</p>
        <p>本ブログにご登録頂きありがとうございます。<br>24時間以内に下記アドレスへアクセスし、登録を完了して下さい。</p>
        <a href="<? echo htmlspecialchars($authenticationURL, ENT_QUOTES) ?>"><? echo htmlspecialchars($authenticationURL, ENT_QUOTES) ?></a>
    </div>
    <? elseif($errorMessages) :?>
        <? foreach($errorMessages as $errorMessage) : ?>
            <p><? echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></p>
        <? endforeach ?>
    <? else : ?>
    <div class="unlogged-user-form-div">
        <h1 class="unlogged-user-form-title">新規登録画面</h1>
        <? foreach($warningMessages as $warningMessage) : ?>
            <div class="error-message" style="color: #ff0000;"><? echo htmlspecialchars($warningMessage, ENT_QUOTES); ?></div>
        <? endforeach ?>
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
    <? endif; ?>
</main>
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>