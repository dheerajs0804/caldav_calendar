<?php

class chat extends rcube_plugin
{
	private $username;
	private $password;
   	private $host;
   	private $uname;
   	private $domain;
	private $debug_flag;
	private $timeout;
        private $connectiontimeout;
	private $maildomainchatintegration;
        private $mailclientchatintegration;

	function init()
	{
		$this->load_config();
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

		$this->debug_flag = $this->rc->config->get('chat_debug');
	
	        $this->timeout=$this->rc->config->get('ws_timeout');
		$this->connectiontimeout=$this->rc->config->get('ws_connectiontimeout');


		//set WS values into users session.
                if ( $identity = $this->rc->user->get_identity() ){
			$this->maildomainchatintegration = $_SESSION['maildomainchatintegration'];
                        $this->mailclientchatintegration = $_SESSION['mailclientchatintegration'];

                        //check if WS values are set into session, if not then then call WS and set values into session, else get values form session
                        if($this->maildomainchatintegration == "" && $this->mailclientchatintegration == "" ){
				$this->maildomainchatintegration = $this->domain_properties();
                                $this->mailclientchatintegration = $this->user_properties();
				
				$_SESSION['maildomainchatintegration'] = $this->maildomainchatintegration;
                                $_SESSION['mailclientchatintegration'] = $this->mailclientchatintegration;
                        }

			$this->pe_write_log('using values from session');

                }else{
			$this->maildomainchatintegration = $this->domain_properties();
                        $this->mailclientchatintegration = $this->user_properties();

			$_SESSION['maildomainchatintegration'] = $this->maildomainchatintegration;
                        $_SESSION['mailclientchatintegration'] = $this->mailclientchatintegration;

                }


		###$this->mailclientchatintegration = $this->user_properties();
	
		if($this->maildomainchatintegration == "Enabled" && $this->mailclientchatintegration == "Enabled"){
			$this->add_texts('localization/');	
			$this->add_button(array(
                	          'type'       => 'link',
                        	  'label'      => 'chat.chat',
				  'href'       => './?_task=chat&_action=plugin.launchchatapp',
        	                  'target'     => '_blank',
				  'id'	       => 'openchat',
                        	  'class'      => 'button-chat',
	                          'classsel'   => 'button-chat button-selected',
        	                  'innerclass' => 'button-inner',
                	          'title'      => 'chat.chat'
                       		), 'taskbar');

			$this->register_action('plugin.launchchatapp', array($this, 'launch_chat_app'));
			$this->rc->output->add_label('chat');
		}
	}

	###get maildomainchatintegration property from domain level
        function domain_properties()
        {
                //call WS and check if chat is enabled for domain
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
                $this->maildomainchatintegration = $decodejson['result']['mailclientbayav4chatmicroapp'];

                return $this->maildomainchatintegration;
        }
	
	###get mailclientchatintegration property from user level
	function user_properties()
  	{
    		//call WS and check if chat is enabled for user
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
    		$this->mailclientchatintegration = $decodejson['result']['mailclientbayav4chatmicroapp'];

		$this->pe_write_log($this->mailclientchatintegration);
    		return $this->mailclientchatintegration;
  	}	

	function launch_chat_app()
        {
		$this->pe_write_log("Launch Chat App: START");
		
		$url = rcube::get_instance()->config->get('initaccess_token_url');
                $user=$this->uname;
                $domain=$this->domain;
                $user = $this->rc->user->data['username'];
                $password = $this->rc->decrypt($_SESSION['password']);        
		$rid = base64_encode($password);		

                $ch = curl_init($url);
                $data='{
                         "user": "'.$user.'",
                         "domain": "'.$domain.'",
                         "launcher": "bayav4",
                         "microApp": "chat",
                         "username": "'.$user.'",
                         "rid": "'.$rid.'"
                }';

		$this->pe_write_log("Data : ".$data);                
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                $headers = [];
                $headers[] = 'Content-Type:application/json';
		$headers[] = rcube::get_instance()->config->get('init_token_key');

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($ch);
                $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

                curl_close($ch);
                $decodejson=json_decode($result,true);
		
                //parse json and print required fields.
                $initaccestoken=$decodejson["initAccessToken"];

                //call chat app code
		$redirectchaturl = rcube::get_instance()->config->get('redirect_chat_url');
                $chatredirecturl= $redirectchaturl .$initaccestoken;
		
		$this->pe_write_log($chatredirecturl);
                header("Location: $chatredirecturl");

		$this->pe_write_log("Launch Chat App: END");
                $rcube::console("Inside init");
        }


	 //function for writing logs after checking if debug flag is set
        function pe_write_log($log){
                if ($this->debug_flag){
                        rcube::write_log('chat', $log);
                }
        }

}

?>
