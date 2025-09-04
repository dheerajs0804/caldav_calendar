<?php

class xf_directory extends rcube_plugin
{
    private $driver;
    private $xf_properties;


    /**
    * Plugin Initialization
    */
    function init()
    {
	$this->add_hook('login_after', array($this, 'get_xfproperties'));
	$this->add_hook('update_xf_properties', array($this, 'get_xfproperties'));
    }

    function get_xfproperties()
    {
	$this->xf_properties = $this->_get();
	if (is_array($this->xf_properties) && !(is_null($this->xf_properties))) {
	    //Added for reference
	    rcube::write_log('errors', $this->xf_properties);
	    
	    //Set xf properties in current session
	    $_SESSION['xf_properties'] = $this->xf_properties;
        }
        else {
	    rcube::write_log('errors', "xf_properties plugin: Unable to get xf properties.");
        }
    }

    function _get()
    {
	if (is_object($this->driver)) {
            $result = $this->driver->get_xf_properties();
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->get_xf_properties();
        }
        return $result;
    }

    function set_xfproperties($entity, $properties)
    {
        $status = $this->_set($entity, $properties);

	if ($status['returncode'] == 0 ){
	    //reload plugin to get updated properties in session.
	    $this->rc = rcmail::get_instance();
	    $this->rc->plugins->exec_hook('update_xf_properties',array());
	}
        return $status;
    }

    function _set($entity, $properties)
    {
	if (is_object($this->driver)) {
            $result = $this->driver->set_xf_properties($entity, $properties);
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->set_xf_properties($entity, $properties);
        }
        return $result;

    }

    private function load_driver()
    {
	$this->rc = rcube::get_instance();
	$this->load_config();
  
        $driver = $this->rc->config->get('xf_properties_driver','webservice');

        $driver_class  = "{$driver}_xf_properties";
        $file   = dirname(__FILE__) . '/drivers/' . $driver . '/' . $driver_class . '.php';
        if (!file_exists($file)) {
	    rcube::write_log('errors', "xf_properties plugin: Unable to open driver file ($file).");
        }

        include_once $file;
        if (!class_exists($driver_class, false)) {
	    rcube::write_log('errors', "xf_properties plugin: Broken driver $driver.");
        }
        $this->driver = new $driver_class();
    }    
}
?>
