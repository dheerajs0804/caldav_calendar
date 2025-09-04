<?php

/**
 * BIMI
 *
 * Plugin to display Display Brand Indicators for Message Identification (BIMI) icons
 * for contacts/addresses that do not have a photo image.
 *
 * @license GNU GPLv3+
 * @author Craig Andrews <candrews@integralblue.com>
 * @website http://roundcube.net
 */
class bimi extends rcube_plugin
{
    public $task = 'addressbook';

    static private $debug ;		

    /**
     * Plugin initialization.
     */
    function init()
    {
	$this->load_config();

	$rc = rcmail::get_instance();
	if(self::$debug === null) {
    		self::$debug = $rc->config->get('bimi_debug');
	}

	self::debug_log("Bimi plugin init called.");

        $this->add_hook('contact_photo', [$this, 'contact_photo']);
	
	self::debug_log("Bimi plugin init completed.");
    }

    /**
     * 'contact_photo' hook handler to inject a bimi image
     */
    function contact_photo($args)
    {
	self::debug_log("Contact photo hook called.");


	$dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
        #$caller = isset($dbt[2]['function']) ? $dbt[1]['function'] : null;
	self::debug_log("Calling functions list below: ");

	foreach( $dbtelem as $dbt )
		self::debug_log("Calling function: ".$dbtelem[1]);
	
	self::debug_log("Parameters url: ".$args['url']);
	self::debug_log("Parameters data: ".$args['data']);
	self::debug_log("Parameters record: ".$args['record']);
	self::debug_log("Parameters email: ".$args['email']);

        // pre-conditions, exit if photo already exists or invalid input
        if (!empty($args['url']) || !empty($args['data'])
            || (empty($args['record']) && empty($args['email']))
        ) {
	    self::debug_log("Skipping because of invalid parameters");
            return $args;
        }
	self::debug_log("Processing mail");

        $rcmail = rcmail::get_instance();

        // supporting edit/add action may be tricky, let's not do this
        if ($rcmail->action == 'show' || $rcmail->action == 'photo') {
	    self::debug_log("Action is show or photo. Lets get details.".$rcmail->action);

            $email = !empty($args['email']) ? $args['email'] : null;
	    self::debug_log("Email :".$email);

            if (!$email && $args['record']) {
	    	self::debug_log("Email is empty. Record is".$args['record']);

                $addresses = rcube_addressbook::get_col_values('email', $args['record'], true);
                if (!empty($addresses)) {
                    $email = $addresses[0];
                }
            }

            if ($email) {
		self::debug_log("Getting bimi image");		

                require_once __DIR__ . '/bimi_engine.php';
                $bimi_image = new bimi_engine($email,self::$debug);
		self::debug_log("Bimi image object ");		

                if ($rcmail->action == 'show') {
		    
		     self::debug_log("Action is show");		
			
                    // set photo URL
                    if (($icon = $bimi_image->getBinary()) && ($icon = base64_encode($icon))) {
		        self::debug_log("Image is binary, so base64 encoded.");		
                        $mimetype    = $bimi_image->getMimetype();
                        $args['url'] = sprintf('data:%s;base64,%s', $mimetype, $icon);
                    }
                }
                else {
		     self::debug_log("Action is photo");		
                    // send the icon to the browser | -> this is not working - so we are directly returning url
                    //if ($bimi_image->sendOutput()) {
		    //    self::debug_log("Output sent");		
                    //    exit;
                    //}
		    //self::debug_log("Failure to send output");		
		 	$bimi_url = $bimi_image->retrieve_bimi_url();
			$retval = array("url"=>$bimi_url);
			return $retval ;
                }
            }
        }
	else
	{
		self::debug_log("Action is not show or photo");
	}

        return $args;
    }

    static private function debug_log($message) {

        if(self::$debug === true) {
            rcmail::console(__CLASS__.': '.$message);
        }
    }
}
