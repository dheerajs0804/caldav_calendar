<?php

/**
 * 
 * get randomid
 * 
 * script that generates random 10 char string as salt for hashing
 * adds the salt indexed by randomid in database roundcubemail, table salt
 * used in ajax request from hashed_password.js
 * 
 * @version 1.0
 * 
 */

define('INSTALL_PATH', realpath(__DIR__ . '/../../') . '/');
include INSTALL_PATH . 'program/include/iniset.php';
$rcmail = rcmail::get_instance();
$debug_flag = $rcmail->config->get('hp_debug');

hp_write_log('in db connect session data: ');

// generate random salt and randomid
$permitted_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
$salt = substr(str_shuffle($permitted_chars), 0, 16);
$randomid = substr(str_shuffle($permitted_chars), 0, 16);

// add salt in database-------------------------------------------------------------------------------------------------
$db = rcube_db::factory($rcmail->config->get('db_dsnw'));   // get driver instance of db object
$db->db_connect('w');                                       // connecting in write mode
hp_write_log('is connected: ' . $db->is_connected());     // check if connected
if (!$db->is_connected()) 
{
    rcube::raise_error("Error connecting to database: " . $db->is_error(), false, true); // raise error if not connected
    hp_write_log("Error connecting to database: " . $db->is_error());
}

$temp = $db->query("INSERT INTO baya_v3_salt (randomid, salt) VALUES (?, ?)", $randomid, $salt); // fire query to insert data
if ($db->is_error()) 
{
    rcube::raise_error("Error inserting in database: " . $db->is_error(), false, true); // raise error if not inserted
    hp_write_log('Error inserting in database');
}

hp_write_log('inserted in database');
hp_write_log('salt: ' . $salt . ' randomid: ' . $randomid);

// creating array for returning
$sid = array($randomid, $salt);
echo json_encode($sid, JSON_FORCE_OBJECT);

// function for writing logs after checking if debug flag is set
// enable disable logging in config.inc.php from config folder
function hp_write_log($log)
{
    global $debug_flag;
    if ($debug_flag) 
    {
        rcube::write_log('hashed_password', $log);
    }
}
