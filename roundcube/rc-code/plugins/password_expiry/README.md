# Password Expiry

=============================

### Plugin to check if password is expired -

### checks if the user's password has been expired for given domain and email



## steps to activate plugin in roundcube baya_v4

> cd roundcubemail/config
> vi config.inc.php
if password_expiry plugin is not present in the plugin array, add it as below

$config['plugins'] = array(
'password_expiry'
);

or if present uncomment it 

$config['plugins'] = array(
//'password_expiry'
);

## to enable debugging

set pass_exp_debug to true in main config.inc.php

> cd roundcubemail/config
> vi config.inc.php

$config['pass_exp_debug'] = true;

if not present add this in main config.inc.php:

// Password expiry options    
// ---------------------------------    
// option to set debugging on or off    
// Default: false
    
$config['pass_exp_debug'] = false;

///creae table using below query in mysql container
create table bayav4_update_pass(id int NOT NULL primary key AUTO_INCREMENT,userid varchar(256) not null,captcha varchar(128) not null, timestamp timestamp default CURRENT_TIMESTAMP);

## Setup appkey and secretkey 

These keys are used for the webservice authentication. \
In main config.inc.php present at - roundcubemail/config/config.inc.php, add the following -

// app key required for request authentication
$config['appkey'] = 'MCS-HOSTED';

// secret key required for request authentication
$config['secretkey'] = 'MCS-HOSTED';
