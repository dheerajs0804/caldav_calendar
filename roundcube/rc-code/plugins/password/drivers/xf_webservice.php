<?php

/**
 * XF Web service Password Driver
 *
 * Driver for passwords stored in XF server's directory.
 */

class rcube_xf_webservice_password
{
    static private $debug = null;
    private $xf_instance;

    public function save($curpass, $passwd)
    {
	//Get xf instance to set password.
        include_once '/var/www/html/roundcubemail/plugins/xf_directory/xf.php';
        $this->xf_instance = xf2::get_instance();

	//Prepare array for password property
	$password = array( "password" => $passwd );

	//Set user password
	$response = $this->xf_instance->set_xf_user_properties($password);

        rcube::write_log('password_plugin', $response);
	$response_message = $response["operationmsg"];
        rcube::write_log('password_plugin', $response_message);

	//Check response message and return code accordingly.
	if ( strpos($response_message, "The given password is present in your password history" ) !== false ){
		$returncode = PASSWORD_IN_HISTORY;
	}
	elseif( strpos($response_message, "The password should have" ) !== false ){
		$returncode = PASSWORD_CONSTRAINT_VIOLATION;
	}
	elseif( strpos($response_message, "executed successfully" ) !== false ){
		$returncode = PASSWORD_SUCCESS;
	}
	else{
		$returncode = PASSWORD_ERROR;
	}

        return $returncode;
    }
}
