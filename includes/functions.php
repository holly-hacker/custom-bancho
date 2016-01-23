<?php

$replayLocation = $_SERVER['DOCUMENT_ROOT'].'/web/replay/';

//contains functions used by multiple scripts
function HashWithSalt($md5, $salt) {
  return md5($md5.'super secret value here'.md5($salt));
}
/* random string */
function RandomString( $length ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $size = strlen( $chars );
    $str = '';
    for( $i = 0; $i < $length; $i++ ) {
        $str .= $chars[ rand( 0, $size - 1 ) ];
    }
    return $str;
}
 ?>
