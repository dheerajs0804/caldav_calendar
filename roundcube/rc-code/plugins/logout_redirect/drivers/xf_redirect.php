<?php

/**
 * XF Web service get redirect url for domain from xf directory
 *
 */

require_once "redirect_base.php";

class xf_redirect extends redirect_base 
{
    private $rcmail;
    private $domain;

    /**
    * constructor.
    *
    */
    public function __construct($domain)
    {
	$this->domain = $domain;
 
        //construct server params for calling getting redirect url from xf directory
	parent::__construct();
    }

    /**
    * Get redirect url using xf webservice.
    *
    */
    public function get()
    {
    	$response = $this->get_redirect_url();
        return $response;
    }

    private function get_redirect_url()
    {
        //Prepare request
        $ch = curl_init();

        $URL=$this->host.'/orchestration.ws/domain/'.$this->domain.'?properties=mailclientloginlogoutpath&absolutevalues=false';
	rcube::write_log('logout_redirect', $URL );	

        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");

        // Execute a request
        $result=curl_exec ($ch);

        //Close curl connection
        curl_close ($ch);

        //Decode response and return properties 
        $response = json_decode($result, true);

	rcube::write_log('logout_redirect', 'RESPONSE::' );	
	rcube::write_log('logout_redirect', $response );	

	//prepare response
        if ( $response['returncode'] == 0 && $response['operationmsg'] == 'success' )
        {
                //Return success response
                $redirecturl['status'] = 'success';
                $redirecturl['url'] = $response['result']['mailclientloginlogoutpath'];
		
        }
        else
        {
                //Failed to get redirect url
                $redirecturl['status'] = 'failed';
        }


        return $redirecturl;
    }
}

