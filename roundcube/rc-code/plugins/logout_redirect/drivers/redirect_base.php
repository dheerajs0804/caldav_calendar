<?php

/**
 * Logout Redirect Driver Base class
 *
 */

class redirect_base
{
    protected $adminid;
    protected $adminpass;
    protected $host;

    /**
    * construct server parameters to call xf webservice.
    *
    */
    public function __construct()
    {
        //construct server params for calling quota webservice
        $rcmail = rcmail::get_instance();

        $dir_serverinfo = $rcmail->config->get('xf_directory_server', array());
        $this->adminid = $dir_serverinfo['adminid'];
        $this->adminpass = $dir_serverinfo['adminpass'];
        $this->host = $dir_serverinfo['host'];

    }
}

