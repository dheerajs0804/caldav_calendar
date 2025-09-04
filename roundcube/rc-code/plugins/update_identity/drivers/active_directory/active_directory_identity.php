<?php

class active_directory_identity
{
    /**
    * Get identity for login id.
    * Login id could be email or employeeid
    * This will apply filter in webservice as stated in config file
    */
    function get_identity()
    {
	//TODO: Check identity in memcache.

	
	//If not found then do following
	//Prepare params for webservice
	$this->prepare_get_identity();
	
	//Request identity
	$response = $this->request_get_identity();
	rcube::write_log('errors', 'RESPONSE::');
	rcube::write_log('errors', $response);

	if ( !empty($response) && !array_key_exists('error',$response) )
	{
	    //Change keys from response according to identity_attribute_mapping
	    foreach ($this->identity_attribute_mapping as $dir_key => $identity_key) {
 		$response[$identity_key] = $response[$dir_key];
		unset($response[$dir_key]);
	    }
	
	    //TODO:Update identity in memcache     
	    //Update identity with response that is formed in identity syntax.
	    $identity = $response;
	}
	else
	{
	    rcube::write_log('errors', 'Failed to get identity for login id.');
	    //Sending identity null to keep existing identity as it is.
	    $identity = NULL;
	}

	return $identity;
    }

    /**
    * Function to prepare parameters required to call addict api based on provided filter.
    */
    function prepare_get_identity()
    {
	$this->rc = rcube::get_instance();
        // Read filter,login id and xf server info from config.
	$this->login_id = $this->rc->user->data['username'];
        //$this->searchfield = $this->rc->config->get('get_identity_filter');
        $this->identity_fields = $this->rc->config->get('identity_fields');
	$this->identity_attribute_mapping = $this->rc->config->get('identity_attribute_mapping', array());

        $identity_server_info = $this->rc->config->get('identity_server', array());
        $this->host = $identity_server_info['host'];
    }

    /**
    * Function to call WS to get identity for login id.
    */
    function request_get_identity()
    {
	//init request
        $ch = curl_init();
	$arr_response = array();
	
	//In case of active directory identity should filter on 'sAMAccountName' only.
	//So user gets login by sAMAccountName only.
        $URL= $this->host. '/user/' .$this->login_id. '?_fields=' .$this->identity_fields;
        rcube::write_log('errors', $URL);

        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout after 20 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

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
