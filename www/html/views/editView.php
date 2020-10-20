<?
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
	exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<? include_once 'header.php'; ?>
<title>編集</title>
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
    <? endif ?>
    </div>
</header>
<div class="message"></div>
<main class="main main--has-right-sidebar">
    <article class="main__wysiwyg wysiwyg">
        <form method="post" class="wysiwyg__form flex-direction-column">
            <input type="hidden" name="token" value="<? echo $token; ?>">
            <input type="hidden" name="current-tags" value="<? echo $tags; ?>">
            <input class="wysiwyg__title" type="text" name="title" placeholder="タイトルを入力して下さい。" value="<? if(isset($_GET["postID"])){echo htmlspecialchars($result['title'], ENT_QUOTES);} ?>">
            <textarea id="post-form" name="post"><? if(isset($_GET["postID"])){echo htmlspecialchars($result['post'], ENT_QUOTES);} ?></textarea>
            <input class="wysiwyg__tags" type="text" name="tags" placeholder="tags" value="<? echo htmlspecialchars($result['tags'], ENT_QUOTES); ?>">
            <button class="button button--disabled wysiwyg__button margin-top-20px" name="posting" type="submit" disabled="true">送信</button>
        </form>
    </article>
</main>

<div class="sidebar right-sidebar">
    <div class="sidebar__inner right-sidebar__inner margin-top-50px">
        <form method="post" class="image-uploader" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<? echo $token; ?>">
            <input type="file" class="image-uploader__file" name="upfile">
            <button type="button" class="button image-uploader__button" name="uploading">画像をアップロード</button>
        </form>
    </div>
    <div class="sidebar__inner right-sidebar__inner">
        <div class="sidebar__caption">画像一覧</div>
        <div class="image-selection" token="<? echo $token; ?>">
            <div class="image-selection__year">
                <select class="image-selection__items">
                    <? for($i = 0; $i < count($picturesDirectories); $i++): ?>
                    <? $i + 1 === count($picturesDirectories) ? $selected = ' selected' : $selected = ''; ?>
                    <option value="<? echo $picturesDirectories[$i]; ?>"<? echo $selected ?>><? echo $picturesDirectories[$i].'年'; ?></option>
                    <? endfor ?>
                </select>
            </div>

            <div class="image-selection__month">
                <select class="image-selection__items">
                    <? for($i = 0; $i < count($selectedYearDirectories); $i++) :?>
                    <? $i + 1 === count($selectedYearDirectories) ? $selected = ' selected' : $selected = ''; ?>
                    <option value="<? echo $selectedYearDirectories[$i]; ?>"<? echo $selected; ?>><? echo $selectedYearDirectories[$i]. '月'; ?></option>
                    <? endfor ?>
                </select>
            </div>
        </div>

    </div>
    <div class="sidebar__inner right-sidebar__inner uploaded-images flex-direction-row" year="<? echo htmlspecialchars($nowYear); ?>" month="<? echo htmlspecialchars($nowMonth); ?>">
        <? foreach($files as $file) : ?>
            <? if(is_file($file)) : ?>
                <div class="uploaded-images__item">
                    <? $buttonName = substr(htmlspecialchars($file), 57, 40); ?>
                    <div class="uploaded-images__img-wrapper">
                        <img class="uploaded-images__img" src="<? echo htmlspecialchars($file); ?>" imageID="<? echo htmlspecialchars($buttonName); ?>">
                    </div>
                    <div class="uploaded-images__delete" name="deleting">
                        <i class="far fa-trash-alt"></i>
                    </div>
                </div>
            <? endif?>
        <? endforeach ?>
    </div>
</div>

<footer class="footer">
    <p class="footer__copyright">copyright 2019 Satoshi Kumano</p>
</footer>
</body>
</html>