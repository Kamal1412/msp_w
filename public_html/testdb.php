<?php
	echo __LINE__;
$link = mysqli_connect('localhost', 'root', '') ;
echo __LINE__;
if (!$link) {
die('Could not connect: ' . mysql_error());
}
echo 'Connected successfully';
mysql_close($link);
?>