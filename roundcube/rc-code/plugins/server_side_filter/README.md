# Server Side Filter

=============================

###add/delete filter at server side


## steps to activate plugin in roundcube baya_v4

> cd roundcubemail/config
> vi config.inc.php
if server_side_filter plugin is not present in the plugin array, add it as below

$config['plugins'] = array(
'server_side_filter'
);

or if present uncomment it 

$config['plugins'] = array(
//'server_side_filter'
);

## to enable debugging

set server_side_filter_debug to true in main config.inc.php

> cd roundcubemail/config
> vi config.inc.php

$config['server_side_filter_debug'] = true;

if not present add this in main config.inc.php:

// Server side filter options    
// ---------------------------------    
// option to set debugging on or off    
// Default: false
    
$config['server_side_filter_debug'] = false;

Add below conf in  main config.inc.php:
```
//session timeout for ws
$config['ws_timeout'] = 30;
//connection timeout for ws
$config['ws_connectiontimeout'] = 30;
```
