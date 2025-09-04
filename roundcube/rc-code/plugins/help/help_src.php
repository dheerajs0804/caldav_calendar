<?php
class help_src
{
     /**
     * Singleton instance of help_src
     *
     * @var help_src
     */
    static protected $instance;

    // Server parameters for help app
    private $incomingimapserver;
    private $outgoingsmtpserver;
    private $ldapservername;
    private $caldavservername;
    private $xmppservername;
    private $domainname;

    /**
    * Private constructor
    */
    private function __construct(){}


    /**
    * Function to get instance of help_src
    * @return help_src        instance of help_src
    **/
    static function get_instance()
    {
        if (!self::$instance || !is_a(self::$instance, 'help_src')) {
            self::$instance = new help_src();
        }
        return self::$instance;
    }

    public function get_help_link()
    {   
        $rcmail = rcmail::get_instance();

        //Get xf instance
        include_once '/var/www/html/roundcubemail/plugins/xf_directory/xf.php';
        $xf_instance = xf2::get_instance();

	//List of properties to fetch
	$arr_properties = array('incomingimapserver','outgoingsmtpserver','ldapservername','caldavservername','xmppservername');

        //Get server properties from xf
        $server_properties = $xf_instance->get_xf_domain_properties($arr_properties);

	//Get caldavserver from config
        $calendar_server_url = $rcmail->config->get('mithi_calendar_server');
        $calendar_url_component = parse_url($calendar_server_url);
        $calendar_server = $calendar_url_component['host'];

        //validate server properties to check for empty for null
        //If found empty set with default server name formed using domain
        $this->validate_serverproperties($server_properties);

        $link = $rcmail->config->get('help_source');
        $link .='?app=&client=&domainname=&vendorname=&szIMAPServer='.$this->incomingimapserver.'&szSMTPServer='.$this->outgoingsmtpserver.'&szLDAPServer='.$this->ldapservername.'&szCALDAVServer='.$calendar_server.'&szCALDAVServerURL='.$calendar_server_url.'&szXMPPServer='.$this->xmppservername.'&szUserid='.$rcmail->get_user_name().'&appcontext=mail&defaultapp=email';


        return $link;
    }

    private function validate_serverproperties($server_properties)
    {
	//validate incomingimapserver outgoingsmtpserver ldapservername caldavservername xmppservername serverproperties
	//If server properties not set, return defaultserver name

	if ($server_properties['returncode'] != 0)
	{
	    //Failed to get server properties from xf_directory.
	    rcube::write_log('errors', "help plugin: Failed to get server properties from xf_directory.");

	    //Set default servername for serverproperties
	    $this->incomingimapserver = $this->get_default_servername();
	    $this->outgoingsmtpserver = $this->get_default_servername();
    	    $this->ldapservername = $this->get_default_servername();
    	    $this->caldavservername = $this->get_default_servername();
    	    $this->xmppservername = $this->get_default_servername();
	

	}
	else
	{
	    //Successfully retrieved server properties from xf_directory.
	    foreach($server_properties as $property => $propertyvalue)
 	    {
	        if ( empty($propertyvalue) || is_null($propertyvalue) )
		{
                    $this->$property = $this->get_default_servername();
                }
	        else
	        {
                    $this->$property = $propertyvalue;
                }
            }
        } 
    }

    private function get_default_servername()
    {
        //return defaultserver name as <domain>.mithiskyconnect.com
        $domainname = $this->get_domainname();
        $domainname = substr($domainname, 0,strripos($domainname, '.'));

        $defaultserver = $domainname.".mithiskyconnect.com";

        return $defaultserver;
    }

    private function get_domainname()
    {
        $rcmail = rcmail::get_instance();

        //get domainname
        $username = $rcmail->user->data['username'];
        if (strstr($username, '@')){
            $temparr = explode('@', $username);
            $this->domainname = $temparr[1];
        }
	elseif ( $identity = $rcmail->user->get_identity() )
        {
            list($name,$domain) = explode('@', $identity['email']);
            $this->domainname = $domain;
        }
        else 
	{
            $domain = $rcmail->config->get('username_domain', false);
            if (!$domain) {
                rcube::write_log('errors', 'Plugin help : $config[\'username_domain\'] is not defined.');
            }
            $this->domainname = $domain;
        }

        return $this->domainname;
    }
}
?>
