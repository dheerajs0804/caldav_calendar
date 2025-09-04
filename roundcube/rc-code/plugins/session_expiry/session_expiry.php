<?php

class session_expiry extends rcube_plugin
{

	function init(){
		
		$this->load_config();

                $this->rc = rcube::get_instance();
                $this->rcmail = rcmail::get_instance();

		$this->add_hook('login_after', array($this, 'login_after'));

		// get debug flag - enable/disable debugging
                $this->debug_flag = $this->rc->config->get('session_expiry_debug');
                $this->pe_write_log('START');

		$this->session_expiry_check();
		
	}

	function session_expiry_check(){

		$this->pe_write_log('inside');
		
		//get session expiry time from conf file
		$session_expiry_time = $this->rc->config->get('session_expiry_time');

		//set session expiry time into env
		$this->rcmail->output->set_env('sessionexpirytime', $session_expiry_time);
		
		//call clinet side scripting to listen for users activty status
		$this->include_script('session_expiry.js');

	}

	//function for writing logs after checking if debug flag is set
        function pe_write_log($log){
                if ($this->debug_flag){
                        rcube::write_log('session_expiry', $log);
                }
        }

}

?>
