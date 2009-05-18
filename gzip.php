<?php
if(isset($_POST['data'])){
    $time = microtime(true);
    $length = gzencode($_POST['data'], 9, FORCE_GZIP);
    $time = microtime(true) - $time;
    header('X-Served-In: '.$time);
    exit(''.strlen($length));
}
?>