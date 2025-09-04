<?php

class forgot_password  extends rcube_plugin
{
    // On task 'login/logout'
    public $task = 'login|logout';

    private $rc;
    private $forgot_pass_server;
    private $adminid;
    private $adminpass;
    private $host;
    private $domain;
    private $is_forgotpass_enabled;
    private $forgot_password_link;
    private $clientredirecturl;
    static private $debug = null;
                        
    function init()
    {	
        $this->rc = rcube::get_instance();

	//Read required configurations required for forgot password link
        $this->forgot_pass_server = $this->rc->config->get('forgot_password_server');
	$this->clientredirecturl = $this->rc->config->get('client_redirect_url');
	//$dir_serverinfo = $this->rc->config->get('xf_directory_server', array());
        //$this->adminid = $dir_serverinfo['adminid'];
        //$this->adminpass = $dir_serverinfo['adminpass'];
        //$this->host = $dir_serverinfo['host'];
	//Read domain from url
	//$this->domain = $_GET['domain'];
	//Check for debugging
	if(self::$debug === null) {
            self::$debug = $this->rc->config->get('forgot_password_debug');
        }

	//Check if domain and forgot password server is present or not.
	if ( !empty($this->forgot_pass_server) )
        {
            //Add hook to get loginform template
            $this->add_hook('template_object_loginform', array($this, 'add_forgot_password_link'));
	}
	else
	{
	    self::debug_log("Skipped forgot password since domain or forgot_pass_server not provided.");
	}

	//Handle forgot pass messages
	if ( isset($_GET["code"]))
	{
	    switch($_GET["code"]){
		case 10:
			$this->rc->output->show_message('Session found invalid.', 'warning');
			break;
		case 35:
			$this->rc->output->show_message('Password Changed Successfully', 'warning');
                        break;
	    }
	}
    }

    function add_forgot_password_link($arg)
    {
	//Check forgot password is enabled for prepared domain.
	//$this->is_forgotpass_enabled = $this->get_forgotpass_property();
	//if ( !empty($this->is_forgotpass_enabled) && strcasecmp($this->is_forgotpass_enabled, "True") == 0 )
	//{
	$this->forgot_password_link = $this->prepare_forgot_password_link();
	//}
	//else
	//{
	//    self::debug_log("Forgot password disabled or failed to get property from server.");
//	}		
                
        // Add forgot password link in login form
        if ( !empty($this->forgot_password_link) )
	{
	    
            $addstr  = '<style>#forgot_password:focus, #forgot_password:hover { font-weight: 900;}</style>';
            $addstr .= '<script type="text/javascript">';
            $addstr .= 'var forgot_password_link='.json_encode($this->forgot_password_link).';';
            $addstr .= '</script>'."\n".'<script type="text/javascript" src="plugins/forgot_password/forgot_password.js"></script>';
            $this->rc->output->add_footer( $addstr );
        }
        return $arg;
    }

    //Depricated for now, will enable once we get approch to get domain on homepage
    private function get_forgotpass_property()
    {
	self::debug_log("Calling orchestration webservice");
	$response = null;
        $curl = curl_init();

        $URL=$this->host.'/orchestration.ws/domain/'.$this->domain.'?properties=enableforgotpassword&absolutevalues=false';
	self::debug_log($URL);

        curl_setopt($curl, CURLOPT_URL,$URL);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20); //timeout after 20 seconds
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$this->adminid:$this->adminpass");

        // Execute a request
	$result = array (
			curl_exec($curl),
        		curl_getinfo($curl),
        		curl_errno($curl),
        		curl_error($curl),
        		curl_close($curl)
		  );

	self::debug_log("Response:".print_r($result, true));

	switch($result[1]['http_code']) {
            case 200 :
		$output = json_decode($result[0],true);
		if ( $output['result']['returncode'] == 0 ){
		    $response = $output['result']['enableforgotpassword'];		
		}else{
        	    self::debug_log("Failed to get forgot password property.");
		}
                break;
            case 401 :
		self::debug_log("Unauthorized call.");
                break;
	    case 503 :
                self::debug_log("Service Temporarily Unavailable.");
                break;
            default :
		self::debug_log("Error:".print_r($result[3], true));
                break;
        }
	
        return $response;
    }

    private function prepare_forgot_password_link()
    {
        // Get client url 
        // Use to redirect on roundcube page from forgot password app.
        //$client_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];
	$client_url = $this->clientredirecturl;

        //$forgot_password_link = 'https://'.$this->forgot_pass_server.'/ueiws/Forgotpassword.jsp?rand='.rand().'&domain='.$this->domain.'&clientaddress='.urlencode($client_url);
        $forgot_password_link = 'https://'.$this->forgot_pass_server.'/ueiws/Forgotpassword.jsp?rand='.rand().'&clientaddress='.urlencode($client_url);
	
	return $forgot_password_link;
    }

    static private function debug_log($message) {
        if(self::$debug === true) {
            rcmail::console(__CLASS__.': '.$message);
        }
    }

}

?>
