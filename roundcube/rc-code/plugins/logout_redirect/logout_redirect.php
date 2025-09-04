<?php

/**
 * Redirect on logout
/**
 * Notice: This plugin must run as the last plugins because it exits
  *         on the 'logout_after' hook !!!
**/
 
class logout_redirect extends rcube_plugin
{
    public $task = 'logout';
    // we've got no ajax handlers
    public $noajax = true;
    // skip frames
    public $noframe = true;

    private $rcmail;
    private $redirect_url;
  
    function init()
    {
	$this->rcmail = rcmail::get_instance();
        $this->load_config();

        $this->add_hook('logout_after', array($this,'logout_after'));
    }

    // user logout 
    function logout_after($args)  
    {        

	rcube::write_log('logout_redirect', $args);

	//Get domain for loggedout user
	$domain = $this->get_loggedout_userdomain($args['user']);

	if ($domain != null)
	{
	    //Get logout url from configured driver.
            $logout_url = $this->get_logout_url($domain);
            rcube::write_log('logout_redirect', "logout_redirect plugin: get logout_url response from configured driver:");
            rcube::write_log('logout_redirect', $logout_url);
	    
	    //Validate response and url.
	    if ($logout_url['status'] == 'success' && filter_var($logout_url['url'], FILTER_VALIDATE_URL))
	    {
                setcookie ('ajax_login','',time()-3600);
                header("Location:".$logout_url['url'], true, 301);
                exit;
	    }
	}

        return $args; 
    } 

    private function get_loggedout_userdomain($user)
    {
	// pick users default identity email
        $sql_result = $this->rcmail->db->query(
        		"SELECT i.email FROM ".$this->rcmail->db->table_name('users')." AS u
			JOIN ".$this->rcmail->db->table_name('identities')." AS i 
			ON u.user_id=i.user_id WHERE u.username=? AND i.standard=1 LIMIT 1;",
              		$user);

	if ( $sql_result && ($sql_arr = $this->rcmail->db->fetch_assoc($sql_result)) ) {
            list($name,$domain) = explode('@', $sql_arr['email']);
        }
	
	return $domain;
    }

    /**
    * Function to get logout url from configured driver.
    *
    */
    private function get_logout_url($domain)
    {
	if (is_object($this->driver)) {
            $result = $this->driver->get();
        }
        elseif (!($result = $this->load_driver($domain))){
            $result = $this->driver->get();
        }

        return $result;
    }


     /**
     * Function to Load driver.
     *
     */
    private function load_driver($domain)
    {
	//Default driver will be xf_webservice
        $driver = $this->rcmail->config->get('logout_redirect_driver','xf_redirect');
	$driver_class  = "{$driver}";
        $file   = $this->home . "/drivers/$driver.php";

	if (!file_exists($file)) {
            rcube::raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "logout_redirect plugin: Unable to open driver file ($file)"
            ), true, false);
            rcube::write_log('logout_redirect', "logout_redirect plugin: Unable to open driver file ($file).");
            return $this->gettext('internalerror');
        }

        include_once $file;

        if (!class_exists($driver_class, false)) {
            rcube::raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "logout_redirect plugin: Broken driver $driver"
            ), true, false);
            rcube::write_log('logout_redirect', "logout_redirect plugin: Broken driver $driver.");
            return $this->gettext('internalerror');
        }

        $this->driver = new $driver_class($domain);
    }
 
}
?>
