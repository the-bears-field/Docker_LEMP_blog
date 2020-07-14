<?php
mb_language("Japanese");
mb_internal_encoding("UTF-8");

$headers = 'From: thebearsfield@gmail.com'. "\n" .
           'Reply-To: thebearsfield@gmail.com'. "\n";

if(mb_send_mail("thebearsfield@gmail.com", "test", "text", $headers)){
    echo 'success';
} else {
    echo 'failed';
}