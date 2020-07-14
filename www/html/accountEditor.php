<?php
header("Content-type: text/json; charset=UTF-8");

require_once __DIR__ . "/authenticateFunctions.php";
require_once __DIR__ . "/databaseConnection.php";
require_once __DIR__ . "/vendor/autoload.php";

requireLoginedSession();

//セッション開始
@session_start();
//セッションID更新
@session_regenerate_id();

date_default_timezone_set('Asia/Tokyo');

$token = sha1(uniqid(random_bytes(16), true));
$_SESSION['token'] = $token;

if(isset($_POST['process'])){
    $process = $_POST['process'];

    switch ($process) {
        case "username":
            $formId            = 'username-editor';
            $username          = $_SESSION['name'];
            $settingsTitle     = 'ユーザー名を変更';
            $settingsInputTags = '<div class="flex-direction-row settings__input-wrapper input-wrapper">'. "\n".
                                    '<div class="input-wrapper__label">'. "\n".
                                        '<input class="input-wrapper__input" type="text" name="username" placeholder="ユーザー名を入力" value="'. $username. '">'. "\n".
                                    '</div>'. "\n".
                                 '</div>';
            $isSecure          = false;
            $result            = settingsEditor($formId, $settingsTitle, $settingsInputTags, $token);
            break;

        case 'email':
            //pasword入力画面への誘導が必要
            $formId            = 'email-editor';
            $email             = $_SESSION['email'];
            $settingsTitle     = 'メールアドレスを変更';
            $settingsInputTags = '<p class="settings__text margin-top-20px">'. "\n".
                                    '現在のメールアドレス: '. $email. "\n".
                                 '</p>'. "\n".
                                 '<div class="flex-direction-row settings__input-wrapper input-wrapper">'. "\n".
                                    '<div class="input-wrapper__label">'. "\n".
                                        '<input id="email-input" class="input-wrapper__input" type="email" name="email" placeholder="メールアドレスを入力" value="">'. "\n".
                                    '</div>'. "\n".
                                 '</div>'. "\n".
                                 '<div class="flex-direction-row settings__input-wrapper input-wrapper border-top-none">'. "\n".
                                    '<div class="input-wrapper__label">'. "\n".
                                        '<input id="password-input" class="input-wrapper__input" type="password" name="password" placeholder="パスワードを入力">'. "\n".
                                    '</div>'. "\n".
                                    '<div class="password-toggle-icon">'. "\n".
                                        '<i class="fas fa-eye"></i>'. "\n".
                                    '</div>'. "\n".
                                 '</div>'. "\n";
            $isSecure          = true;
            $result            = settingsEditor($formId, $settingsTitle, $settingsInputTags, $token);
            break;

        case 'password':
            $formId            = 'password-editor';
            $settingsTitle     = 'パスワードを変更';
            $settingsInputTags = '<div class="flex-direction-row settings__input-wrapper input-wrapper">'.
                                 '<div class="input-wrapper__label">'. "\n".
                                 '<input class="input-wrapper__input" type="password" name="current-password" placeholder="現在のパスワードを入力">'. 
                                 '</div>'. "\n".
                                 '<div class="password-toggle-icon">'.
                                 '<i class="fas fa-eye"></i>'.
                                 '</div>'.
                                 '</div>'.
                                 '<div class="flex-direction-row settings__input-wrapper input-wrapper margin-top-50px">'.
                                 '<div class="input-wrapper__label">'. "\n".
                                 '<input class="input-wrapper__input" type="password" name="new-password" placeholder="新しいパスワードを入力">'.
                                 '</div>'. "\n".
                                 '<div class="password-toggle-icon">'.
                                 '<i class="fas fa-eye"></i>'.
                                 '</div>'.
                                 '</div>'.
                                 '<div class="flex-direction-row settings__input-wrapper input-wrapper border-top-none">'.
                                 '<div class="input-wrapper__label">'. "\n".
                                 '<input class="input-wrapper__input" type="password" name="password-confirmation" placeholder="新しいパスワードを、もう一度入力">'.
                                 '</div>'. "\n".
                                 '<div class="password-toggle-icon">'.
                                 '<i class="fas fa-eye"></i>'.
                                 '</div>'.
                                 '</div>';
            $isSecure          = true;
            $result            = settingsEditor($formId, $settingsTitle, $settingsInputTags, $token);
            break;

        case 'deactivate':
            $username          = $_SESSION['name'];
            $email             = $_SESSION['email'];
            $isSecure          = true;
            $result            = accountDeactivator($username, $email, $token);
            break;

        default:
            '';
            break;
    }
    echo json_encode($result);
}

function settingsEditor(string $formId, string $settingsTitle, string $settingsInputTags, string $token) {
    $result = '<div class="main__settings settings">'. "\n".
              '<div class="settings__label flex-direction-row">'. "\n".
              '<span class="settings__back"><i class="fas fa-arrow-left"></i></span>'. "\n".
              '<h1 class="settings__title">'. $settingsTitle. '</h1>'. "\n".
              '</div>'. "\n".
              '<form id="'. $formId. '" class="flex-direction-column" method="post" action="account.php">'. "\n".
              '<input type="hidden" name="token" value="'. $token. '">'. "\n".
              $settingsInputTags. "\n".
              '<button class="button button--disabled settings__button margin-top-20px" name="sending" type="submit" disabled="true">保存</button>'. "\n".
              '</form>'. "\n".
              '</div>'. "\n";

    return $result;
}

function accountDeactivator(string $username, string $email, string $token) {
    $result = '<div class="main__settings settings">'. "\n".
                '<div class="settings__label flex-direction-row">'. "\n".
                    '<span class="settings__back"><i class="fas fa-arrow-left"></i></span>'. "\n".
                    '<h1 class="settings__title">アカウントの削除</h1>'. "\n".
                '</div>'. "\n".
                '<div class="flex-direction-column">'. "\n".
                    '<div class="margin-top-20px">'. "\n".
                        '<h2>'. htmlspecialchars($username, ENT_QUOTES, "UTF-8"). '</h2>'. "\n".
                        '<p>'. htmlspecialchars($email, ENT_QUOTES, "UTF-8"). '</p>'. "\n".
                    '</div>'. "\n".
                    '<p class="settings__text margin-top-20px">'. "\n".
                        '上記アカウントが削除されます。<br>'. "\n".
                        '一度、アカウントの削除を実行すると、アカウントの復元はできなくなります。<br>'. "\n".
                        '以上をご理解の上、アカウントの削除を実行して下さい。'. "\n".
                    '</p>'. "\n".
                    '<p class="settings__text settings__warning-message margin-top-20px"></p>'.
                    '<div id="account-deactivator" class="flex-direction-column" method="post" action="account.php">'. "\n".
                        '<input type="hidden" class="token" name="token" value="'. $token. '">'. "\n".
                        '<div class="flex-direction-row settings__input-wrapper input-wrapper margin-top-20px">'. "\n".
                            '<div class="input-wrapper__label">'. "\n".
                                '<input class="input-wrapper__input" type="password" name="password" placeholder="パスワードを入力">'. "\n".
                            '</div>'. "\n".
                            '<div class="password-toggle-icon">'. "\n".
                                '<i class="fas fa-eye"></i>'. "\n".
                            '</div>'. "\n".
                        '</div>'. "\n".
                        '<button class="button button--disabled button--warning settings__button margin-top-20px" type="button" disabled="true">アカウントの削除</button>'. "\n".
                    '</div>'. "\n".
                '</div>'. "\n".
            '</div>'. "\n";

    return $result;
}