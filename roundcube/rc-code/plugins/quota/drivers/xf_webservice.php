<?php

/**
 * XF Web service base class
 *
 */

class rcube_xf_webservice_quota
{
    protected $adminid;
    protected $adminpass;
    protected $host;
    protected $user_name;
    protected $domain;

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

         //construct user and domain
         $username = $rcmail->user->data['username'];
         if (strstr($username, '@')){
             $temparr = explode('@', $username);
             $this->user_name = $temparr[0];
             $this->domain = $temparr[1];
         }
         elseif ( $identity = $rcmail->user->get_identity() )
         {
             list($name,$domain) = explode('@', $identity['email']);
             $this->user_name = $name;
             $this->domain = $domain;
         }
         else
         {
             $domain = $rcmail->config->get('username_domain', false);
             if (!$domain)
             {
                 rcube::write_log('quota', 'Plugin quota : $config[\'username_domain\'] is not defined.');
             }
             $this->user_name = $username;
             $this->domain = $domain;
         }
    }

}
