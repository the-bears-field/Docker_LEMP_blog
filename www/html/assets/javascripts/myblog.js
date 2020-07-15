if (document.URL.match(/new.php/) || document.URL.match(/edit.php/)) {
    //trumbowyg生成
    $('#post-form').trumbowyg({
        autogrow:            true,
        imageWidthModalEdit: true,
        lang:                'ja',
        resetCss:            true,
        tagsToKeep:          ['i'],
        tagsToRemove:        ['script']
    });
}

$(document).ready(function(){
    /**************************************** common ****************************************/
    //ウィンドウのキャンセルボタンを押した時の処理
    $('body').on('click', '.cancel', function() {
        $('body').removeClass('fixed');
        $('.message-box').fadeOut(500);
        logoutButtonClickState = false;
        $('.message-box__content').delay(500).queue(function () {
            $(this).empty().dequeue();
        });
    });


    //ウィンドウ外をクリックした時の処理
    $('body').on('click', '.message-box', function(event) {
        if (!$(event.target).is('.message-box-window, .message-box--window div, .message-box-window p, .message-box-window button, .logout-link')) {
            $('body').removeClass('fixed');
            $('.message-box').fadeOut(500);
            logoutButtonClickState = false;

            $('.message-box__content').delay(500).queue(function () {
                $(this).empty().dequeue();
            });
        }
    });

    //Passwordトグルボタン
    $('.main').on('click', '.password-toggle-icon', function() {
        $(this).children().toggleClass('fa-eye fa-eye-slash');

        let passwordInput = $(this).prev('.input-wrapper__label').children('input');
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
        } else {
            passwordInput.attr('type', 'password');
        }
    });

    //スマートフォン, タブレットでヘッダーメニューのアイコンをクリックした時の処理
    let headerMenuToggleState = false;
    let innerWidth = window.innerWidth;
    $('.header').on('click', '.header__nav', function(){
        if (headerMenuToggleState === false){
            $(this).find('.header__nav-line').addClass('header__nav-line_open');
            $(this).find('.header__nav-line').next().addClass('header__nav-line_open');
            $(this).find('.header__nav-line').next().next().addClass('header__nav-line_open');
            $('.header__menu').css('display', 'flex');
            headerMenuToggleState = true;
        } else {
            $(this).find('.header__nav-line').removeClass('header__nav-line_open');
            $(this).find('.header__nav-line').next().removeClass('header__nav-line_open');
            $(this).find('.header__nav-line').next().next().removeClass('header__nav-line_open');
            $('.header__menu').hide();
            headerMenuToggleState = false;
        }
    });

    $('body').on('click', function (event) {
        if (!$(event.target).is('.header__menu, .header__link, .header__nav, .header__logo') && headerMenuToggleState) {
            $('.header').find('.header__nav-line').removeClass('header__nav-line_open');
            $('.header').find('.header__nav-line').next().removeClass('header__nav-line_open');
            $('.header').find('.header__nav-line').next().next().removeClass('header__nav-line_open');
            $('.header__menu').hide();
            headerMenuToggleState = false;
        }
    });

    //メニューを開いている最中にウインドウサイズが変更になった時の処理
    $(window).resize(function () {
        if (window.matchMedia('(min-width:961px)').matches) {
            // 処理...
            $('body').removeClass('fixed');
            $('.header__nav').find('.header__nav-line').removeClass('open-header-menu');
            $('.header__nav').find('.header__nav-line').next().removeClass('header__nav-line_open');
            $('.header__nav').find('.header__nav-line').next().next().removeClass('header__nav-line_open');
            $('.header__menu').show();
            headerMenuToggleState = false;
        }

        if (window.matchMedia('(max-width:960px)').matches) {
            $('.header__menu').hide();
            headerMenuToggleState = false;
        }
    });

    /**************************************** index.php, post.php ****************************************/
    if (document.location.pathname === '/' || document.URL.match(/index.php/) || document.URL.match(/post.php/)) {
        let postNavClickState = false;
        let openNavIndex;
        let index;

        $('.main').on('click', '.posts-nav', function() {
            index = $('.posts-nav').index(this);

            if (postNavClickState && openNavIndex === index) {
                $('.posts-nav__menu').hide();
                postNavClickState = false;
                openNavIndex      = undefined;
            } else {
                $('.posts-nav__menu').hide();
                $('.posts-nav__menu').eq(index).css('display', 'flex');
                postNavClickState = true;
                openNavIndex      = index;
            }
        });

        $('body').on('click', function(event) {
            if (!$(event.target).is('.posts-nav__menu, .posts-nav__icon') && postNavClickState) {
                $('.posts-nav__menu').hide();
                openNavIndex      = undefined;
                postNavClickState = false;
            }
        });
    }

    /**************************************** new.php, edit.php ****************************************/
    if (document.URL.match(/new.php/) || document.URL.match(/edit.php/)) {
        //trumbowyg生成
        $('#post-form').trumbowyg({
            lang: 'ja'
        });

        $('.uploaded-images').on('mouseenter', '.uploaded-images__item', function () {
            $(this).children('.uploaded-images__delete').css('display', 'block');
        });

        $('.uploaded-images').on('mouseleave', '.uploaded-images__item', function () {
            $(this).children('.uploaded-images__delete').css('display', 'none');
        });

        //アップロードされた画像をクリック、またはタップした時の処理
        $('.uploaded-images').on('click', '.uploaded-images__img-wrapper', function () {
            //2020年3月17日時点で、スマートフォンにまだ対応していない
            //https://programming.sincoston.com/clipboard-copy/
            $this = $(this);
            let imageSource      = $(this).children('.uploaded-images__img').attr('src');
            let copyFrom         = document.createElement("textarea");
            copyFrom.textContent = imageSource;

            let bodyElm = document.getElementsByTagName('body')[0];
            bodyElm.appendChild(copyFrom);

            copyFrom.select();
            document.execCommand('copy');
            bodyElm.removeChild(copyFrom);

            $(this).parent().addClass('uploaded-images__img-warpper--selection').delay(1000).queue(function () {
                $(this).removeClass('uploaded-images__img-warpper--selection').dequeue();
            });

            $('.message').remove();
            let $divInfo = $('<div></div>');
            $divInfo.addClass('message message--display');
            $divInfo.html('<div class="message__text">画像のURLをコピーしました</div>');
            $('body').prepend($divInfo);
            $divInfo.delay(1000).fadeOut('slow');
        });

        /* ブログのタイトルの値、ブログの本文の値、それぞれの変更前の値を定義 */
        // wysiwygの値に関しては、htmlでの取得が必要
        const CURRENT_BLOG_TITLE_VALUE       = document.getElementsByClassName('wysiwyg__title')[0].value;
        const CURRENT_TROMBOWYG_EDITOR_VALUE = document.getElementsByClassName('trumbowyg-editor')[0].innerHTML;
        const CURRENT_BLOG_TAGS_VALUE        = document.getElementsByClassName('wysiwyg__tags')[0].value;

        //ブログのタイトルの値を変更した時の処理
        $('.main').on('input', '.wysiwyg__title', function () {
            let blogTitleValue       = $(this).val();
            let trumbowygEditorValue = $(this).next().children('.trumbowyg-editor').html();
            let sendButton           = $(this).parent().find('.button');
            let verificationEmpties  = [blogTitleValue, trumbowygEditorValue];
            let isEnabled            = verificationInputValue(verificationEmpties);

            if (document.URL.match(/edit.php/)) {
                if (blogTitleValue === CURRENT_BLOG_TITLE_VALUE &&
                    trumbowygEditorValue === CURRENT_TROMBOWYG_EDITOR_VALUE){
                    isEnabled = false;
                }
            }
            toggleSendButton(isEnabled, sendButton);
        });

        //ブログの本文の値を変更した時の処理
        $('.main').on('input', '.trumbowyg-editor', function () {
            let blogTitleValue       = $(this).parent().prev().val();
            let trumbowygEditorValue = $(this).html();
            let sendButton           = $(this).parent().parent().find('.button');
            let verificationEmpties  = [blogTitleValue, trumbowygEditorValue];
            let isEnabled            = verificationInputValue(verificationEmpties);

            if (document.URL.match(/edit.php/)) {
                if (blogTitleValue === CURRENT_BLOG_TITLE_VALUE &&
                    trumbowygEditorValue === CURRENT_TROMBOWYG_EDITOR_VALUE){
                    isEnabled = false;
                }
            }
            toggleSendButton(isEnabled, sendButton);
        });

        //ブログのタグの値を変更した時の処理
        $('.main').on('input', '.wysiwyg__tags', function () {
            let blogTitleValue       = $(this).parent().children('.wysiwyg__title').val();
            let trumbowygEditorValue = $(this).prev().children(".trumbowyg-editor").html();
            let blogTagsValue        = $(this).val();
            let sendButton           = $(this).parent().parent().find('.button');
            let verificationEmpties  = [blogTitleValue, trumbowygEditorValue];
            let isEnabled = verificationInputValue(verificationEmpties);

            if (document.URL.match(/edit.php/)) {
                if (blogTitleValue === CURRENT_BLOG_TITLE_VALUE &&
                    trumbowygEditorValue === CURRENT_TROMBOWYG_EDITOR_VALUE &&
                    blogTagsValue === CURRENT_BLOG_TAGS_VALUE) {
                    isEnabled = false;
                }
            }
            toggleSendButton(isEnabled, sendButton);
        });

        //年を変更した時の処理
        $('.image-selection__year').children('select').on('change', function () {
            $.ajax({
                url:  'selectionPictureDirectory.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    process: 'yearsChange',
                    token:   $('.image-selection').attr('token'),
                    year:    $('.image-selection__year').children('select').val()
                }
            }).done(function (result) {
                $('.image-selection__month').children('select').empty();
                result.forEach(function (month) {
                    $('.image-selection__month').children('select').append(month);
                });
            }).fail(function () {
                alert('エラーが発生しました。');
            });
        });

        //月を変更した時の処理
        $('.image-selection__month').children('select').on('change', function () {
            $.ajax({
                url:  'selectionPictureDirectory.php',
                type: 'post',
                dataType: 'json',
                data: {
                    process: 'selectionYearAndMonth',
                    token: $('.image-selection').attr('token'),
                    year:  $('.image-selection__year').children('select').val(),
                    month: $('.image-selection__month').children('select').val()
                }
            }).done(function (result) {
                let files = result.files;
                let year  = result.year;
                let month = result.month;
                $('.uploaded-images').attr('year', year);
                $('.uploaded-images').attr('month', month);
                $('.uploaded-images').empty();
                files.forEach(function (image) {
                    $('.uploaded-images').append(image);
                });
            }).fail(function () {
                alert('エラーが発生しました。');
            });
        });

        //uploadボタンをクリックした時の処理
        $('.image-uploader').on('click', '.image-uploader__button', function () {
            $('.image-uploader__file').click();
        });

        //uploadボタンを押して、画像を選択した時の処理
        $('.image-uploader').on('change', '.image-uploader__file', function () {
            let form     = $('.image-uploader').get(0);
            let formData = new FormData(form);

            $.ajax({
                url:         'fileUpload.php',
                type:        'post',
                data:        formData,
                cache:       false,
                processData: false,
                contentType: false,
                dataType:    'json'
            }).done(function (result) {
                let years  = result.years;
                let months = result.months;
                let images = result.files;
                let fileURL = result.fileURL;

                $('.selection-year').children('select').empty();
                $('.selection-month').children('select').empty();
                $('.uploaded-images').empty();

                years.forEach(function (year) {
                    $('.selection-year').children('select').append(year);
                });

                months.forEach(function (month) {
                    $('.selection-month').children('select').append(month);
                });

                images.forEach(function (image) {
                    $('.uploaded-images').append(image);
                });

                $('.image-uploader__file').val('');

                if (window.matchMedia('(max-width:480px)').matches) {
                    let imgCSS = 'max-width: 200px; max-height: 200px; object-fit: contain;';
                    fileURL = '<img src="' + fileURL + '" style="' + imgCSS +'">'
                    $('.trumbowyg-editor').append(fileURL);
                }
            }).fail(function () {
                $('.image-uploader__file').val('');
                alert('失敗しました');
            });
        });

        //削除ボタンをクリックした時の処理
        $('.sidebar').on('click', '.uploaded-images__delete', function () {
            let $div = $(this).closest('.uploaded-images__item');
            $.ajax({
                url: 'fileDelete.php',
                type: 'post',
                data: {
                    token: $('.image-selection').attr('token'),
                    year: $('.uploaded-images').attr('year'),
                    month: $('.uploaded-images').attr('month'),
                    imageID: $(this).prev().children('img').attr('imageID'),
                }
            }).done(function () {
                $div.remove();
            }).fail(function () {
                alert('失敗しました');
            });
        });
    }

    /**************************************** account.php ****************************************/
    if (document.URL.match(/account.php/)) {
        let innerWidth = window.innerWidth;

        $('body').on("click", '.settings-list__link', function () {
            let settingId = $(this).attr('id');
            innerWidth    = window.innerWidth;
            $.ajax({
                url: 'accountEditor.php',
                type: 'post',
                dataType: 'json',
                data: {
                    process: settingId
                }
            }).done(function (result) {
                $('.main__default-message').css('display', 'none');
                $('.main').children('.main__settings').remove();
                $('.main').append(result);
                if (innerWidth < 480) {
                    $('.sidebar').hide();
                }
            }).fail(function () {
                console.log('failed');
            });

            $(this).parents('body').on('click', '.cancel', function () {
                $('body').removeClass('fixed');
                $('.message-box').fadeOut(500);
                logoutButtonClickState = false;
                $('.message-box-text').delay(500).queue(function () {
                    $(this).text('').dequeue();
                });
                $('.send-link .button--enabled:last-child').delay(500).queue(function () {
                    $(this).text('').dequeue();
                });
                $('.message-box').find('.button--enabled:last-child').delay(500).queue(function () {
                    $('.message-box').find('.button--enabled:last-child').removeClass('logout-button');
                    $(this).text('').dequeue();
                });
                $('.message-box').removeClass('account-deactivator');
            });
        });

        $('.main').on('click', '.settings__back', function () {
            $('.main').children('.main__settings').remove();
            if (innerWidth < 480) {
                $('.sidebar').show();
            } else {
                $('.main').children('.main__default-message').show();
            }
        });

        let currentUserName = $('#username').find('.settings-list__content').text();
        let currentEmail    = $('#email').find('.settings-list__content').text();

        $('.main').on('input', '#username-editor .input-wrapper__input', function () {
            let userNameValue       = $(this).val();
            let sendButton          = $(this).parent().parent().parent().find('.button');
            let verificationEmpties = [userNameValue];
            let isEnabled           = userNameValue === currentUserName ? false : true;

            if (isEnabled) {
                isEnabled = verificationInputValue(verificationEmpties);
            }

            toggleSendButton(isEnabled, sendButton);
        });

        $('.main').on('input', '#email-editor .input-wrapper__input', function () {
            let emailValue             = $(this).parent().parent().parent().find('#email-input').val();
            let passwordValue          = $(this).parent().parent().parent().find('#password-input').val();
            let sendButton             = $(this).parent().parent().parent().find('.button');
            let verificationEmpties    = [emailValue, passwordValue];
            let verificationPasswords  = [passwordValue];
            let vertificationEmails    = [emailValue];
            let isEnabled              = emailValue === currentEmail ? false : true;

            if (isEnabled) {
                isEnabled = verificationInputValue(verificationEmpties, verificationPasswords, vertificationEmails);
            }

            toggleSendButton(isEnabled, sendButton);
        });

        $('.main').on('input', '#password-editor .input-wrapper__input', function () {
            let currentPasswordValue      = $(this).parent().parent().parent().children('.settings__input-wrapper').find('.input-wrapper__input').val();
            let newPasswordValue          = $(this).parent().parent().parent().children('.settings__input-wrapper').next().find('.input-wrapper__input').val();
            let passwordConfirmationValue = $(this).parent().parent().parent().children('.settings__input-wrapper').next().next().find('.input-wrapper__input').val();
            let sendButton                = $(this).parent().parent().parent().find('.button');
            let verificationEmpties       = [currentPasswordValue, newPasswordValue, passwordConfirmationValue];
            let verificationPasswords     = [currentPasswordValue, newPasswordValue, passwordConfirmationValue];
            let isEnabled                 = verificationInputValue(verificationEmpties, verificationPasswords);

            toggleSendButton(isEnabled, sendButton);
        });

        $('.main').on('input', '#account-deactivator .input-wrapper__input', function () {
            let passwordValue         = $(this).val();
            let sendButton            = $(this).parent().parent().next('.button');
            let verificationEmpties   = [passwordValue];
            let verificationPasswords = [passwordValue];
            let isEnabled             = verificationInputValue(verificationEmpties, verificationPasswords);

            toggleSendButton(isEnabled, sendButton);
        });

        $('.main').on("click", '#account-deactivator .button', function () {
            let passwordValue = $(this).prev().find('.input-wrapper__input').val();
            let token = $(this).parent().find('.token').attr('token');

            $.ajax({
                url: 'deactivateAccount.php',
                type: 'post',
                dataType: 'json',
                data: {
                    password: passwordValue,
                    token: token
                }
            }).done(function (result) {
                if(result['accept'] === true){
                    $('.message-box__content').append(result["messageBoxContent"]);
                    $('.settings__warning-message').hide();
                    $('body').addClass('fixed');
                    $('.message-box').fadeIn(500);
                } else {
                    failedMessage = 'パスワードが違います。'
                    $('.settings__warning-message').text(failedMessage).show();
                }
            }).fail();
        });
    }

    if (document.URL.match(/login.php/)) {
        $('.main').on('input', '.input-wrapper__input', function() {
            let emailInputValue        = $(this).parents('.unlogged-user-form').children().find('.input-wrapper__input').val();
            let passwordInputValue     = $(this).parents('.unlogged-user-form').children().next().find('.input-wrapper__input').val();
            let sendButton             = $(this).parents('.unlogged-user-form').find('.button');
            let verificationEmpties    = [emailInputValue, passwordInputValue];
            let verificationPasswords  = [passwordInputValue];
            let verficationEmails      = [emailInputValue];
            let isEnabled              = verificationInputValue(verificationEmpties, verificationPasswords, verficationEmails);

            toggleSendButton(isEnabled, sendButton);
        });
    }

    if(document.URL.match(/signUp.php/)){
        $('.main').on('input', '.input-wrapper__input', function(){
            let userNameInputValue             = $(this).parents('.unlogged-user-form').children().find('.input-wrapper__input').val();
            let emailInputValue                = $(this).parents('.unlogged-user-form').children().next().find('.input-wrapper__input').val();
            let emailConfirmationInputValue    = $(this).parents('.unlogged-user-form').children().next().next().find('.input-wrapper__input').val();
            let passwordInputValue             = $(this).parents('.unlogged-user-form').children().next().next().next().find('.input-wrapper__input').val();
            let passwordConfirmationInputValue = $(this).parents('.unlogged-user-form').children().next().next().next().next().find('.input-wrapper__input').val();
            let sendButton                     = $(this).parents('.unlogged-user-form').find('.button');
            let verificationEmpties            = [userNameInputValue, emailInputValue, emailConfirmationInputValue, passwordInputValue, passwordConfirmationInputValue];
            let verificationPasswordValues     = [passwordInputValue, passwordConfirmationInputValue];
            let verificationEmailValues        = [emailInputValue, emailConfirmationInputValue];
            let isEnabled                     = verificationInputValue(verificationEmpties, verificationPasswordValues, verificationEmailValues);

            toggleSendButton(isEnabled, sendButton);
        });
    }

    /**************************************** functions ****************************************/
    //インプット内容を検証してBooleanを返す
    function verificationInputValue(items, passwords, emails) {
        const EMAIL_REGEXP        = /^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@{1}[A-Za-z0-9_.-]{1,}\.[A-Za-z0-9]{1,}$/;
        let verificationPasswords = passwords === undefined ? null : passwords;
        let verificationEmails    = emails    === undefined ? null : emails;

        for (let i = 0; i < items.length; ++i) {
            if (items[i] === '') return false;
        }

        if (verificationPasswords) {
            for (let i = 0; i < verificationPasswords.length; ++i) {
                if (verificationPasswords[i].length < 8) return false;
            }
        }

        if (verificationEmails) {
            for (let i = 0; i < verificationEmails.length; ++i) {
                if (!EMAIL_REGEXP.test(verificationEmails[i])) return false;
            }
        }

        return true;
    }

    //送信ボタンの状態切り替えに関する関数
    function toggleSendButton (isEnabled, sendButton) {
        if (isEnabled) {
            sendButton.removeClass('button--disabled').addClass('button--enabled').prop('disabled', false);
        } else {
            sendButton.removeClass('button--enabled').addClass('button--disabled').prop('disabled', true);
        }
    }
});