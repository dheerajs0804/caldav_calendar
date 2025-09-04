<?php

/**
 * XF Web service Quota Driver
 *
 */

require_once "xf_webservice.php";

class rcube_xf_dovecot_webservice_quota extends rcube_xf_webservice_quota
{

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
	$quota = $this->getuserquota();
        return $quota;
    }

    private function getuserquota()
    {
	//Prepare request
        $ch = curl_init();

        $URL= $this->host.'/orchestration.ws/domain/'.$this->domain.'/user/'.$this->user_name.'/storage/mail/quota';
        rcube::write_log('quota', 'Plugin quota (xf_webservice driver) : get quota request');
        rcube::write_log('quota', $URL);

        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout after 20 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('token: 121asd564dsffg798wer'));

        // Execute a request
        $result=curl_exec ($ch);

        //Close curl connection
        curl_close ($ch);

        //Decode response
        $userquota = json_decode($result, true);	
        rcube::write_log('quota', 'get quota response:');
        rcube::write_log('quota', $userquota);

	//prepare response
        if ( $userquota['returnCode'] == 0 && $userquota['operationMsg'] == 'success' )
        {
                //Format and calculate quota required for plugin
                $quota = $this->format_getquota_result($userquota['result']);
        }
        else
        {
                //Failed to get quota
                $quota['status'] = 'failed';
                $quota['message'] = $result['operationMsg'];
        }

        return $quota;
    }

    /**
    * Function to format quota response reuired by plugin.
    *
    */
    private function format_getquota_result($result)
    {
	//Convert quota from byte to KB
	$total = $this->convert_bytes_to_specified($result['quota']['mailstoreLimit']['storage'], 'K');
	$used = $this->convert_bytes_to_specified($result['quota']['mailstoreUsage']['storage'], 'K');
	
	//In case of unlimited quota keep percent to 0
	if ( $result['quota']['mailstoreLimit']['storage'] == 0 )
	{
	    $percent = 0;
	}
	else
	{
	    $percent = number_format( $used/$total * 100, 2, '.', ''); 
	}

	$quota['total'] = $total;
	$quota['used'] = $used;
	$quota['percent'] = $percent;
	$quota['status'] = 'success';

        rcube::write_log('quota', 'Plugin quota (dovecot_webservice driver) : Quota after formatting:');
        rcube::write_log('quota', $quota);

	return $quota;
    }

    /**
    * Function to convert quota from byte to specified unit.
    *
    */
    private function convert_bytes_to_specified($bytes, $to, $decimal_places = 2) 
    {
	if ( empty($bytes) || $bytes < 0 )
	    return $bytes;

        $formulas = array(
            'K' => number_format($bytes / 1024, $decimal_places, '.', ''),
            'M' => number_format($bytes / 1048576, $decimal_places, '.', ''),
            'G' => number_format($bytes / 1073741824, $decimal_places, '.', '')
        );

        return isset($formulas[$to]) ? $formulas[$to] : 0;
    }

    /**
    * Function to update quota using xf webservice.
    *
    */
    public function update()
    {
	//Quota get updated automatically in dovecot
	//So, we disable update quota button in case of dovecot-imap
        return;
    }
}

