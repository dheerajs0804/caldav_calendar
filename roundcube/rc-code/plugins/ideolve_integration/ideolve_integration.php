<?php

class ideolve_integration extends rcube_plugin{

        private $username;
        private $password;
        private $host;
        private $uname;
        private $domain;
        private $debug_flag;
        private $timeout;
        private $connectiontimeout;
        private $clientid;
        private $clientsecret;
        private $maildomainideolveintegration;
        private $mailclientideolveintegration;

	function init(){

		$this->load_config();

                $this->rc = rcube::get_instance();
                $this->rcmail = rcmail::get_instance();

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

                // get debug flag - enable/disable debugging
                $this->debug_flag = $this->rc->config->get('ideolve_debug');
                $this->pe_write_log('ideolve-integration - START');

                $this->add_texts('localization/');

                $this->timeout=$this->rc->config->get('ws_timeout');
                $this->connectiontimeout=$this->rc->config->get('ws_connectiontimeout');
                //$this->maildomainideolveintegration = $this->domain_properties();
                //$this->mailclientideolveintegration = $this->user_properties();

		//set WS values into users session.
                if ( $identity = $this->rc->user->get_identity() ){
                        $this->maildomainideolveintegration = $_SESSION['maildomainideolveintegration'];
                        $this->mailclientideolveintegration = $_SESSION['mailclientideolveintegration'];

                        //check if WS values are set into session, if not then then call WS and set values into session, else get values form session
                        if($this->maildomainideolveintegration=="" & $this->mailclientideolveintegration==""){
                                $this->maildomainideolveintegration = $this->domain_properties();
                                $this->mailclientideolveintegration = $this->user_properties();

                                $_SESSION['maildomainideolveintegration'] = $this->maildomainideolveintegration;
                                $_SESSION['mailclientideolveintegration'] = $this->mailclientideolveintegration;
                        }

                        $this->pe_write_log('using values from session');
                }else{

                        $this->maildomainideolveintegration = $this->domain_properties();
                        $this->mailclientideolveintegration = $this->user_properties();

                        $_SESSION['maildomainideolveintegration'] = $this->maildomainideolveintegration;
                        $_SESSION['mailclientideolveintegration'] = $this->mailclientideolveintegration;

                }
	

		if($this->maildomainideolveintegration == "Enabled" &&  $this->mailclientideolveintegration == "Enabled")
                {

                        //ideolve button to lauch ideolve app
                        $this->add_button(array(
                                'label'      => 'ideolve_integration.ideolve',
                                'type'       => 'link',
                                'href'       => './?_task=ideolveintegartion&_action=plugin.launchideolveapp',
                                'target'     => '_blank',
                                'class'      => 'button-ideolve',
                                'classsel'   => 'button-ideolve button-selected',
                                'innerclass' => 'button-inner',
                        ), 'taskbar');

                        //register method to be called when clicking on ideolve button to lauch ideolve app
                        $this->register_action('plugin.launchideolveapp', array($this, 'launch_ideolve_app'));

                        $this->add_hook('settings_actions', array($this, 'settings_actions'));
                        $this->register_action('plugin.ideolve', array($this, 'ideolve_init'));
                        $this->register_action('plugin.ideolve-save', array($this, 'ideolve_save'));
                        $this->add_texts('localization/', array('ideolve','noclientid','noclientsecret'));
                        $this->rc->output->add_label('ideolve_integration.ideolve');
                        $this->include_script('ideolve_integration.js');

                }	

	}

	###get mailclientideolveintegration property from domain level
        function domain_properties()
        {
                //call WS and check if ideolve integartion is enabled for domain
                $url = 'http://'.$this->host.':8080/orchestration.ws/domain/'.$this->domain.'?properties=mailclientideolveintegration&absolutevalues=false';
                $this->pe_write_log('URI to call GET WS to get domain mailclientideolveintegration : "'.$url.'"');

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
                $this->maildomainideolveintegration = $decodejson['result']['mailclientideolveintegration'];

                return $this->maildomainideolveintegration;
        }

