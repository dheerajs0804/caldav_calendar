<?php

// From URL to get webpage contents.
$url = "https://bimi.progist.net/ktkbank_com/52b5e606-9309-494b-9498-901c1eac3c34KTKbank.svg";

echo $url ;

// Initialize a CURL session.
$ch = curl_init();

// Return Page contents.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

//grab URL and pass it to the variable.
curl_setopt($ch, CURLOPT_URL, $url);

$result = curl_exec($ch);

echo $result;

?>

