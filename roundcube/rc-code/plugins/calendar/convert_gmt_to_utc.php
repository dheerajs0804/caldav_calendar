<?php

$gmttime=$argv[1];
$offset=$argv[2];

$tz = new DateTimeZone('UTC');

$date = new DateTime($gmttime, new DateTimeZone($offset));
$date->setTimezone($tz);
echo $date->format('Ymd\THis\Z');

?>
