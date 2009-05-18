<?php
require '../src/php/json.hpack.php';
function write($str){
    static  $firstTime = true;
    if($firstTime){
        ob_start();
        ob_implicit_flush(false);
        $firstTime = false;
        write(str_repeat(' ', 1024 * 4));
    }
    echo    $str,
            ob_get_clean();
    flush();
}
function bench($callback, $time = 12){
    $result = new StdClass;
    $result->all = array();
    for(
        $i      = 0,
        $length = $time;
        $i < $length; ++$i
    ){
        $time = microtime(true);
        $callback();
        $time = microtime(true) - $time;
        $result->all[] = $time;
    }
    $result->max = 0;
    foreach($result->all as $time)
        $result->max = max($result->max, $time);
    $result->min = $result->max;
    foreach($result->all as $time)
        $result->min = min($result->min, $time);
    if($length < 3)
        $result->avg = sprintf('%.2f', (($result->max + $result->min) / 2) * 1000);
    else {
        $i = 0;
        while($length)
            $i += $result->all[--$length];
        $i -= $result->max + $result->min;
        $i /= count($result->all) - 2;
        $result->avg = sprintf('%.2f', $i * 1000);
    }
    return $result;
};
function size($bytes){
    static $size = array('bytes', 'Kb', 'Mb', 'Gb');
    $i = 0;
    while(1023 < $bytes){
        $bytes /= 1024;
        ++$i;
    }
    return sprintf('%.2f', $bytes).' '.$size[$i];
}
write(' ');
?>
<!DOCTYPE html>
<!--
/** JSON.hpack simple test suite for PHP
 * @author      Andrea Giammarchi
 * @license     Mit Style License
 */
-->
<html>
    <head>
        <title>JSON.hpack :: Homogeneous Collection Packer</title>
        <style type="text/css">
        div {
            font-family: "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Verdana, Tahoma, sans-serif;
            font-weight: normal;
        }
        * {
            font-size: 9pt;
        }
        pre {
            border-top: 1px dotted silver;
            padding-left: 2px;
            overflow: auto;
        }
        </style>
    </head>
    <body>
        <div>
<?php
$times  = 12;
$a = json_decode(file_get_contents('5000.txt'));
$original = json_encode($a);
write('<h1>JSON test [homogeneous collection length: '.count($a).']</h1>');
$b = bench(create_function('', '$GLOBALS[\'json\']=json_encode($GLOBALS[\'a\']);'), $times);
write('<pre>'.$json.'</pre>');
write('<pre>Array to JSON String           : '.$b->avg.' milliseconds</pre>');
write('<pre>JSON String to Array           : '.bench(create_function('', 'return json_decode($GLOBALS[\'json\']);'), $times)->avg.' milliseconds</pre>');
write('<pre>JSON String length             : '.strlen($json).'</pre>');
write('<pre>JSON String gzip               : '.size(strlen(gzencode($json, 9, FORCE_GZIP))).'</pre>');
$b = bench(create_function('', '$GLOBALS[\'comp\']=json_hbest($GLOBALS[\'a\']);'), $times);
write('<h1>JSON.hpack best compression level is '.$comp.'</h1>');
write('<pre>'.$b->avg.' ms to retrieve the best option (one time operation)</pre>');
for($comp = 0; $comp < 4; ++$comp){
    write('<h1>JSON.hpack compression level '.($comp === 4 ? ' 4 as "best option"' : $comp).' test</h1>');
    $b = bench(create_function('', '$GLOBALS[\'json\']=json_encode(json_hpack($GLOBALS[\'a\'], '.$comp.'));'), $times);
    write('<pre>'.$json.'</pre>');
    write('<pre>Array to JSON String via hpack: '.$b->avg.' milliseconds</pre>');
    $b = bench(create_function('', '$GLOBALS[\'tmp\']=json_hunpack(json_decode($GLOBALS[\'json\']));'), $times);
    write('<pre>JSON String via hpack to Array : '.$b->avg.' milliseconds</pre>');
    write('<pre>JSON String length             : '.strlen($json).'</pre>');
    write('<pre>JSON String gzip               : '.size(strlen(gzencode($json, 9, FORCE_GZIP))).'</pre>');
    write('<pre>were hpack/hunpack reliable    : '.(json_encode($tmp) === $original ? 'true' : 'false').'</pre>');
}
?>
        </div>
    </body>
</html>