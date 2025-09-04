<?php

class update_identity extends rcube_plugin
{
    private $rc;

    /**
    * Plugin Initialization
    */
    function init()
    {
	$this->rc = rcube::get_instance();
        $this->add_hook('login_after', array($this, 'updateidentity'));
	
    }

    /**
    * Update default identity after login.
    */
    function updateidentity()
    {
	//Get default identity
	$default_identity = $this->get_default_identity();

	//Get id of default identity required for update with new email.
        $default_identity_id = $default_identity['identity_id'];

	//Get identity from selected driver and update identity.
        $identity = $this->get_identity();

	rcube::write_log('errors', "Identity fetched for login id:");
	rcube::write_log('errors', $identity);
	
	if ( !empty($identity) )
    	{
	    foreach ($identity as $key => $value)
	    {
                //If there is difference in default identity and retrieved then only update.
                if ( $default_identity[$key] != $identity[$key]  )
                {
                    $updated = $this->rc->user->update_identity($default_identity_id, $identity);
                    rcube::write_log('errors', 'Update_response:'.$updated);
                    break;
                }
            }
    	}
	else
	{
	    rcube::write_log('errors', 'Failed to get identity.');	
	}
    }

    /**
    * Get default identity of for logged in user.
    *
    */
    function get_default_identity()
    {	
	rcube::write_log('errors', "DEFAULT IDENTITIY:");
		
	$defaultidentity = $this->rc->user->get_identity();

	 if ($this->rc->config->get('debug_mode') == 1) {
		rcube::write_log('errors', $defaultidentity);
	}

	return $defaultidentity;
    }

    function get_identity()
    {
        if (is_object($this->driver)) {
            $result = $this->driver->get_identity();
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->get_identity();
        }
        return $result;
    }  

    private function load_driver()
    {
        $this->rc = rcube::get_instance();
        $this->load_config();

        $driver = $this->rc->config->get('updateidentity_driver','active_directory');

        $driver_class  = "{$driver}_identity";
        $file   = dirname(__FILE__) . '/drivers/' . $driver . '/' . $driver_class . '.php';
        if (!file_exists($file)) {
            rcube::write_log('errors', "update_identities plugin: Unable to open driver file ($file).");
        }

        rcube::write_log('errors', $file);
        include_once $file;
        if (!class_exists($driver_class, false)) {
            rcube::write_log('errors', "update_identities plugin: Broken driver $driver.");
        }
        $this->driver = new $driver_class();
    }

}
?>
