<?php

// backend type (webservice, cache)
$config['xf_properties_driver'] = "webservice";

// array of xf entity to retrieve properties for. e.g. domain, user, role, usercos. 
// Check entity class present in plugin or not, if not need to write entity class for entity
$config['xf_directory_entities'] = array('user','domain');

// Entity wise list of properties to retrieve from xf directory
$config['userproperties'] = "classofservice,mail,username,defaultapp,imapserver,enablepersonalmailarchiving,mdcvacationreplystarttime,mdcvacationreplyendtime,mdcautoreplymaxnoofreply,autoresponsesubject,autoresponsemailbody,mdcautoreply,mdcautoreplytimeinterval,autoresponsemailbodyexternaluser,autoresponsesubjectexternaluser,mdcautoreplystatusexternaluser,messagelifetime";
$config['domainproperties'] = "domainname,xmppservername,ldapservername,incomingimapserver,outgoingsmtpserver,caldavservername,mailclientlogindomaingif";

//Add following block in host config file, so that it will connect to hostwise directory server.
//i.e. in ../skyconnect/config/<host>.inc.config.
//Add directory server info to get properties for configured entities
//$config['xf_directory_server'] = array(
//    'adminid' => 'postmaster@test.int',
//    'adminpass' => 'mailjol@123',
//    'host' => '192.168.0.57',
//    'port' => '8080'
//    );


?>
