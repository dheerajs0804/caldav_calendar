<?php
class xf2 
{
     /**
     * Singleton instance of xf
     *
     * @var xf
     */
    static private $instance;

    private $xf_dir;

    /**
     * Private constructor
     */
    private function __construct(){}

    /**
    * Function to get instance of xf
    * @return xf	instance of xf
    **/
    static function get_instance()
    {
        if (!self::$instance || !is_a(self::$instance, 'xf')) {
            self::$instance = new xf2();
        }
        return self::$instance;
    }

    /**
    * Function to get xf properties for signed in user
    *
    * @param properties array
    * @param empty 			to get all retrieved user properties
    *
    * @return $xf_user_properties	array of user properties and its retrieved values.
    **/
    public function get_xf_user_properties($properties = array())
    {
	if ( ! isset($_SESSION['xf_properties']) || is_null($_SESSION['xf_properties']['userproperties']) || empty($_SESSION['xf_properties']['userproperties']) ){
	    $xf_user_properties['status']="failed";
	    $xf_user_properties['operationmsg'] = "User properties not found.";
	    $xf_user_properties['returncode'] = 1;
        }
	elseif(!$properties)
	{
	    //If properties not propvided return all retrieved properties
	    $xf_user_properties = $_SESSION['xf_properties']['userproperties'];
	}
	else
        {
 	    //return all demanded properties
	    foreach ($properties as $property){
                $xf_user_properties[$property] =  $_SESSION['xf_properties']['userproperties'][$property];
            } 
	    $xf_user_properties['returncode'] =  $_SESSION['xf_properties']['userproperties']['returncode'];
	}

        return $xf_user_properties; 	
    }

    /**
    * Function to get xf properties for signed in users domain
    * 
    * @param properties array
    * @param empty 			to get all retrieved domain properties
    *
    * @return $xf_domain_properties	array of domain properties and its retrieved values.
    **/
    public function get_xf_domain_properties($properties = array())
    {
	if ( ! isset($_SESSION['xf_properties']) || is_null($_SESSION['xf_properties']['domainproperties']) || empty($_SESSION['xf_properties']['domainproperties']) ){
	    $xf_domain_properties['status']="failed";
	    $xf_domain_properties['operationmsg'] = "Domain properties not found.";
	    $xf_domain_properties['returncode'] = 1;

	}
	elseif(!$properties)
	{
	    //If properties not provides return all domain properties
	    $xf_domain_properties = $_SESSION['xf_properties']['domainproperties'];
	}
	else
	{
	    //return all demanded domain properties
	    foreach ($properties as $property){
		 $xf_domain_properties[$property] =  $_SESSION['xf_properties']['domainproperties'][$property];
	    }
	    $xf_domain_properties['returncode'] =  $_SESSION['xf_properties']['domainproperties']['returncode'];
	}

	return $xf_domain_properties;
    }

    /**
    * Function to set xf properties for signed in user
    * 
    * @param properties 		An assosiative array with property as key and propertyvalue as value.
    *
    * @return status     		status of request
    **/
    public function set_xf_user_properties($properties)
    {
	if( is_array($properties)){
	    $this->get_xf_directory_instance();
	    $status = $this->xf_dir->set_xfproperties("user",$properties);
	}
	else{
	    $status['status']="failed";
            $status['operationmsg']="Invalid properties form. Send properties in assosiative array form.";
            $status['returncode']=1;
	}

	return $status;
    }

    /**
    * Function to set xf properties for signed in users domain
    * 
    * @param properties                 An assosiative array with property as key and propertyvalue as value.
    *
    * @return status                    status of request
    **/
    public function set_xf_domain_properties($properties)
    {
        if( is_array($properties)){
            $this->get_xf_directory_instance();
            $status = $this->xf_dir->set_xfproperties("domain",$properties);
        }
        else{
	    $status['status']="failed";
            $status['operationmsg']="Invalid properties form. Send properties in assosiative array form.";
	    $status['returncode']=1;
        }

        return $status;
    }

    /**
    * Function to get xf directory instance.
    * 
    * @return xf_dir instance
    **/
    private function get_xf_directory_instance()
    {
        $file = dirname(__FILE__) . '/xf_directory.php';
        if (!file_exists($file)) {
            rcube::write_log('errors', "xf_properties plugin: Unable to open xf_directory file ($file).");
            exit("xf_properties plugin: Unable to open xf_direcory ($file).");
        }

        include_once $file;
        if (!class_exists(xf_directory, false)) {
            rcube::write_log('errors', "xf_properties plugin: Broken xf_directory.");
            exit("xf_properties plugin: Broken xf_directory.");
        }
        $this->xf_dir = new xf_directory($api);

    }

}
?>
