<?php

/**
*
* Password Expiry
*
* plugin to show password expired error message when password is expired
*
* @version 1.0
*
*/

class password_expiry extends rcube_plugin
{
    public $task = 'login|logout';
    public $noframe = true;
    private $rcmail;
    private $debug_flag;
    private $forgot_pass_server;
    private $usernametobepassed;

    private $adminid;
    private $adminpass;
    private $host;
    private $user_name;
    private $domain;
    private $properties;

    // constructor
    public function init()
    {
	$this->load_config();
        $this->rcmail = rcmail::get_instance(); // create instance to be used in entire class
        $this->debug_flag = $this->rcmail->config->get('pass_exp_debug'); // get debug flag - enable/disable debugging
        $this->pe_write_log('password_expiry - START'); // debug message, writes to roundcube/logs/password_expiry

        $this->appkey = $this->rcmail->config->get('appkey');
        $this->secretkey = $this->rcmail->config->get('secretkey');

	$dir_serverinfo = $this->rcmail->config->get('xf_directory_server', array());
        $this->adminid = $dir_serverinfo['adminid'];
        $this->adminpass = $dir_serverinfo['adminpass'];
        $this->host = $dir_serverinfo['host'];

	//construct user properties
        $this->properties = $this->rcmail->config->get('userproperties');
	$this->pe_write_log('Properties : '.$this->properties);

	//construct user and domain
        $username = $this->rcmail->user->data['username'];
        if (strstr($username, '@')){
                $temparr = explode('@', $username);
                $this->user_name = $temparr[0];
                $this->domain = $temparr[1];
        }
        elseif ( $identity = $this->rcmail->user->get_identity() )
        {
                list($name,$domain) = explode('@', $identity['email']);
                $this->user_name = $name;
                $this->domain = $domain;
        }
        else
        {
                $domain = $this->rcmail->config->get('username_domain', false);
                if (!$domain)
                {
                        rcube::write_log('errors', 'Plugin xf_directory (webservice::userproperties) : $config[\'username_domain\'] is not defined.');
                }
                $this->user_name = $username;
                $this->domain = $domain;
        }


        if (empty($this->appkey) || empty($this->secretkey))
        {
            $this->pe_write_log('Either appkey or secretkey is empty, please check the config.inc.php file whether keys are present.');
            rcube::raise_error('Either appkey or secretkey is empty, please check the config.inc.php file whether keys are present.');
        }

        $this->add_hook('authenticate', array($this, 'password_expiry_check')); // connecting fn to authenticate hook
	$this->include_script('jsencrypt.min.js');
    	//$this->include_script('https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js');
    }

    // main function to check password expiry
    public function password_expiry_check($args)
    {
        $this->pe_write_log('password_expiry_check - START'); // debug message, writes to roundcube/logs/password_expiry

        $this->construct_user_domain($args); // obtain user and domain to pass to web service

        // call web service and check if password is expired
        $int_response = $this->is_password_expired($args);
        
        // password expired
        switch ($int_response) 
        {
            case 114:
                // password expired, give error message
                $this->pe_write_log('***Password EXPIRED.***');
                rcube::raise_error('***Password EXPIRED.***');
	
		$orgusername = $args['user'];
                //check username contains domain
                if (strstr($orgusername, '@')){

                        $this->usernametobepassed = $orgusername;

                }
                elseif ( $identity = $this->rcmail->user->get_identity() ){

                        list($name,$domain) = explode('@', $identity['email']);
                        $user_name = $name;
                        $domain = $domain;

                        $this->usernametobepassed = "$user_name"."@"."$domain";

                }
                else{
                        $domain = $this->rcmail->config->get('username_domain', false);
                        if (!$domain)
                        {
                                $this->pe_write_log('Plugin password_expiry  : $config[\'username_domain\'] is not defined.');
                        }

                        $user_name = $orgusername;
                        $domain = $domain;

                        $this->usernametobepassed = "$user_name"."@"."$domain";
                }

		$this->pe_write_log($user_name);
		$this->pe_write_log($domain);
		$user_pass = $args['pass'];

		$currenthost = $args['host'] ;
		$serverhost = $_SERVER['SERVER_NAME'];

		///check users password is valid using ldap auth

		$ldap_host = "ldap://".$currenthost;

        	$ldap = ldap_connect($ldap_host);

        	ldap_set_option($ldap,LDAP_OPT_PROTOCOL_VERSION,3);
        	ldap_set_option($ldap,LDAP_OPT_REFERRALS,0);

		$result = $this->getuserproperties();
		$userid = $result['mail'];
	
		//$userdn = "mail=".$this->usernametobepassed.",dc=addressbook";
		$userdn = "mail=".$userid.",dc=addressbook";
        	// verify user and password
        	$bind = @ldap_bind($ldap, $userdn, $user_pass);

		$this->pe_write_log($bind);

		if($bind){

                	//set username into cookies to be used internally
                	setcookie("username", "$this->usernametobepassed", time() + (60 * 5), "/");
			setcookie("host", "$currenthost", time() + (60 * 5), "/");
			setcookie("serverhost", "$serverhost", time() + (60 * 5), "/");

			//load js file
			$this->include_script('password_expiry.js');
                
			// set abort to true to stop the authentication process
                	$args['abort'] = 1;
                	// display error message to user and redirect to login page

			$client_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];

                	$this->rcmail->output->set_env('task', 'login');
                	$this->rcmail->output->send('login');
                	return $args;
		}else{
			$args['abort'] = 1;
		
			$this->pe_write_log("ldap auth failed for user");	
			rcube::raise_error('Login Failed');
		
			return $args;
		}
            
            case 0:
                // password not expired, continue normally - success
                $this->pe_write_log('Password NOT EXPIRED.');
                break;

            case 7:
                // request authentication failure
                $this->pe_write_log('***Request authentication failure***');
            
            default:
                // server error
                $this->pe_write_log('***Internal server error - ' . $int_response . ' ***');
                rcube::raise_error('Password Expiry - Internal server error - ' . $int_response);

                // set abort to true to stop the authentication process
                $args['abort'] = 1;
                // display error message to user and redirect to login page
                $this->rcmail->output->show_message('Internal server error - Please refresh page and try again.', 'error');
                $this->rcmail->output->set_env('task', 'login');
                $this->rcmail->output->send('login');
                return $args;
        }
        
