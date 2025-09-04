<?php

/**
 * db cleanup
 * 
 * script to clean salt values at regular intervals 
 * deletes records that were more than a day old
 * 
 * @version 1.0
 * 
 */

define('INSTALL_PATH', realpath(__DIR__ . '/../../') . '/');
include INSTALL_PATH . 'program/include/iniset.php';
$rcmail = rcmail::get_instance();

// connect to database
$db = rcube_db::factory($rcmail->config->get('db_dsnw'));   // get driver instance of db object
$db->set_debug();                                           // setting debug mode
$db->db_connect('w');                                       // connecting in write mode
if (!$db->is_connected()) 
{
    rcube::raise_error("Error connecting to database: " . $db->is_error(), false, true); // raise error if not connected
}

// delete records from salt table
$temp = $db -> query("DELETE FROM baya_v3_salt WHERE timestamp < (NOW() - INTERVAL 1 DAY)"); // fire query to insert data
if ($db -> is_error())
{
    rcube::raise_error("Error deleting from database: " . $db->is_error(), false, true); // raise error if not inserted
}