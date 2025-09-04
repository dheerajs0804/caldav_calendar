<?php

list($first,$secound,$thrid) = explode('.', $_SERVER[HTTP_HOST]);
$hostincookie = ".".$secound.".".$thrid;

setcookie("roundcube_sessauth", "-del-", time() - 60,"/",$hostincookie,"","true");

?>