	### get mailclientideolveintegration property from user level
        function user_properties()
        {
                //call WS and check if ideolve integration is enabled for user
                $url = 'http://'.$this->host.':8080/orchestration.ws/domain/'.$this->domain.'/user/'.$this->uname.'?properties=mailclientideolveintegration';
                $this->pe_write_log('URI to call GET WS to get user mailclientideolveintegration : "'.$url.'"');

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

                $this->pe_write_log('JSON response(user) : "'.$data.'"');
                $this->pe_write_log('WS Called with return code(user) : "'.$httpcode.'"');

                curl_close($ch);
                $decodejson=json_decode($data,true);
                $this->mailclientideolveintegration=$decodejson['result']['mailclientideolveintegration'];

                return $this->mailclientideolveintegration;
        }
	
	 ###call WS and update ideolve id and secret into ldap using WS
        function ideolve_save(){

                $this->pe_write_log('ideolve save - START');
                $this->add_texts('localization/');
                $this->register_handler('plugin.body', array($this, 'ideolve_form'));
                $this->rc->output->set_pagetitle($this->gettext('ideolve'));
                $this->pe_write_log('Input parameters :');
                $this->clientid = trim(rcube_utils::get_input_value('_clientid', rcube_utils::INPUT_POST, true));
                $this->pe_write_log('input ideolve client id : "'.$this->clientid.'"');
                $this->clientsecret = trim(rcube_utils::get_input_value('_clientsecret', rcube_utils::INPUT_POST, true));
                $this->pe_write_log('input ideolve client secret : "'.$this->clientsecret.'"');

                if($this->clientid == "" || $this->clientsecret == "")
                        $this->rc->output->command('display_message', $this->gettext('nodata'), 'error');
                else{

                        //check if user has permission to save ideolve credentials
                        if($this->maildomainideolveintegration== "Enabled" &&  $this->mailclientideolveintegration == "Enabled")
                        {
                                $url='http://'.$this->host.':8080/orchestration.ws/domain/'.$this->domain.'/user/'.$this->uname.'?&op=replace';
                                $post = '{  "mailclientideolveclientkey": "'.$this->clientid.'", "mailclientideolveclientsecretkey": "'.$this->clientsecret.'"}';
                                $this->pe_write_log('URI to call PUT WS to add ideolve credentials : "'.$url.'"');
                                $this->pe_write_log('JSON passed for WS : "'.$post.'"');

                                $ch = curl_init($url);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectiontimeout);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
                                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

                                $response = curl_exec($ch);

                                $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                                $this->pe_write_log('WS Called with return code : "'.$httpcode.'"');
                                $this->pe_write_log('JSON response: "'.$response.'"');

                                curl_close($ch);

                                if($httpcode==200){
                                        $this->rc->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
                                }
                                else{
                                        $this->rc->output->command('display_message', $this->gettext('unsuccessfullysaved'), 'error');
                                }
                        }
                        else{
                                $this->rc->output->command('display_message', $this->gettext('nodata'), 'error');
                        }
                }

