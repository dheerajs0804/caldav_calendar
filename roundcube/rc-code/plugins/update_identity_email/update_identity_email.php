<?php

class update_identity_email extends rcube_plugin
{
    private $rc;

    /**
    * Plugin Initialization
    */
    function init()
    {
	$this->rc = rcube::get_instance();
        $this->add_hook('login_after', array($this, 'update_identity_mail'));
    }

    /**
    * Update identity mail after login.
    * Update only if existing identity mail is different from email retrieved from server.
    */
    function update_identity_mail()
    {
	//Get default identity
	$default_identity = $this->get_default_identity();

	//Get id of default identity required for update with new email.
        $default_identity_id = $default_identity['identity_id'];

	//Get mailid from xf server and update identity.
        $email = $this->get_mailid_for_loginid();

	rcube::write_log('errors', "Email Fetched for login id:");
	rcube::write_log('errors', $email);
	
	if ( strcmp($default_identity['email'], $email ) != 0  && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
        {
	    //Set fields in identity to update.
            $identity['email'] = $email;

	    $updated = $this->rc->user->update_identity($default_identity_id, $identity);
	    rcube::write_log('errors', 'Update_response:'.$updated);
        }
	else
	{
	    rcube::write_log('errors', 'Skipped updating email in identity.');	
	}

    }

    /**
    * Get default identity of for logged in user.
    *
    */
    function get_default_identity()
    {	
	rcube::write_log('errors', "DEFAULT IDENTITIY:");
	
	$identity = $this->rc->user->get_identity();
	rcube::write_log('errors', $identity);

	return $identity;
    }  

    /**
    * Get mailid for login id.
    * Login id could be username, email or employeeid
    * This will apply filter in webservice as stated in config file i.e. get_email_filter
    */
    function get_mailid_for_loginid()
    {
	//Response
	$response = array();
	
	//Prepare params for webservice
	$this->prepare_get_mailid();
	
	//Request mailid
	$response = $this->request_get_mailid();
	rcube::write_log('errors', 'RESPONSE::');
	rcube::write_log('errors', $response);

	if ( array_key_exists('users', $response))
	{
	    $email = $response['users'][0]['mail'];
	}
	else
	{
	    rcube::write_log('errors', 'Failed to get email id for login id.');
	    //Sending email null so that it get failed to compare with existing and keep existing identity as it is.
	    $email = NULL;
	}

	return $email;
    }

    /**
    * Function to prepare parameters required to call user webservice based on provided filter.
    *
    */
    function prepare_get_mailid()
    {
        // Read filter,login id and xf server info from config.
	$this->login_id = $this->rc->user->data['username'];
        $this->searchfield = $this->rc->config->get('get_email_searchfield');

        $xf_serverinfo = $this->rc->config->get('xf_directory_server', array());
        $this->adminid = $xf_serverinfo['adminid'];
        $this->adminpass = $xf_serverinfo['adminpass'];
        $this->host = $xf_serverinfo['host'];

	$this->domain = $this->rc->config->get('x-mithi-domain');
    }

    /**
    * Function to call WS to get emailid for login id.
    *
    */
    function request_get_mailid()
    {
	//init request
        $ch = curl_init();

        $URL= $this->host.'/orchestration.ws/domain/'.$this->domain.'/users?properties=mail,username,employeenumber&searchstring='.$this->login_id.'&searchfields='.$this->searchfield.'&filterop=equals';

        rcube::write_log('errors', $URL);

        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");

        // Execute a request
        $result=curl_exec ($ch);

        //Close curl connection
        curl_close ($ch);

        //Decode response in array
        $arr_response = json_decode($result, true);
		
	return $arr_response;
    }
}
?>
