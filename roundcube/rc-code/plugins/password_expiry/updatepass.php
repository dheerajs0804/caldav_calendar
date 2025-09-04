<?php

	///get values from form
	$currentpass =  $_POST['currentpass'];
	$newpass =  $_POST['newpass'];
        $confirmpass =  $_POST['confirmpass'];
	$usercaptcha =  $_POST['usercaptcha'];
	$emailid = $_POST['mailid'];


	///get host name from cookies, to call WS
        $host = $_COOKIE['host'];
        $serverhost = $_COOKIE['serverhost'];
	$redircthost = $_SERVER['SERVER_NAME'];

	///check if all required values exits
        if($currentPass=="" && $newPass=="" && $confirmPass=="" && $host=="" && $emailid=="" && $usercaptcha==""){
                header("Location: https://$redircthost");
        }else{
		include 'DBConnection.php';
 
		$db = new DBConnection();
		$userid = $emailid;
		$data = $db->query("select captcha,timestamp from bayav4_update_pass where userid = '".$userid."' order by timestamp desc limit 1;");
		$timestamp = "";
		$captcha = "";
		if ($data) {
	   		foreach ($data as $data) {
				$timestamp =  $data['timestamp'];
				$captcha = $data['captcha'];
	   		}
        	}

		$currentDate = strtotime($timestamp);
		$futureDate = $currentDate+(60*5);
		$timestamp = date("Y-m-d H:i:s", $futureDate);	

		//$timezone = date_default_timezone_get();
		//$date = date('Y-m-d H:i:s');
		$date = gmdate("Y-m-d H:i:s");

		if(($timestamp >= $date) == 1 && $captcha == $usercaptcha){
				
			///decrypt passwords
	  		$currentPass = decryptPassword($currentpass);
          		$newPass = decryptPassword($newpass);
          		$confirmPass = decryptPassword($confirmpass);

			//call WS and update user pass
        		$WSResponse = updatePass($currentPass,$newPass,$confirmPass,$host,$emailid);

        		$msg = renderWSResponse($WSResponse);
 
	  		echo ($msg);     
		
		}else if($captcha != $usercaptcha){
			echo("Wrong Captcha, Please retry again");
		}
     		else{
	  		echo("Captcha Expired, Please retry again.");
		}
	
       		$db = null;
		
	}

    function decryptPassword($password)
    {
	$private_key_path = "rsa_1024_priv.pem";
	$private_key = file_get_contents($private_key_path);
	$encrypted_pass = $password;
        $decrypted_pass = '';

        if (openssl_private_decrypt(base64_decode($encrypted_pass), $decrypted, $private_key))
        {
            $decrypted_pass = $decrypted;
        }
	//echo ($decrypted_pass."<br>");
        return $decrypted_pass;
     }

     function updatePass($currentPass,$newPass,$confirmPass,$host,$emailid){

	///split username and domain
	 list($name,$domain) = explode('@', $emailid);
         $user = $name;
         $domain = $domain;

	$url = "http://".$host.":8080/orchestration.ws/domain/".$domain."/user/".$user."?&op=replace";
        $post = '{"password":"'.$newPass.'"}';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$emailid:$currentPass");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);
	$httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

	if($httpcode!=200){
		echo "Password failed to update.";
	}

	return $response;

     }

     function renderWSResponse($WSResponse){
	
	$decodejson = json_decode($WSResponse);
	$status = $decodejson->status;
	$returncode = $decodejson->returncode;
	
	if ($status == "error" && $returncode == 1) {
		return "Password failed to update.";
	}
	else if($status == "warning" && $returncode == 2) {
		return "Password failed to update.";
	}
	else if($status == "success" && $returncode == 0) {
		return "Password updated successfully.";
	}else{
		return "Password failed to update.";
	}
    }


?>