                $this->pe_write_log('ideolve save - END');
                $this->rc->overwrite_action('plugin.ideolve');
                $this->rc->output->send('plugin');
        }

        ###to show ideolve tab in setting
        function settings_actions($args){
                $args['actions'][] = array(
                'action' => 'plugin.ideolve',
                'class' => 'ideolve',
                'label' => 'ideolve_integration.ideolve',
                'domain' => 'ideolve',
                );

                return $args;
        }

         function ideolve_init(){

                $this->add_texts('localization/');
                $this->register_handler('plugin.body', array($this, 'ideolve_form'));
                $this->rc->output->set_pagetitle($this->gettext('ideolve'));
                $this->rc->output->send('plugin');
        }


        ###get mailclientideolveclientkey and mailclientideolveclientsecretkey property for the user
        function ideolve_properties(){
                $url='http://'.$this->host.':8080/orchestration.ws/domain/'.$this->domain.'/user/'.$this->uname.'?properties=mailclientideolveclientkey,mailclientideolveclientsecretkey';
                $this->pe_write_log('URI to get ideolve credentials : "'.$url.'"');

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectiontimeout);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                $data = curl_exec($ch);

                $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                curl_close($ch);

                $decodejson=json_decode($data,true);
                $this->pe_write_log('JSON response : "'.$data.'"');
                $this->pe_write_log('WS Called with return code : "'.$httpcode.'"');

                return $decodejson;

        }


        //ideolve form for get ideolve credentials
        function ideolve_form(){

                $this->pe_write_log('ideolve form - START');
                $decodejson = $this->ideolve_properties();

                $mailclientideolveclientkey =$decodejson['result']['mailclientideolveclientkey'];
                $mailclientideolveclientsecretkey =$decodejson['result']['mailclientideolveclientsecretkey'];

                if(method_exists($this->rc,'imap_connect')) $this->rc->imap_connect();

                else $this->rc->storage_connect();
                        $table = new html_table(array('cols' => 2, 'class' => 'propform cols-sm-6-6'));

                        $table->add('title', rcube_utils::rep_specialchars_output($this->gettext('clientid').":"), 'html');
                        $inputfield = new html_inputfield(array('name' => '_clientid', 'id' => 'clientid'));
                        $table->add('', $inputfield->show("$mailclientideolveclientkey"));

                        $table->add('title', rcube_utils::rep_specialchars_output($this->gettext('clientsecret').":"), 'html');
                        $inputfield = new html_inputfield(array('name' => '_clientsecret', 'id' => 'clientsecret'));
                        $table->add('', $inputfield->show("$mailclientideolveclientsecretkey"));

                        $user = $this->rc->user;
                        $arr_prefs = $user->get_prefs();
                        $i = 1;
                        $table2 = new html_table(array('cols' => 2));

                        if ($this->rc->config->get('skin') == 'elastic') {
                                $out = html::tag('fieldset', array('class' => 'main'),
                                html::tag('legend', null, $this->gettext('mainoptions')).
                                $table->show() .
                                html::p(null,
                                        $this->rc->output->button(array(
                                        'command' => 'plugin.ideolve-save',
                                        'type' => 'input',
                                        'class' => 'button mainaction',
                                        'label' => 'save'
                                        ))
                                        )
                                );

                        }
                else {
                        $out = html::div(array('class' => 'box'),
                        html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('ideolve')).
                        html::div(array('class' => 'boxcontent'), $table->show() .
                        html::p(null,
                                $this->rc->output->button(array(
                                'command' => 'plugin.ideolve-save',
                                'type' => 'input',
                                'class' => 'button mainaction',
                                'label' => 'save'
                                )))));
                        }

                $this->rc->output->add_gui_object('ideolveform', 'ideolve-form');

                $form = $this->rc->output->form_tag(array(
                'id' => 'ideolve-form',
                'name' => 'ideolve-form',
                'method' => 'post',
                'class' => 'propform cols-sm-6-6',
                        'action' => './?_task=settings&_action=plugin.ideolve-save',
                ), $out);

                $this->pe_write_log('ideolve form - END');
                if ($this->rc->config->get('skin') == 'elastic') {
                        return html::div(array('class' => 'formcontent'), $form);
                } else {
                        return $form;
                }
        }

        //function for writing logs after checking if debug flag is set
        function pe_write_log($log){
                if ($this->debug_flag){
                        rcube::write_log('ideolve', $log);
                }
        }
	
	function launch_ideolve_app(){
		###call method and get user property
                $decodejson = $this->ideolve_properties();
                $mailclientideolveclientkey =$decodejson['result']['mailclientideolveclientkey'];
                $mailclientideolveclientsecretkey =$decodejson['result']['mailclientideolveclientsecretkey'];

		$emailid=$this->username;
                $ideolveclinetid=$mailclientideolveclientkey;
                $ideolvesecretkey=$mailclientideolveclientsecretkey;

                $data='{
                        "email": "'.$emailid.'",
                        "connectorClientId": "'.$ideolveclinetid.'",
                        "connectorApiKey": "'.$ideolvesecretkey.'"
                }';

		$this->pe_write_log($data);

		###convert json array to base64
		$base64encode = base64_encode($data);

		$ideolve_integartion_url = rcube::get_instance()->config->get('ideolve_url');

		$ideolveconnecturl = $ideolve_integartion_url."thinkshop/Login/Login.html#?r=".$base64encode;

		$this->pe_write_log($ideolveconnecturl);
	
		header("Location: $ideolveconnecturl");

                $rcube::console("Inside init");
		
	}	

}

?>
