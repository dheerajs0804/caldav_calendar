<?php

class user_properties
{
    private $adminid;
    private $adminpass;
    private $host;
    private $user_name;
    private $domain;
    private $properties;
    
    public function __construct()
    {
	 //construct server params for calling webservice
         $rcmail = rcmail::get_instance();

	 $dir_serverinfo = $rcmail->config->get('xf_directory_server', array());
	 $this->adminid = $dir_serverinfo['adminid'];
	 $this->adminpass = $dir_serverinfo['adminpass'];
	 $this->host = $dir_serverinfo['host'];

	 //construct user properties
	 $this->properties = $rcmail->config->get('userproperties');

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
                 rcube::write_log('errors', 'Plugin xf_directory (webservice::userproperties) : $config[\'username_domain\'] is not defined.');
             }
	     $this->user_name = $username;
             $this->domain = $domain;
         }
    }

    public function get()
    {
        $result = $this->getuserproperties();
        return $result;
    }

    public function set($properties)
    {
	$status = $this->setuserproperties($properties);
	return $status;
    }

    private function getuserproperties()
    {
	//Prepare request
        $ch = curl_init();

        $URL= $this->host.'/orchestration.ws/domain/'.$this->domain.'/user/'.$this->user_name.'?properties='.$this->properties;

        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");
        
	// Execute a request
        $result=curl_exec ($ch);
        
        //Close curl connection
        curl_close ($ch);
	
	//Decode response
	$userproperties = json_decode($result, true);
	return $userproperties['result'];
    }

    private function setuserproperties($properties)
    {
        //Prepare request 
        $ch = curl_init();
        $url = $this->host.'/orchestration.ws/domain/'.$this->domain.'/user/'.$this->user_name.'?&op=replace';


        // Convert json string to json object
        $post_data = json_encode($properties,true);

        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
               'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");

        //Execute request
        $result = curl_exec($ch);
        //var_dump($result);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        // Close curl connection
        curl_close ($ch);
	
	//Decode response
	$status = json_decode($result, true);
	$status['Status Code'] = $status_code;

	return $status;
    }

}
?>
