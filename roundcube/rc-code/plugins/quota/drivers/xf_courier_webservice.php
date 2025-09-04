<?php

/**
 * XF Web service Quota Driver
 *
 */


require_once "xf_webservice.php";

class rcube_xf_courier_webservice_quota extends rcube_xf_webservice_quota
{
    private $rcmail;

    /**
    * constructor.
    *
    */
    public function __construct()
    {
        //construct server params for calling quota webservice
	parent::__construct();

    }

    /**
    * Get userquota using xf webservice.
    *
    */
    public function get()
    {
	//No need to get quota in case of courier 
	return;
    }

    /**
    * Function to update quota using xf webservice.
    *
    */
    public function update()
    {
	$response = $this->updateuserquota();
        return $response;
    }

    private function updateuserquota()
    {
	//Prepare request
        $ch = curl_init();

        $URL= $this->host.'/orchestration.ws/server/mail/orchestration/updateusedquotanow.sh?'.$this->user_name.'='.$this->domain;
        rcube::write_log('quota', 'Plugin quota (xf_webservice driver) : update quota request');
        rcube::write_log('quota', $URL);

        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('token: 121asd564dsffg798wer'));

        // Execute a request
        //$result=curl_exec ($ch);
        $response=curl_exec ($ch);

        //Close curl connection
        curl_close ($ch);

        //Decode response
        $response = json_decode($response, true);
        rcube::write_log('quota', 'update quota response:');
        rcube::write_log('quota', $response);

        return json_decode($response['result'], true);
    }
}

