<?
require 'conf.php';
header( 'Content-type: text/html; charset=utf-8' );
echo "<html>";
$t = new ctwitter_stream();

$t->login('...', '...', '...', '...');

$t->start(array('facebook', 'fbook', 'fb'));

?>