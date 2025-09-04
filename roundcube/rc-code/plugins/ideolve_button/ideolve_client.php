<?php

//require_once (dirname(__FILE__).'/../../../../vendor/autoload.php');

class ideolve_client
{
    private $ideolveuser;
    private $ideolveuserpassword;
    private $ideolveserver;
    private $ideolveClientId;
  	public function __construct($ideolveserver, $user, $pass)
    	{
        	$this->ideolveuser = $user ;
        	$this->ideolveuserpassword = $pass;
        	$this->ideolveserver = $ideolveserver;
     	}
     	public function init()
     	{
        	$RESULT=false;
        	while( true )
        	{	
			$this->ideolveClientId = $this->getGUID(); 

                	$RESULT=true;
               		 break;
        	}
        	return $RESULT ;
      	}

	protected function getGUID()
	{
    		if (function_exists('com_create_guid')){
       	 		return com_create_guid();
    		}else{
        		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        		$charid = strtoupper(md5(uniqid(rand(), true)));
        		$hyphen = chr(45);// "-"
        		$uuid = /*chr(123)// "{"*/""
            			.substr($charid, 0, 8).$hyphen
            			.substr($charid, 8, 4).$hyphen
            			.substr($charid,12, 4).$hyphen
            			.substr($charid,16, 4).$hyphen
            			.substr($charid,20,12);
            			//.chr(125);// "}"
       		 	return $uuid;
    		}
	}
 	/**
     * Re-implements wrapper for all curl functions including support to
     * set OAuth2 bearer tokens in HTTP headers.
     *
     * @param string $url
     * @param array $settings
     * @return array
     */
    protected function curlRequest($url, $settings) {
        $curl = curl_init($url);
        curl_setopt_array($curl, $settings);
        return array(
            curl_exec($curl),
            curl_getinfo($curl),
            curl_errno($curl),
            curl_error($curl),
            curl_close($curl)
        );
    }

	private function parseTokenResponse( $responseStr )
	{
		/*
		Expected response	{"status": "SUCCESS","authtoken": "o5dWVFUwVQIb1Ega","authstatus": 0,"message": "user successfully authenticated"}
		*/
		$response = json_decode($responseStr, true);
		return $response["authtoken"];

	}
	private function getTokenUrl()
	{
               	$randomStr = strtoupper(md5(uniqid(rand(), true)));
		return $this->ideolveserver . "/ideolve.ws/user/".$this->ideolveuser ."/v1/auth?rand=".$randomStr ;
	}
	private function getTokenOptions()
	{
 		// TODO: Lifetime should be less than session time outi
		$fields[ "password" ] = $this->ideolveuserpassword ;
		$fields[ "lifetime" ] = 180;

		$postfields = json_encode( $fields );
		
	        $options = array(
        	    CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json', 
		                "Cache-Control: no-cache",
				'Depth: 0', 
			//TODO: Get a app name for baya	'App-Name: baya4', 
				'App-Name: Mithi-Billing-Invoice',
				'Client-Id:'.$this->ideolveClientId 
			),
	            CURLOPT_POSTFIELDS => $postfields,
        	    CURLOPT_RETURNTRANSFER => true,
	            CURLOPT_TIMEOUT        => 30,
	            CURLOPT_CUSTOMREQUEST  => "POST",
	        );

		return $options;
	}
	public function getClientId()
	{
		return $this->ideolveClientId;
	}
      	public function getIdeolveToken(&$token)
	{
                $RESULT=false;
                while( true )
                {
			
			$result = $this->curlRequest($this->getTokenUrl(), $this->getTokenOptions());
	        	if($result[1]['http_code'] == 200 /*SUCCESS*/) {
				 rcube::write_log('errors', "SUCCESS Request url: ".$this->getTokenUrl());
				 rcube::write_log('errors', "SUCCESS Request options: ".implode(", ",  $this->getTokenOptions() ));
				 rcube::write_log('errors', "SUCCESS Request options headers: ".implode(", ",  $this->getTokenOptions()[CURLOPT_HTTPHEADER] ));
				 rcube::write_log('errors', "SUCCESS Result: ".implode(", ", $result ));
        	    		$token = $this->parseTokenResponse($result[0]);
				 rcube::write_log('errors', "SUCCESS Result token: ".$token);
                	        $RESULT=true;
                        	 break;
			}
			else
			{
				// TODO handle error - ideolve service may be down for upgrade, network may be down ....
				 rcube::write_log('errors', "Request url: ".$this->getTokenUrl());
				 rcube::write_log('errors', "Request options: ".implode(", ",  $this->getTokenOptions() ));
				 rcube::write_log('errors', "Request options headers: ".implode(", ",  $this->getTokenOptions()[CURLOPT_HTTPHEADER] ));
				 rcube::write_log('errors', "Result: ".implode(", ", $result ));
			}
			break; //failed
                }
                return $RESULT ;
        }


	
	public function validateIdeolveToken( $token )
	{
		// simulate a opening of login page with the token
                $RESULT=false;
                while( true )
                {
                        // TODO implement validation
			$RESULT=true;
                        break; 
                }
                return $RESULT ;
	}

}
?>
