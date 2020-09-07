<?
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
	exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<? include_once 'header.php'; ?>
<title>ログイン</title>
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
    <? if(!isset($_SESSION['name'])) : ?>
        <a class="header__link" href="login.php" ontouchstart="">ログイン</a>
        <a class="header__link" href="signUp.php" ontouchstart="">新規登録</a>
    <? endif ?>
    </div>
</header>
<main class="main main--no-sidebar">
    <!-- 認証 auth -->
    <div class="main__login-form unlogged-user-form-div">
        <h1 class="unlogged-user-form-title">ログイン画面</h1>
        <div class="error-message"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></div>
        <form class="unlogged-user-form flex-direction-column" name="unlogged-user-form" action="login.php" method="POST">
            <div class="flex-direction-row input-wrapper">
                <div class="input-wrapper__label">
                    <input type="text" class="input-wrapper__input" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES) : "test@example.com"; ?>">
                </div>
            </div>
            <div class="flex-direction-row input-wrapper border-top-none">
                <div class="input-wrapper__label">
                    <input class="input-wrapper__input" type="password" name="password" placeholder="password" value="12345678">
                </div>
                <span class="password-toggle-icon">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <button type="submit" class="button button--disabled margin-top-20px" name="login" disabled="false">ログイン</button>
        </form>
    </div>
</main>
<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>