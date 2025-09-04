<?php

class xf_access_control 
{
    private $adminid;
    private $adminpass;
    private $host;
    private $user_name;
    private $domain;
    private $prop_usercos_access_control;
    private $clientip;
    private $is_access_allowed;

    public function __construct($clientip)
    {
         //construct server params for calling usercos webservice
         $rcmail = rcmail::get_instance();

         $dir_serverinfo = $rcmail->config->get('xf_directory_server', array());
         $this->adminid = $dir_serverinfo['adminid'];
         $this->adminpass = $dir_serverinfo['adminpass'];
         $this->host = $dir_serverinfo['host'];

         //construct user and domain
         $username = $rcmail->user->data['username'];
         if (strstr($username, '@')){
             $temparr = explode('@', $username);
             $this->user_name = $temparr[0];
             $this->domain = $temparr[1];
         }
         elseif ( $identity = $rcmail->user->get_identity() )
         {
             list($name,$domain) = explode('@', $identity['email']);
             $this->user_name = $name;
             $this->domain = $domain;
         }
         else
         {
             $domain = $rcmail->config->get('username_domain', false);
             if (!$domain)
             {
                 rcube::write_log('access_control', 'Plugin access_control  : $config[\'username_domain\'] is not defined.');
             }
             $this->user_name = $username;
             $this->domain = $domain;
         }

	 //Client ip
	 $this->clientip = $clientip;

	 //Default access is true.
	 $this->is_access_allowed = true;
    }


    public function get_access_control()
    {
        rcube::write_log('access_control', "Get access control for user: ".$this->user_name );

	$access = $this->check_access();
        return $access;
    }

    private function check_access()
    {
	//Get xf instance
        include_once '/var/www/html/skyconnect/plugins/xf_directory/xf.php';
        $xf_instance = xf::get_instance();

	$user_properties = $xf_instance->get_xf_user_properties(array('classofservice'));
        rcube::write_log('access_control', "User's cos:" );
        rcube::write_log('access_control', $user_properties );
	$classofservice = $user_properties['classofservice'];

	if($classofservice)
 	{
	    //Get usercos properties for access control
	    $this->prop_usercos_access_control = $this->getusercosproperties($classofservice);
	    
	    if( $this->prop_usercos_access_control['status'] == "success" )
	    {
		$mailclientaccess = $this->prop_usercos_access_control['mailclientaccess'];
		$mailclientip = $this->prop_usercos_access_control['mailclientip'];
		
		switch($mailclientaccess)
		{
		    case "nocheck":
        			rcube::write_log('access_control', "mailclient access:".$mailclientaccess);
				break;

		    case "block":
        			rcube::write_log('access_control', "mailclient access:".$mailclientaccess);
				$this->is_access_allowed = false;
				break;

		    case "check":
        			rcube::write_log('access_control', "mailclient access:".$mailclientaccess);
				$this->is_access_allowed = $this->check_mailclientip_range($mailclientip);
				break;

		    default:
        			rcube::write_log('access_control', "mailclient access: default case");
				break;
		}
	    }
	    
	}

	return $this->is_access_allowed;		
    }

    private function getusercosproperties($usercos)
    {
        //Prepare request
        $ch = curl_init();

        $URL=$this->host.'/orchestration.ws/domain/'.$this->domain.'/usercoslist?searchstring='.$usercos.'&searchFields=usercosname&filterop=equals&properties=usercosname,mailclientaccess,mailclientip';
        rcube::write_log('access_control', "Get usercos access control url:" );
        rcube::write_log('access_control', $URL );

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

        rcube::write_log('access_control', 'Usercos access control properties response::');
        rcube::write_log('access_control', $response );

        //prepare response
        if ( $response['returncode'] == 0 && $response['status'] == 'success' )
        {
                //Return success response
                $usercos_prop['status'] = 'success';
                $usercos_prop['mailclientaccess'] = $response['usercos'][0]['mailclientaccess'];
                $usercos_prop['mailclientip'] = $response['usercos'][0]['mailclientip'];

        }
        else
        {
                //Failed to get redirect url
                $usercos_prop['status'] = 'failed';
        }


        rcube::write_log('access_control', 'Usercos access control properties prepared response::' );
        rcube::write_log('access_control', $usercos_prop );

        return $usercos_prop;
    }

    private function check_mailclientip_range($mailclientip)
    {
	
        rcube::write_log('access_control', 'check mailclient ip range' );
	$access = false;

        //Include ip check helper class
        include_once '/var/www/html/skyconnect/plugins/access_control/helper/IPRange.php';
        $oIPRange = new IPRange();


	//Check input is array or not
	//In case of single allowed range/ip web service gives string response for mailclientip
	//In case of multiple allowed range/ip web service gives array response for mailclientip
	if (!is_array($mailclientip))
	    $mailclientip_array = explode('**', $mailclientip);
	else
	    $mailclientip_array = $mailclientip;

	foreach ($mailclientip_array as $allowedip)
	{
	    $cidr = $oIPRange->convert_range_to_cidr($allowedip);
       	    rcube::write_log('access_control', "Allowed IP/range:: ".$cidr );

	    if($oIPRange->ipCheck($this->clientip, $cidr))
	    {
		$access = true;
		break;
	    }	    
	}
        rcube::write_log('access_control', "Client IP:: ".$this->clientip );
 		
	return $access;
    }	

}
?>
