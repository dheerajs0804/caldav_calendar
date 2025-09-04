# session expiry

=============================


## steps to activate plugin in roundcube baya_v4

> cd roundcubemail/config
> vi config.inc.php
if session expiry plugin is not present in the plugin array, add it as below

$config['plugins'] = array(
'session_expiry'
);

or if present uncomment it

$config['plugins'] = array(
//'session_expiry'
);

## to enable debugging

set session_expiry_debug to true in main config.inc.php

> cd roundcubemail/config
> vi config.inc.php

$config['session_expiry_debug'] = true;

if not present add this in main config.inc.php:

// session_expiry_debug options
// ---------------------------------
// option to set debugging on or off
// Default: false

$config['session_expiry_debug'] = false;

Add below conf in  main config.inc.php:
```
//session expiry timeout limit in millisecounds
$config['session_expiry_time'] = 1800000;

Note: session time is in millisecounds. 1800000 means 30 mins

//for staging i.e rc.mithi.com
//session domain to set cookies with specfied domain
$config['session_domain'] = 'mithi.com';

//for live i.e for production server
//session domain to set cookies with specfied domain
$config['session_domain'] = 'mithiskyconnect.com';