        $this->pe_write_log('password_expiry_check - END');
    }

    private function getuserproperties()
    {
        //Prepare request
        $ch = curl_init();

        $URL= $this->host.'/orchestration.ws/domain/'.$this->domain.'/user/'.$this->user_name.'?properties='.$this->properties;
	$this->pe_write_log('URL : '.$URL);
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
	$this->pe_write_log('Userprop : '.$userproperties['result']);
        return $userproperties['result'];
    }

    // get request to web service
    private function is_password_expired($args)
    {
        $this->pe_write_log('is_password_expired - START');

        //Prepare request
        $ch = curl_init();
        $int_response = 0; // default is 0 for success - pass not expired
        $host = $args['host'];
	#$dir_serverinfo_from_conf = $this->rcmail->config->get('xf_directory_server', array());
	#$securehost = $dir_serverinfo_from_conf['securehost'];
	#list($protocol,$hostname) = explode('//', $securehost);
	#$host = $hostname;

        $url = "https://" . $host . "/connectxf.ws/domain/" . $this->domain . "/user/" . $this->user_name
         . "/service/imap/passwordexpirycheck";

        $this->pe_write_log('url: ' . $url);

        // set curl options
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout after 3 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "appkey: $this->appkey",
            "secretkey: $this->secretkey"
        ));

        // Execute a request
        $result = curl_exec ($ch);
        $this->pe_write_log('result: ' . $result);

        //Close curl connection
        curl_close ($ch);

        //Decode response
        $response = json_decode($result, true);
        $this->pe_write_log('response: ' . print_r($response, true));

        // check if JSON is valid
        if (json_last_error() === JSON_ERROR_NONE && isset($response['isPasswordExpired'])) 
        {
            $int_response = $response['isPasswordExpired']; // either 0 (success - pass not expired) or 114 (password expired)
        }
        else
        {
            $this->pe_write_log('JSON decode error - ' . json_last_error_msg());
            rcube::raise_error('JSON decode error - ' . json_last_error_msg());
            $int_response = 1; // error code
        }
        
        $this->pe_write_log('returning int_response - ' . $int_response);
        $this->pe_write_log('is_password_expired - END');

        return $int_response;
    }

    // construct user and domain
    private function construct_user_domain($args)
    {
        $this->pe_write_log('construct_user_domain - START');

        $username = $args['user'];
        // separate username and domain at @
        if (strstr($username, '@')){
            $temparr = explode('@', $username);
            $this->user_name = $temparr[0];
            $this->domain = $temparr[1];
        }
        elseif ( $identity = $this->rcmail->user->get_identity() )
        {
            list($name,$domain) = explode('@', $identity['email']);
            $this->user_name = $name;
            $this->domain = $domain;
        }
        else
        {
            $domain = $this->rcmail->config->get('username_domain', false);
            if (!$domain)
            {
                $this->pe_write_log('Plugin password_expiry  : $config[\'username_domain\'] is not defined.');
            }
            $this->user_name = $username;
            $this->domain = $domain;
        }

        $this->pe_write_log('user_name: ' . $this->user_name . ' - domain: ' . $this->domain);
        $this->pe_write_log('construct_user_domain - END');
    }

    // function for writing logs after checking if debug flag is set
    // enable disable logging in config.inc.php from config folder
    private function pe_write_log($log)
    {
        if ($this->debug_flag)
        {
            rcube::write_log('password_expiry', $log);
        }
    }
}
