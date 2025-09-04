<?php

class skyconnect_identity
{

    // rcube_cache instance for memcache
    private $mem_cache;

    /**
    * Update identity after login.
    */
    function get_identity()
    {
	//Prepare params for webservice
        $this->prepare_get_identity();

	//Get identity from skyconnect server.
        $response = $this->request_get_identity();
	rcube::write_log('errors', 'RESPONSE::');
        rcube::write_log('errors', $response);

	if ( !empty($response) )
        {
            //Change keys from response according to identity_attribute_mapping
            foreach ($this->identity_attribute_mapping as $dir_key => $identity_key) {
                $response[$identity_key] = $response[$dir_key];
                unset($response[$dir_key]);
            }

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
    * Function to prepare parameters required to call user webservice based on provided filter.
    *
    */
    function prepare_get_identity()
    {
        $this->rc = rcube::get_instance();

        // Read filter,login id and xf server info from config.
	$this->login_id = $this->rc->user->data['username'];
	$this->identity_fields = $this->rc->config->get('identity_fields');
        $this->searchfield = $this->rc->config->get('get_identity_filter');
        $this->identity_attribute_mapping = $this->rc->config->get('identity_attribute_mapping', array());

        $xf_serverinfo = $this->rc->config->get('xf_directory_server', array());
        $this->adminid = $xf_serverinfo['adminid'];
        $this->adminpass = $xf_serverinfo['adminpass'];
        $this->host = $xf_serverinfo['host'];

	$temparr = explode('@', $this->login_id);
	$this->domain = $temparr[1];
	
//	$this->domain = $this->rc->config->get('x-mithi-domain');
    }

    /**
    * Function to call WS to get emailid for login id.
    *
    */
    function request_get_identity()
    {
	//init request
        $ch = curl_init();

        $URL= $this->host. '/orchestration.ws/domain/' .$this->domain. '/users?properties=' .$this->identity_fields. '&searchstring=' .$this->login_id. '&searchfields=' .$this->searchfield. '&filterop=equals';

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

	if ( array_key_exists('users', $arr_response))
        {
            $response = $arr_response['users'][0];
        }
	else
	{
	    //If identity not found for user, send response as NULL to skip identity updation
	    $response = NULL;
	}
		
	return $response;
    }
}
?>

