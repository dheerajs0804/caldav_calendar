<?php

class intchat extends rcube_plugin
{
	private $username;
	private $password;
   	private $host;
   	private $uname;
   	private $domain;
	private $debug_flag;
	private $timeout;
        private $connectiontimeout;
	private $maildomainintchatintegration;
        private $mailclientintchatintegration;

	function init()
	{
		$this->rcmail = rcmail::get_instance();
		$this->rc = rcube::get_instance();

		 ###get values for WS auth
                $this->username= $this->rc->user->data['username'];
                $this->password=$this->rc->decrypt($_SESSION['password']);

                ###get login user email id and extract username and domain from it
                if ( $identity = $this->rc->user->get_identity() ){
                        list($name,$domain) = explode('@', $identity['email']);
                        $this->uname = $name;
                        $this->domain = $domain;
                }else{
                        list($name,$domain) = explode('@', $this->rc->user->data['username']);
                        $this->uname = $name;
                        $this->domain = $domain;
                }
	
		###get host name from conf  file
                $this->host = $this->rc->config->get('default_host');

		$this->debug_flag = $this->rc->config->get('intchat_debug');
	
	        $this->timeout=$this->rc->config->get('ws_timeout');
		$this->connectiontimeout=$this->rc->config->get('ws_connectiontimeout');


		//set WS values into users session.
                if ( $identity = $this->rc->user->get_identity() ){
			$this->maildomainintchatintegration = $_SESSION['maildomainintchatintegration'];
                        $this->mailclientintchatintegration = $_SESSION['mailclientintchatintegration'];

                        //check if WS values are set into session, if not then then call WS and set values into session, else get values form session
                        if($this->maildomainintchatintegration == "" && $this->mailclientintchatintegration == "" ){
				$this->maildomainintchatintegration = $this->domain_properties();
                                $this->mailclientintchatintegration = $this->user_properties();
				
				$_SESSION['maildomainintchatintegration'] = $this->maildomainintchatintegration;
                                $_SESSION['mailclientintchatintegration'] = $this->mailclientintchatintegration;
                        }

			$this->pe_write_log('using values from session');

                }else{
			$this->maildomainintchatintegration = $this->domain_properties();
                        $this->mailclientintchatintegration = $this->user_properties();

			$_SESSION['maildomainintchatintegration'] = $this->maildomainintchatintegration;
                        $_SESSION['mailclientintchatintegration'] = $this->mailclientintchatintegration;

                }


		###$this->mailclientintchatintegration = $this->user_properties();
	
		if($this->maildomainintchatintegration == "Enabled" && $this->mailclientintchatintegration == "Enabled"){
			$this->add_texts('localization/');	
			$this->add_button(array(
                	          'type'       => 'link',
                        	  'label'      => 'intchat.intchat',
				  'href'       => './?_task=intchat&_action=plugin.launchintchatapp',
        	                  'target'     => '_blank',
				  'id'	       => 'openchat',
                        	  'class'      => 'button-intchat',
	                          'classsel'   => 'button-intchat button-selected',
        	                  'innerclass' => 'button-inner',
                	          'title'      => 'intchat.intchat'
                       		), 'taskbar');

			$this->register_action('plugin.launchintchatapp', array($this, 'launch_intchat_app'));
			$this->rc->output->add_label('intchat');
		}
	}

	###get maildomainintchatintegration property from domain level
        function domain_properties()
        {
                //call WS and check if intchat is enabled for domain
                $url = 'http://'.$this->host.':8080/orchestration.ws/domain/'.$this->domain.'?properties=mailclientbayav4chatmicroapp&absolutevalues=false';
                $this->pe_write_log($url);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectiontimeout);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                $data = curl_exec($ch);

                $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                $this->pe_write_log('WS Called with return code(domain) : "'.$httpcode.'"');
                curl_close($ch);

                $decodejson=json_decode($data,true);
                $this->pe_write_log('JSON response(domain) : "'.$data.'"');
                $this->maildomainintchatintegration = $decodejson['result']['mailclientbayav4chatmicroapp'];

                return $this->maildomainintchatintegration;
        }
	
	###get mailclientintchatintegration property from user level
	function user_properties()
  	{
    		//call WS and check if intchat is enabled for user
		$url = 'http://'.$this->host.':8080/orchestration.ws/domain/'.$this->domain.'/user/'.$this->uname.'?properties=mailclientbayav4chatmicroapp';
		$this->pe_write_log($url);
    		$ch = curl_init($url);
    		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
    		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectiontimeout);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
    		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    		$data = curl_exec($ch);
    		$httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    		$decodejson=json_decode($data,true);

    		curl_close($ch);
    		$decodejson=json_decode($data,true);
    		$this->mailclientintchatintegration = $decodejson['result']['mailclientbayav4chatmicroapp'];

		$this->pe_write_log($this->mailclientintchatintegration);
    		return $this->mailclientintchatintegration;
  	}	

	function launch_intchat_app()
	{
		$user = $this->rc->user->data['username'];
                $password = $this->rc->decrypt($_SESSION['password']);
                echo "<script type='text/javascript' src='plugins/intchat/intchat.js'></script>";
                echo "<script type='text/javascript'> jsfunction('$user','$password'); </script>";

                $rcube::console("Inside init");
	}

	 //function for writing logs after checking if debug flag is set
        function pe_write_log($log){
                if ($this->debug_flag){
                        rcube::write_log('intchat', $log);
                }
        }

}

?>
