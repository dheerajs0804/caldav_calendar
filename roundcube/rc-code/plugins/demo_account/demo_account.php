<?php

class demo_account extends rcube_plugin
{
    // On task 'login/logout'
    public $task = 'login|logout';

    private $rcmail;
    private $demouser;
    private $demouserpass;
    private $demohost;
                        
    function init()
    {	
        $this->rcmail = rcube::get_instance();
	$this->load_config();

	//Read demo account details from config file
	$this->demouser = $this->rcmail->config->get('demo_user');
	$this->demouserpass = $this->rcmail->config->get('demo_user_password');
	$this->demohost = $this->rcmail->config->get('demo_host');

        //Add hook to get loginform template
        $this->add_hook('template_object_loginform', array($this, 'demo_page'));
    }

    function demo_page($arg)
    {
	if(!empty($this->demouser) && !empty($this->demouserpass) && !empty($this->demohost))
	{
	    if($_SERVER['SERVER_NAME'] == $this->demohost)
	    {
		$addstr = '<script type="text/javascript">var demouser='.json_encode($this->demouser).';';
		$addstr .= 'var demouserpass='.json_encode($this->demouserpass).';</script>';
                $addstr .= '<script type="text/javascript" src="plugins/demo_account/demo_account.js"></script>';
                $this->rcmail->output->add_footer( $addstr );
	    }
	}
        return $arg;
    }
}

?>
