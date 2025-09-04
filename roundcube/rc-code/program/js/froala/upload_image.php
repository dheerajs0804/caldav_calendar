<?php

	// Generate response.
        $response = new StdClass;
	$response->name = $_FILES["file"]["name"];

        echo stripslashes(json_encode($response));

?>
