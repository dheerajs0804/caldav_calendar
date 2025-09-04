<?php

class access_control extends rcube_plugin
{
    private $driver;
    private $rcmail;
    private $clientip;


    /**
    * Plugin Initialization
    */
    function init()
    {
	$this->rcmail = rcmail::get_instance();
	$this->load_config();

	$this->add_hook('login_after', array($this, 'checkaccess'));

    }

    public function checkaccess()
    {
	//Get Client IP	
	$this->clientip = $this->get_client_ip();

	//Get access control from configured driver
	$is_allowed = $this->is_access_allowed($this->clientip);

	if($is_allowed)
	{
	    //Do nothing
	    rcube::write_log('access_control', "Access allowed");	    
	}
	else
	{
		$this->__exitSession();
	    //Block access here 
	    rcube::write_log('access_control', "Access not allowed");
	    setcookie ('ajax_login','',time()-3600);
            header('Location: ?_task=login');
            exit;
	}
    }

    private function __exitSession() {

        $rcmail = rcmail::get_instance();
        header('Location: ?_task=logout&_token='.$rcmail->get_request_token());
        exit;
    }

    private function get_client_ip()
    {
	//Get client ip from $_SERVER['HTTP_X_FORWARDED_FOR']
        //The general format of the field is:
        //X-Forwarded-For: client, proxy1, proxy2
        //Converting in array based on , and taking first ip as client ip
        //In case of single ip, explode will create array on one element.       
        rcube::write_log('access_control', "HTTP_X_FORWARDED_FOR :: ".$_SERVER['HTTP_X_FORWARDED_FOR']);

        $X_Forwarded_For_Array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

        rcube::write_log('access_control', "Client IP from HTTP_X_FORWARDED_FOR :: ".$X_Forwarded_For_Array[0]);

	return $X_Forwarded_For_Array[0];
    }

    private function is_access_allowed($clientip)
    {
	if (is_object($this->driver)) {
            $result = $this->driver->get_access_control();
        }
        elseif (!($result = $this->load_driver($clientip))){
            $result = $this->driver->get_access_control();
        }

        return $result;
    }


    private function load_driver($clientip)
    {
        $driver = $this->rcmail->config->get('access_control_driver','xf');

        $driver_class  = "{$driver}_access_control";
        $file   = dirname(__FILE__) . '/drivers/' . $driver_class . '.php';
        if (!file_exists($file)) {
	    rcube::write_log('access_control', "access_control plugin: Unable to open driver file ($file).");
        }

        include_once $file;
        if (!class_exists($driver_class, false)) {
	    rcube::write_log('access_control', "access_control plugin: Broken driver $driver.");
        }
        $this->driver = new $driver_class($clientip);
    }    
}
?>
