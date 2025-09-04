<?php

/**
*
* Hashed Password
*
* includes the js file, which does the hashing
* add prefix and salt before hash and send for authentication
*
* @version 1.0
*
*/

class hashed_password extends rcube_plugin
{
    public $task = 'login|logout';
    public $noframe = true;
    private $rcmail;
    private $debug_flag;
    private $db_dsnw;

    public function init()
    {
        $this -> rcmail = rcmail::get_instance(); // create instance to be used in entire class
        $this -> debug_flag = $this -> rcmail -> config -> get('hp_debug');
        $this -> db_dsnw = $this -> rcmail -> config -> get('db_dsnw'); // get debug flag - enable/disable debugging

        $this -> hp_write_log('');
        $this -> hp_write_log('in init hp'); // debug message, writes to skyconnect/logs/hashed_password

        $this -> include_script('hashed_password.js');
        $this -> include_script('crypto-js.js'); // downloaded and moved only the js file in the folder

        $this -> add_hook('authenticate', array($this, 'authenticate'));
    }

    public function authenticate($args)
    {
        $this -> hp_write_log('in authenticate hp - pass - "' . $args['pass'] . '"');
        $randomid = $_POST['randomid'];

        // connect to database to get salt
        $this -> hp_write_log($this -> db_dsnw); 
        $db = rcube_db::factory($this -> db_dsnw); // get driver instance of db object
        $db->db_connect('r'); // connecting in read mode
        $this -> hp_write_log($db->is_connected()); // check if connected
        if (!$db->is_connected()) {
            rcube::raise_error("Error connecting to database: " . $db->is_error(), false, true); // raise error if not connected
        }

        $this -> hp_write_log('randomid- ' . $randomid);
        $temp = $db -> query("SELECT salt FROM baya_v3_salt WHERE randomid = ?", $randomid); // fire query
        if ($db->is_error()) {
            rcube::raise_error("Error getting salt from database: " . $db->is_error(), false, true); // raise error if not inserted
            hp_write_log('Error getting salt from database');
        }
        $res = $db -> fetch_assoc($temp); // convert to array
        $this -> hp_write_log('salt- ' . $res['salt']); // get result from the array

        $salt = 0;
        $salt = $res['salt'];

        // prepend [bayassha] so the the authentication server understands it is using sha256 salted hashing on the password 
        $finalout = '[bayassha]' . $salt . $args['pass'];
        $this -> hp_write_log('final output -  ' . $finalout);
        $args['pass'] = $finalout; // change password to send it to imap server for authentication
        return $args;
    }

    // function for writing logs after checking if debug flag is set
    // enable disable logging in config.inc.php from config folder

    private function hp_write_log($log)
    {
        if ($this -> debug_flag)
        {
            rcube::write_log('hashed_password', $log);
        }
    }
}
