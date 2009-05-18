<?php
$time = microtime(true);
$cl = '
/** version 1.0 2009-05-14
 * @author      Andrea Giammarchi
 * @license     Mit Style License
 * @project     http://code.google.com/p/hpack/
 */
';
$name = 'json.hpack';
$output = array(
    'json.hpack'
);
// -- simple build
$file = 'build/'.$name.'.js';
$filemin = str_replace('.js', '.min.js', $file);
if(!function_exists('file_put_contents')){
    function file_put_contents($file, $content){
        $fp = fopen($file, 'wb');
        fwrite($fp, $content);
        fclose($fp);
    }
}
$nl = "\n";
foreach($output as $key => $value)
    $output[$key] = file_get_contents('src/javascript/'.$value.'.js');
$output = implode($nl, $output);
$output = ($cl=trim($cl)).$nl.$nl.$output;
if(file_exists($file))
    unlink($file);
if(file_exists($filemin))
    unlink($filemin);
file_put_contents($file, $output);
exec('java -jar yuicompressor-2.4.2.jar --nomunge '.$file.' -o '.$filemin);
$min = $cl.file_get_contents($filemin);
file_put_contents($filemin, $min);
ob_start('ob_gzhandler');
header('Content-Type: text/javascript');
header('X-Served-In: '.(microtime(true) - $time));
exit($min);
?>